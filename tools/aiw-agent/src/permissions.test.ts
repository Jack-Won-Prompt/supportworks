import './test-env.js';
import assert from 'node:assert/strict';
import { mkdtemp } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import type { ApiClient } from './api.js';
import { PermissionGate } from './permissions.js';
import { Sandbox } from './sandbox.js';

/** 서버 ToolPolicy 와 같은 목록. Bash 는 의도적으로 빠져 있다. */
const AUTO_APPROVABLE = ['Read', 'Edit', 'Write', 'Glob', 'Grep'];

interface AskRecord {
    toolName: string;
    input: unknown;
}

class FakeApi {
    readonly asked: AskRecord[] = [];

    /** 서버가 즉시 돌려줄 결정. 'pending' 이면 사람을 기다린다. */
    behavior: 'allow' | 'deny' | 'pending' = 'allow';

    /** requestPermission 이 던지게 해서 통신 실패 경로를 시험한다. */
    throwOnRequest = false;

    lastRequestKey: string | null = null;

    async quiet<T>(_label: string, fn: () => Promise<T>): Promise<T | null> {
        return fn();
    }

    async status(): Promise<unknown> {
        return null;
    }

    async requestPermission(_jobId: number, requestKey: string, toolName: string, input: unknown) {
        if (this.throwOnRequest) {
            throw new Error('네트워크 오류');
        }

        this.lastRequestKey = requestKey;
        this.asked.push({ toolName, input });

        return { decision: { behavior: this.behavior, message: '사용자가 거부했습니다.' } };
    }

    async inbox() {
        return { messages: [], permissions: [], cancel_requested: false, cost_over_limit: false };
    }
}

interface Built {
    gate: PermissionGate;
    api: FakeApi;
    blocked: { tool: string; reason: string }[];
    waiting: number;
    root: string;
}

async function build(mode: 'acceptEdits' | 'default'): Promise<Built> {
    const root = await mkdtemp(join(tmpdir(), 'aiw-perm-'));
    const api = new FakeApi();
    const blocked: { tool: string; reason: string }[] = [];
    const built: Built = { gate: null as never, api, blocked, waiting: 0, root };

    built.gate = new PermissionGate(
        api as unknown as ApiClient,
        1,
        await Sandbox.create(root),
        mode,
        AUTO_APPROVABLE,
        {
            onWaiting: () => {
                built.waiting++;
            },
            onResumed: () => {},
            onBlocked: (tool, reason) => blocked.push({ tool, reason }),
        },
    );

    return built;
}

// ── 자동 승인 경계 ──────────────────────────────────────────────────────────

test('acceptEdits 라도 Bash 는 자동 승인되지 않는다', async () => {
    const b = await build('acceptEdits');

    const decision = await b.gate.check('Bash', { command: 'npm test' });

    assert.equal(decision.allowed, true, '서버가 허용했으므로 결과는 허용이다.');
    assert.deepEqual(
        b.api.asked.map((a) => a.toolName),
        ['Bash'],
        '임의 명령 실행은 반드시 승인 카드를 거쳐야 한다.',
    );
});

test('acceptEdits 에서 편집 계열 툴은 서버에 묻지 않는다', async () => {
    const b = await build('acceptEdits');

    for (const tool of AUTO_APPROVABLE) {
        const decision = await b.gate.check(tool, { file_path: join(b.root, 'a.txt') });

        assert.equal(decision.allowed, true, `${tool} 이 자동 승인되어야 한다.`);
    }

    assert.deepEqual(b.api.asked, [], '자동 승인은 서버 왕복이 없어야 한다.');
});

test('default 모드에서는 편집 툴도 매번 서버에 묻는다', async () => {
    const b = await build('default');

    await b.gate.check('Read', { file_path: join(b.root, 'a.txt') });

    assert.deepEqual(b.api.asked.map((a) => a.toolName), ['Read']);
});

// ── 샌드박스 우선 ───────────────────────────────────────────────────────────

test('샌드박스에 걸리면 서버에 묻지도 않고 거부한다', async () => {
    const b = await build('acceptEdits');

    const decision = await b.gate.check('Bash', { command: 'git push origin master' });

    assert.equal(decision.allowed, false);
    assert.deepEqual(b.api.asked, [], '사람이 실수로 허용을 눌러도 통과하면 안 되는 것들이다.');
    assert.equal(b.blocked.length, 1);
    assert.equal(b.blocked[0]?.tool, 'Bash');
});

test('작업 폴더 밖 경로는 자동 승인 목록에 있어도 거부한다', async () => {
    const b = await build('acceptEdits');

    const outside = process.platform === 'win32' ? 'C:\\Windows\\System32\\drivers\\etc\\hosts' : '/etc/hosts';
    const decision = await b.gate.check('Write', { file_path: outside });

    assert.equal(decision.allowed, false, '샌드박스 검사가 acceptEdits 보다 먼저다.');
    assert.deepEqual(b.api.asked, []);
});

// ── 서버 결정 ───────────────────────────────────────────────────────────────

test('서버가 거부하면 거부 사유를 그대로 전달한다', async () => {
    const b = await build('default');

    b.api.behavior = 'deny';

    const decision = await b.gate.check('Bash', { command: 'npm test' });

    assert.equal(decision.allowed, false);
    assert.equal(decision.reason, '사용자가 거부했습니다.');
});

test('서버에 물을 수 없으면 거부한다', async () => {
    const b = await build('default');

    b.api.throwOnRequest = true;

    const decision = await b.gate.check('Bash', { command: 'npm test' });

    assert.equal(decision.allowed, false, '확인되지 않은 실행을 통과시키면 안 된다.');
    assert.match(String(decision.reason), /승인을 요청할 수 없어/);
});

// ── 대기와 해소 ─────────────────────────────────────────────────────────────

test('pending 이면 사람의 결정을 기다렸다가 재개한다', async () => {
    const b = await build('default');

    b.api.behavior = 'pending';

    const pending = b.gate.check('Bash', { command: 'npm test' });

    // 대기 상태가 알려져야 UI 가 승인 카드를 띄운다.
    await waitFor(() => b.waiting === 1, '승인 대기 알림');

    b.gate.resolve(String(b.api.lastRequestKey), { allowed: true });

    assert.deepEqual(await pending, { allowed: true });
});

test('세션이 끝나면 대기 중인 승인을 모두 거부로 푼다', async () => {
    const b = await build('default');

    b.api.behavior = 'pending';

    const pending = b.gate.check('Bash', { command: 'npm test' });

    await waitFor(() => b.waiting === 1, '승인 대기 알림');

    // 풀어 주지 않으면 데몬 프로세스가 영원히 매달린다.
    b.gate.abortAll('작업이 종료되었습니다.');

    const decision = await pending;

    assert.equal(decision.allowed, false);
    assert.equal(decision.reason, '작업이 종료되었습니다.');
});

test('모르는 request_key 로 온 결정은 무시한다', async () => {
    const b = await build('default');

    // 다른 작업의 이벤트가 새어 들어와도 아무 일도 일어나지 않아야 한다.
    assert.doesNotThrow(() => b.gate.resolve('없는-키', { allowed: true }));
});

async function waitFor(predicate: () => boolean, label: string): Promise<void> {
    for (let i = 0; i < 400 && !predicate(); i++) {
        await new Promise((resolve) => setTimeout(resolve, 5));
    }

    assert.ok(predicate(), `조건이 성립하지 않았습니다: ${label}`);
}
