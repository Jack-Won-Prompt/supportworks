import './test-env.js';
import assert from 'node:assert/strict';
import { mkdtemp, mkdir, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import type { ApiClient, ControlFlags, JobSpec } from './api.js';
import { REQUIRED_SECTIONS } from './handover.js';
import type { SessionAdapter, SessionEvents, SessionStartOptions, TurnUsage } from './session/adapter.js';
import { SessionManager } from './session-manager.js';

// ── 테스트 대역 ─────────────────────────────────────────────────────────────

const OK_FLAGS: ControlFlags = { cost_over_limit: false, cancel_requested: false, status: 'running' };

/** 관찰 가능한 가짜 서버. 실제 ApiClient 의 quiet() 의미(실패를 삼킴)를 그대로 흉내낸다. */
class FakeApi {
    readonly statuses: string[] = [];

    readonly delivered: number[] = [];

    readonly handovers: Record<string, unknown>[] = [];

    readonly assistantMessages: string[] = [];

    /** 다음 status 호출이 돌려줄 플래그. 비용 초과·취소 경로를 시험할 때 바꾼다. */
    flags: ControlFlags = OK_FLAGS;

    async quiet<T>(_label: string, fn: () => Promise<T>): Promise<T | null> {
        return fn();
    }

    async status(_jobId: number, status: string): Promise<ControlFlags> {
        this.statuses.push(status);

        return this.flags;
    }

    async start(): Promise<unknown> {
        return null;
    }

    async logs(): Promise<ControlFlags> {
        return OK_FLAGS;
    }

    async messages(_jobId: number, messages: { content: string }[]): Promise<unknown> {
        for (const message of messages) {
            this.assistantMessages.push(message.content);
        }

        return null;
    }

    async markDelivered(_jobId: number, messageId: number): Promise<unknown> {
        this.delivered.push(messageId);

        return null;
    }

    async handover(_jobId: number, payload: Record<string, unknown>): Promise<unknown> {
        this.handovers.push(payload);

        return null;
    }
}

/**
 * 가짜 세션 실행기.
 *
 * SDK 대신 테스트가 직접 턴을 흘려보낸다. 받은 프롬프트를 모두 기록해
 * "교체된 세션이 규칙을 다시 받았는가" 같은 질문에 답할 수 있게 한다.
 */
class FakeAdapter implements SessionAdapter {
    readonly kind = 'sdk' as const;

    readonly supportsPermissions = true;

    readonly sent: string[] = [];

    closed = false;

    startOptions: SessionStartOptions | null = null;

    private events!: SessionEvents;

    /** 각 세션 인스턴스를 테스트가 붙잡을 수 있도록 공유 배열에 등록한다. */
    constructor(private readonly registry: FakeAdapter[]) {
        registry.push(this);
    }

    async start(options: SessionStartOptions, events: SessionEvents): Promise<void> {
        this.startOptions = options;
        this.events = events;
        events.onSessionId(`session-${this.registry.length}`);
    }

    send(text: string): void {
        this.sent.push(text);
    }

    interrupt(): void {}

    async close(): Promise<void> {
        this.closed = true;
    }

    // ── 테스트가 조종하는 부분 ───────────────────────────────────────────────

    /** 한 턴이 끝났다고 알린다. SessionManager 는 이 안에서 게이지·인수인계를 판정한다. */
    turn(usage: TurnUsage, completed = false): void {
        this.events.onTurnEnd(usage, completed);
    }

    say(text: string): void {
        this.events.onAssistantText(text);
    }
}

// ── 대기 헬퍼 ───────────────────────────────────────────────────────────────

/**
 * 조건이 성립할 때까지 기다린다.
 *
 * SessionManager 의 내부 진행은 파일 읽기·쓰기를 거치므로 setImmediate 를 정해진
 * 횟수만큼 돌려서는 따라잡히지 않는다(실제 I/O 는 마이크로태스크가 아니다).
 * 고정 대기 대신 결과를 관찰하고, 성립하지 않으면 무엇이 안 됐는지 말하고 실패한다.
 */
async function waitFor(predicate: () => boolean, label: string): Promise<void> {
    for (let i = 0; i < 600 && !predicate(); i++) {
        await new Promise((resolve) => setTimeout(resolve, 5));
    }

    assert.ok(predicate(), `조건이 성립하지 않았습니다: ${label}`);
}

/** 아무 일도 일어나지 않아야 함을 확인하기 위한 짧은 여유. */
function breathe(): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, 50));
}

