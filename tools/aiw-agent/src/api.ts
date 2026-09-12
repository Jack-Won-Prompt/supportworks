import axios, { AxiosInstance, isAxiosError } from 'axios';
import { config } from './config.js';
import { FailureCode } from './errors.js';
import { log } from './logger.js';

export interface JobSpec {
    job_id: number;
    project_id: number;
    local_path: string | null;
    default_branch: string | null;
    use_branch: boolean;
    mode: 'batch' | 'interactive';
    model: string | null;
    instruction: string;
    allowed_tools: string[];
    permission_mode: 'acceptEdits' | 'default';
    context_limit_tokens: number;
    /** null 이면 제한 없음. 판단은 서버가 하고 데몬은 cost_over_limit 만 본다. */
    cost_limit_usd: number | null;
    resume_session_id: string | null;
    status?: string;
    /** 최초 지시문에 붙은 이미지. 내용은 id 로 따로 받는다. */
    attachments?: { id: number; mime: string }[];
}

/** 모든 상태성 응답에 실리는 중단 신호. Reverb 이벤트를 놓쳐도 이걸로 따라잡는다. */
export interface ControlFlags {
    cost_over_limit: boolean;
    cancel_requested: boolean;
    status: string;
}

export interface LogEntry {
    seq: number;
    type: 'system' | 'tool_use' | 'tool_result' | 'result' | 'error' | 'daemon' | 'handover';
    content: string;
    raw?: unknown;
}

export class ApiClient {
    private readonly http: AxiosInstance;

    constructor() {
        this.http = axios.create({
            baseURL: `${config.baseUrl}/api/aiw`,
            timeout: 20000,
            headers: {
                Authorization: `Bearer ${config.token}`,
                Accept: 'application/json',
            },
        });
    }

    // ── 공통 ────────────────────────────────────────────────────────────────

    /**
     * 일시적 오류(네트워크·5xx)는 재시도한다. 4xx 는 재시도해도 같은 결과이므로 즉시 던진다.
     * 보고가 실패해도 작업 자체는 진행되어야 하므로, 호출자가 삼킬 수 있게 예외를 그대로 올린다.
     */
    private async send<T>(fn: () => Promise<{ data: T }>, attempts = 3): Promise<T> {
        let lastError: unknown;

        for (let i = 0; i < attempts; i++) {
            try {
                return (await fn()).data;
            } catch (error) {
                lastError = error;

                const status = isAxiosError(error) ? error.response?.status : undefined;

                if (status && status >= 400 && status < 500) {
                    throw error;
                }

                if (i < attempts - 1) {
                    await new Promise((r) => setTimeout(r, 500 * 2 ** i));
                }
            }
        }

        throw lastError;
    }

    // ── 데몬 공통 ───────────────────────────────────────────────────────────

    heartbeat(capabilities: Record<string, unknown>) {
        return this.send<{
            agent_id: number;
            server_time: string;
            pending_job_ids: number[];
            active_job_ids: number[];
        }>(() => this.http.post('/heartbeat', {
            capabilities,
            // 프로젝트별로 나눠 띄운 경우, 어느 프로젝트가 살아 있는지 매핑에 남긴다.
            // 담당자 전체의 생존만 보면 멈춘 프로젝트가 온라인으로 보인다.
            ...(config.projectId !== null ? { project_id: config.projectId } : {}),
        }));
    }

    /** 이 PC 가 맡은 프로젝트 목록. 셋업 데몬이 프로세스를 몇 개 띄울지 정하는 근거. */
    mappings() {
        return this.send<{
            agent_id: number;
            mappings: {
                project_id: number;
                project_name: string | null;
                display_name: string;
                local_path: string;
                default_branch: string | null;
            }[];
        }>(() => this.http.get('/mappings'));
    }

    /**
     * @param includeActive 재기동 복구용. 활성 job 의 스펙도 함께 받는다
     *                      (resume_session_id 가 실려 온다).
     */
    pendingJobs(includeActive = false) {
        const params: Record<string, unknown> = {};

        if (includeActive) { params.resume = 1; }

        // 남의 프로젝트 일감을 집어가면 같은 폴더에서 git 이 부딪힌다.
        if (config.projectId !== null) { params.project_id = config.projectId; }

        return this.send<{ jobs: JobSpec[] }>(() =>
            this.http.get('/jobs/pending', Object.keys(params).length ? { params } : undefined),
        );
    }

    /** 첨부 원본을 base64 로 받는다. 파일로 떨어뜨리지 않는다. */
    async attachment(jobId: number, attachmentId: number): Promise<string> {
        const response = await this.http.get(`/jobs/${jobId}/attachments/${attachmentId}`, {
            responseType: 'arraybuffer',
            // 이미지는 20초로 부족할 수 있다.
            timeout: 60000,
        });

        return Buffer.from(response.data as ArrayBuffer).toString('base64');
    }

    inbox(jobId: number) {
        return this.send<
            {
                messages: {
                    message_id: number;
                    seq: number;
                    content: string;
                    attachments?: { id: number; mime: string }[];
                }[];
                permissions: { request_key: string; status: string; deny_reason: string | null }[];
            } & ControlFlags
        >(() => this.http.get(`/jobs/${jobId}/inbox`));
    }

    // ── job 보고 ────────────────────────────────────────────────────────────

    start(jobId: number, sessionId: string, resumed = false) {
        return this.send<ControlFlags>(() =>
            this.http.post(`/jobs/${jobId}/start`, { session_id: sessionId, resumed }),
        );
    }

