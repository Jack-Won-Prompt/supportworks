import { spawn } from 'node:child_process';
import { stat } from 'node:fs/promises';
import { ApiClient } from './api.js';
import { config } from './config.js';
import { log } from './logger.js';

/** 서버가 보내는 배포 실행 요청. */
export interface DeployRequest {
    deploy_id: number;
    job_id: number | null;
    project_id: number;
    name: string;
    working_dir: string;
    command: string;
    timeout_sec: number;
}

/**
 * 배포 명령 실행기.
 *
 * 운영 서버가 supportworks 서버와 다른 프로젝트를 위해 있다. 그 경우 서버에는
 * 작업 폴더 자체가 없어 늘 실패했다.
 *
 * **명령은 관리자가 등록한 대상 행에서만 온다.** 요청에서 오지 않는다는 것이
 * 이 기능의 안전장치 전부다 — 여기서는 받은 문자열을 그대로 셸에 넘긴다.
 * 그래서 Claude 가 이 경로를 건드릴 수 없어야 하고, 실제로 건드릴 수 없다:
 * 이 실행기는 세션 밖에 있고 샌드박스를 거치지 않는다.
 *
 * 같은 요청이 두 번 와도 한 번만 돈다(Reverb 재전송·폴링 중복 대비).
 */
export class Deployer {
    private readonly inFlight = new Set<number>();

    constructor(private readonly api: ApiClient) {}

    handle(request: DeployRequest): void {
        // 프로젝트별로 나눠 띄운 프로세스는 남의 배포 요청까지 받는다.
        // 걸러 주지 않으면 셋이 같은 배포를 동시에 돌린다.
        if (config.projectId !== null && Number(request.project_id) !== config.projectId) {
            return;
        }

        if (this.inFlight.has(request.deploy_id)) {
            return;
        }

        this.inFlight.add(request.deploy_id);

        void this.run(request).finally(() => this.inFlight.delete(request.deploy_id));
    }

    private async run(request: DeployRequest): Promise<void> {
        const id = request.deploy_id;

        log('info', '배포 요청 수신', { deployId: id, name: request.name });

        try {
            const dir = await stat(request.working_dir);

            if (!dir.isDirectory()) {
                throw new Error('폴더가 아닙니다');
            }
        } catch {
            await this.report(id, 'failed', null, `작업 폴더를 찾을 수 없습니다: ${request.working_dir}`);

            return;
        }

        await this.api.quiet('deploy running', () => this.api.deployResult(id, { status: 'running' }));

        const result = await this.execute(request);

        await this.report(
            id,
            result.code === 0 ? 'succeeded' : 'failed',
            result.code,
            result.output,
        );

        log(result.code === 0 ? 'info' : 'error', '배포 종료', { deployId: id, exit: result.code });
    }

    private execute(request: DeployRequest): Promise<{ code: number | null; output: string }> {
        return new Promise((resolve) => {
            // Windows 에서도 셸은 Git Bash 를 쓴다. deploy 명령은 대개 POSIX 문법이다.
            const shell = config.shell || (process.platform === 'win32' ? 'bash.exe' : '/bin/sh');

            const child = spawn(shell, ['-lc', request.command], {
                cwd: request.working_dir,
                windowsHide: true,
            });

            let output = '';
            const append = (chunk: Buffer) => {
                output += chunk.toString();

                // 출력이 수십 MB 로 불어나면 보고 자체가 실패한다. 앞부분만 남긴다.
                if (output.length > 200_000) {
                    output = output.slice(0, 200_000) + '\n…(이후 출력 생략)';
                }
            };

            child.stdout.on('data', append);
            child.stderr.on('data', append);

            // 끊긴 채로 영원히 "실행 중" 으로 남지 않게 한다.
            const timer = setTimeout(() => {
                output += `\n\n[중단] ${request.timeout_sec}초를 넘겨 강제 종료했습니다.`;
                child.kill('SIGKILL');
            }, Math.max(30, request.timeout_sec) * 1000);

            child.on('error', (error) => {
                clearTimeout(timer);
                resolve({ code: null, output: output + `\n\n[오류] ${String(error)}` });
            });

            child.on('close', (code) => {
                clearTimeout(timer);
                resolve({ code, output });
            });
        });
    }

    private async report(
        deployId: number,
        status: 'succeeded' | 'failed',
        exitCode: number | null,
        output: string,
    ): Promise<void> {
        await this.api.quiet('deploy result', () =>
            this.api.deployResult(deployId, { status, exit_code: exitCode, output }),
        );
    }
}