/** noUncheckedIndexedAccess 아래에서 인덱스 접근을 단언과 함께 좁힌다. */
function nth<T>(items: readonly T[], index: number): T {
    const item = items[index];

    assert.ok(item !== undefined, `${index}번 항목이 없습니다 (길이 ${items.length}).`);

    return item;
}

function makeJob(overrides: Partial<JobSpec> = {}): JobSpec {
    return {
        job_id: 1,
        project_id: 1,
        local_path: null,
        default_branch: null,
        use_branch: false,
        mode: 'batch',
        model: null,
        instruction: '테스트 지시문',
        allowed_tools: ['Read'],
        permission_mode: 'default',
        context_limit_tokens: 100_000,
        cost_limit_usd: 10,
        resume_session_id: null,
        ...overrides,
    };
}

interface Harness {
    manager: SessionManager;
    api: FakeApi;
    adapters: FakeAdapter[];
    root: string;
    terminal: { reason: string; detail?: string }[];
    /** 현재 살아 있는 세션. 인수인계 후에는 새 세션을 가리킨다. */
    current(): FakeAdapter;
}

async function harness(job: Partial<JobSpec> = {}, root?: string): Promise<Harness> {
    const dir = root ?? (await mkdtemp(join(tmpdir(), 'aiw-session-')));
    const api = new FakeApi();
    const adapters: FakeAdapter[] = [];
    const terminal: { reason: string; detail?: string }[] = [];

    const manager = new SessionManager(
        api as unknown as ApiClient,
        makeJob(job),
        { onTerminal: (reason, detail) => terminal.push({ reason, detail }) },
        () => new FakeAdapter(adapters),
    );

    await manager.run(dir);

    return {
        manager,
        api,
        adapters,
        root: dir,
        terminal,
        current: () => nth(adapters, adapters.length - 1),
    };
}

/** Claude 가 형식에 맞는 인수인계 문서를 쓴 것처럼 꾸민다. */
async function writeGoodHandover(root: string, jobId: number, index: number): Promise<void> {
    const dir = join(root, 'docs', 'aiw', 'handover');

    await mkdir(dir, { recursive: true });
    await writeFile(
        join(dir, `job-${jobId}-${index}.md`),
        REQUIRED_SECTIONS.join('\n내용\n') + '\n내용\n',
        'utf8',
    );
}

/**
 * 인수인계 한 바퀴를 끝까지 몰고 간다.
 *
 * @param rounds 데몬이 문서를 재요청하는 횟수. 문서가 없거나 형식 미달일 때 2가 된다.
 * @returns 새로 시작된 세션.
 */
async function driveHandover(h: Harness, rounds = 1): Promise<FakeAdapter> {
    const before = h.adapters.length;
    const ending = h.current();

    for (let i = 1; i <= rounds; i++) {
        await waitFor(() => ending.sent.length >= i, `${i}번째 인수인계 문서 요청`);
        ending.say('HANDOVER_DONE');
    }

    await waitFor(() => h.adapters.length > before, '교체 세션 시작');

    return nth(h.adapters, before);
}

// ── 토큰 집계 ───────────────────────────────────────────────────────────────

test('컨텍스트 토큰은 누적이 아니라 마지막 턴 값으로 덮어쓴다', async () => {
    const h = await harness();

    h.current().turn({ contextTokens: 30_000, cumulativeCostUsd: 0.4 });
    await waitFor(() => h.manager.metrics.contextTokens === 30_000, '첫 턴 반영');

    h.current().turn({ contextTokens: 34_000, cumulativeCostUsd: 0.9 });
    await waitFor(() => h.manager.metrics.contextTokens === 34_000, '둘째 턴 반영');

    // 합산이면 64,000 이 되어 한도의 60% 를 넘고 인수인계가 잘못 터진다.
    assert.equal(h.manager.metrics.contextTokens, 34_000);
    assert.equal(h.manager.metrics.costUsd, 0.9);
    assert.equal(h.adapters.length, 1, '세션이 교체되면 안 된다.');
});