    logs(jobId: number, logs: LogEntry[]) {
        return this.send<{ accepted: number } & ControlFlags>(() =>
            this.http.post(`/jobs/${jobId}/logs`, { logs }),
        );
    }

    /**
     * 대화 메시지 저장. **번호는 서버가 매긴다.**
     *
     * 예전에는 여기서 seq 를 보냈는데, 서버도 사람의 메시지에 자기 번호를 매기고
     * 있어 둘이 부딪혔다. unique(job_id, seq) 에 걸린 답변은 조용히 사라졌다.
     * 지금은 재전송 판별용 client_key 만 보내고, 응답으로 id 와 seq 를 받는다.
     */
    messages(
        jobId: number,
        messages: {
            /** 같은 메시지를 두 번 보냈는지 서버가 가리는 키. */
            client_key: string;
            role: 'assistant' | 'handover';
            content: string;
            /** 모델이 제시한 선택지. 화면이 버튼으로 그린다. */
            choices?: string[];
        }[],
    ) {
        return this.send<{
            accepted: number;
            messages: { client_key: string | null; id: number; seq: number }[];
        } & ControlFlags>(() => this.http.post(`/jobs/${jobId}/messages`, { messages }));
    }

    /** 담당자 PC 가 실행한 배포의 진행/결과 보고. */
    deployResult(
        deployId: number,
        payload: { status: 'running' | 'succeeded' | 'failed'; exit_code?: number | null; output?: string },
    ) {
        return this.send<{ status: string }>(() => this.http.post(`/deploys/${deployId}`, payload));
    }

    /** 커밋·푸시 진행/결과 보고. */
    publishResult(
        jobId: number,
        publishId: number,
        payload: { status: 'running' | 'succeeded' | 'failed'; output?: string; commit_sha?: string },
    ) {
        return this.send<{ status: string }>(() =>
            this.http.post(`/jobs/${jobId}/publishes/${publishId}`, payload),
        );
    }

    /**
     * 담당자가 만든 결과물(스크린샷)을 그 발언에 붙인다.
     *
     * 붙일 곳은 messages() 응답으로 받은 id 로 지정한다 — 번호를 서버가 매기므로
     * 데몬은 seq 를 미리 알 수 없다.
     */
    async uploadAttachment(
        jobId: number,
        messageId: number,
        filename: string,
        mime: string,
        data: Buffer,
    ): Promise<{ attachment_id: number | null }> {
        const form = new FormData();

        form.append('message_id', String(messageId));
        form.append('file', new Blob([new Uint8Array(data)], { type: mime }), filename);

        const response = await this.http.post(`/jobs/${jobId}/attachments`, form, {
            // 이미지는 20초로 부족할 수 있다.
            timeout: 60000,
        });

        return response.data as { attachment_id: number | null };
    }

    markDelivered(jobId: number, messageId: number) {
        return this.send<ControlFlags>(() =>
            this.http.post(`/jobs/${jobId}/messages/${messageId}/delivered`, {}),
        );
    }

    status(
        jobId: number,
        status: 'running' | 'waiting_input' | 'waiting_permission' | 'handover',
        metrics: { context_tokens?: number; cost_usd?: number } = {},
    ) {
        return this.send<ControlFlags>(() =>
            this.http.post(`/jobs/${jobId}/status`, { status, ...metrics }),
        );
    }

    requestPermission(jobId: number, requestKey: string, toolName: string, toolInput: unknown) {
        return this.send<
            {
                request_key: string;
                status: string;
                decision: { behavior: 'allow' | 'deny' | 'pending'; message?: string };
            } & ControlFlags
        >(() =>
            this.http.post(`/jobs/${jobId}/permissions`, {
                request_key: requestKey,
                tool_name: toolName,
                tool_input: toolInput,
            }),
        );
    }

    handover(
        jobId: number,
        payload: {
            ended_session_id: string;
            new_session_id: string;
            document_path: string;
            summary: string;
            reason?: string;
        },
    ) {
        return this.send<{ handover_count: number } & ControlFlags>(() =>
            this.http.post(`/jobs/${jobId}/handover`, payload),
        );
    }

    complete(
        jobId: number,
        payload: {
            result_summary?: string | null;
            changed_files?: string[] | null;
            git_diff?: string | null;
            cost_usd?: number;
            duration_ms?: number;
        },
    ) {
        return this.send<ControlFlags>(() => this.http.post(`/jobs/${jobId}/complete`, payload));
    }

    /**
     * @param extra error_code 는 화면이 복구 버튼을 고르는 데 쓴다(서버가 화이트리스트로 검증).
     */
    fail(
        jobId: number,
        errorMessage: string,
        extra: {
            cost_usd?: number;
            duration_ms?: number;
            error_code?: FailureCode;
            error_detail?: Record<string, unknown>;
        } = {},
    ) {
        return this.send<ControlFlags>(() =>
            this.http.post(`/jobs/${jobId}/fail`, { error_message: errorMessage, ...extra }),
        );
    }

    /** 보고 실패가 작업을 죽이지 않게 감싼다. 서버는 폴링·스케줄러로 결국 따라잡는다. */
    async quiet<T>(label: string, fn: () => Promise<T>): Promise<T | null> {
        try {
            return await fn();
        } catch (error) {
            log('warn', `서버 보고 실패: ${label}`, { error: String(error) });

            return null;
        }
    }
}