test('컨텍스트가 줄어들면 게이지도 줄어든다', async () => {
    const h = await harness();

    h.current().turn({ contextTokens: 50_000, cumulativeCostUsd: 1 });
    await waitFor(() => h.manager.metrics.contextTokens === 50_000, '첫 턴 반영');

    h.current().turn({ contextTokens: 12_000, cumulativeCostUsd: 1.2 });
    await waitFor(() => h.manager.metrics.contextTokens === 12_000, '줄어든 값 반영');
});

test('비용은 SDK 의 누적값을 그대로 쓴다', async () => {
    const h = await harness();

    h.current().turn({ contextTokens: 10_000, cumulativeCostUsd: 0.25 });
    await waitFor(() => h.manager.metrics.costUsd === 0.25, '첫 비용');

    h.current().turn({ contextTokens: 11_000, cumulativeCostUsd: 0.70 });
    await waitFor(() => h.manager.metrics.costUsd === 0.70, '누적 비용');
});

// ── 인수인계 트리거 판정 ────────────────────────────────────────────────────

test('한도의 60% 미만이면 인수인계하지 않는다', async () => {
    const h = await harness();

    h.current().turn({ contextTokens: 59_999, cumulativeCostUsd: 0 });
    await breathe();

    assert.equal(h.adapters.length, 1);
    assert.equal(h.api.statuses.includes('handover'), false);
});

test('한도의 60% 이상이면 세션을 교체한다', async () => {
    const h = await harness({ mode: 'interactive' });

    await writeGoodHandover(h.root, 1, 1);

    const first = h.current();

    first.turn({ contextTokens: 60_000, cumulativeCostUsd: 1 });

    const second = await driveHandover(h);

    assert.equal(first.closed, true, '이전 세션은 닫혀야 한다.');
    assert.notEqual(second, first);

    await waitFor(() => h.api.handovers.length === 1, '서버 보고');

    const payload = nth(h.api.handovers, 0);

    assert.equal(payload.reason, 'context');
    assert.equal(payload.ended_session_id, 'session-1');
    assert.equal(payload.new_session_id, 'session-2');
    assert.equal(h.api.statuses.includes('handover'), true);
});

test('context_limit_tokens 가 0 이면 비율을 계산하지 않는다', async () => {
    const h = await harness({ context_limit_tokens: 0 });

    // 0 으로 나누면 Infinity 가 되어 첫 턴부터 무한 인수인계에 빠진다.
    h.current().turn({ contextTokens: 999_999, cumulativeCostUsd: 0 });
    await breathe();

    assert.equal(h.adapters.length, 1);
    assert.equal(h.api.statuses.includes('handover'), false);
});

test('batch 완료는 인수인계보다 우선한다', async () => {
    const h = await harness({ mode: 'batch' });

    // 한도를 넘겼지만 작업이 끝났다. 끝난 일을 정리해 넘길 이유가 없다.
    h.current().turn({ contextTokens: 90_000, cumulativeCostUsd: 1 }, true);

    await waitFor(() => h.terminal.length === 1, '종료 보고');

    assert.deepEqual(h.terminal, [{ reason: 'completed', detail: undefined }]);
    assert.equal(h.adapters.length, 1, '완료된 작업을 인수인계하면 안 된다.');
});

test('interactive 에서는 완료 신호가 와도 세션을 유지한다', async () => {
    const h = await harness({ mode: 'interactive' });

    h.current().turn({ contextTokens: 10_000, cumulativeCostUsd: 0.1 }, true);

    await waitFor(() => h.api.statuses.includes('waiting_input'), '입력 대기 전환');

    assert.deepEqual(h.terminal, []);
});

test('서버가 중단을 지시하면 인수인계 판정 전에 멈춘다', async () => {
    const h = await harness();

    h.api.flags = { cost_over_limit: true, cancel_requested: false, status: 'running' };
    h.current().turn({ contextTokens: 90_000, cumulativeCostUsd: 99 });

    await waitFor(() => h.terminal.length === 1, '중단 보고');

    assert.equal(nth(h.terminal, 0).reason, 'cancelled');
    assert.equal(h.adapters.length, 1, '중단 지시를 받고 새 세션을 띄우면 안 된다.');
});

// ── 인수인계 문서 실패 폴백 ─────────────────────────────────────────────────

test('문서 형식이 계속 미달이면 데몬 폴백으로 교체를 진행한다', async () => {
    const h = await harness({ mode: 'interactive' });

    // 문서를 만들지 않는다. 데몬은 2회 요청한 뒤 스스로 요약을 쓴다.
    h.current().turn({ contextTokens: 70_000, cumulativeCostUsd: 1 });

    await driveHandover(h, 2);
    await waitFor(() => h.api.handovers.length === 1, '서버 보고');

    assert.match(String(nth(h.api.handovers, 0).summary), /daemon-generated: true/);
});

// ── 입력 큐: 잠금 / 보류 / 재개 ─────────────────────────────────────────────

test('인수인계 중 들어온 메시지는 보류했다가 교체 후 순서대로 주입한다', async () => {
    const h = await harness({ mode: 'interactive' });

    await writeGoodHandover(h.root, 1, 1);

    const first = h.current();

    first.turn({ contextTokens: 70_000, cumulativeCostUsd: 1 });

    // 문서 요청이 나간 시점 = 교체가 진행 중인 창.
    await waitFor(() => first.sent.length >= 1, '인수인계 문서 요청');

    await h.manager.deliver(11, '첫 번째');
    await h.manager.deliver(12, '두 번째');

    assert.deepEqual(h.api.delivered, [], '보류 중에는 전달 확인을 보내지 않는다.');
    assert.equal(first.sent.includes('첫 번째'), false, '이전 세션에 흘러들면 안 된다.');

    first.say('HANDOVER_DONE');

    await waitFor(() => h.adapters.length === 2, '교체 세션 시작');
    await waitFor(() => h.api.delivered.length === 2, '보류 메시지 재개');

    assert.deepEqual(nth(h.adapters, 1).sent, ['첫 번째', '두 번째'], '순서가 보존되어야 한다.');
    assert.deepEqual(h.api.delivered, [11, 12]);
});

test('평시에는 메시지가 즉시 세션으로 간다', async () => {
    const h = await harness({ mode: 'interactive' });

    await h.manager.deliver(5, '지금 바로');

    assert.deepEqual(h.current().sent, ['지금 바로']);
    assert.deepEqual(h.api.delivered, [5]);
    assert.equal(h.api.statuses.at(-1), 'running');
});

// ── 프롬프트 구성 ───────────────────────────────────────────────────────────

test('CLAUDE.md 규칙은 첫 세션과 교체 세션 모두에 실린다', async () => {
    const root = await mkdtemp(join(tmpdir(), 'aiw-rules-'));

    await writeFile(join(root, 'CLAUDE.md'), '프로젝트 규칙: 탭 대신 스페이스', 'utf8');

    const h = await harness({ mode: 'interactive' }, root);
    const first = h.current();

    assert.match(String(first.startOptions?.prompt), /탭 대신 스페이스/);
    assert.match(String(first.startOptions?.prompt), /work order #1/);

    await writeGoodHandover(root, 1, 1);

    first.turn({ contextTokens: 70_000, cumulativeCostUsd: 1 });

    const second = await driveHandover(h);

    // 새 세션은 이전 맥락을 물려받지 않으므로 헤더와 규칙을 다시 받아야 한다.
    assert.match(String(second.startOptions?.prompt), /탭 대신 스페이스/);
    assert.match(String(second.startOptions?.prompt), /work order #1/);
    assert.equal(second.startOptions?.resumeSessionId, null, '문서로만 맥락을 잇는다.');
});

test('CLAUDE.md 가 없어도 프롬프트는 정상 구성된다', async () => {
    const h = await harness();

    const prompt = String(h.current().startOptions?.prompt);

    assert.match(prompt, /work order #1/);
    assert.match(prompt, /테스트 지시문/);
});

test('assistant 텍스트는 서버로 가지만 HANDOVER_DONE 은 새어나가지 않는다', async () => {
    const h = await harness({ mode: 'interactive' });
    const first = h.current();

    first.say('작업을 시작합니다');
    await waitFor(() => h.api.assistantMessages.length === 1, '대화 기록 전송');

    await writeGoodHandover(h.root, 1, 1);

    first.turn({ contextTokens: 70_000, cumulativeCostUsd: 1 });
    await driveHandover(h);
    await breathe();

    // 완료 신호는 제어용이므로 사용자 대화 기록에 남기지 않는다.
    assert.deepEqual(h.api.assistantMessages, ['작업을 시작합니다']);
});
