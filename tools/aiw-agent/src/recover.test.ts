import './test-env.js';
import assert from 'node:assert/strict';
import { test } from 'node:test';
import type { ApiClient, JobSpec } from './api.js';
import { recoverActiveJobs } from './recover.js';
import type { JobManager } from './job-manager.js';

function spec(overrides: Partial<JobSpec> & { job_id: number }): JobSpec {
    return {
        project_id: 1,
        local_path: 'E:/work',
        default_branch: null,
        use_branch: false,
        mode: 'interactive',
        model: null,
        instruction: '이어서 진행',
        allowed_tools: ['Read'],
        permission_mode: 'acceptEdits',
        context_limit_tokens: 200_000,
        cost_limit_usd: 1,
        resume_session_id: 'sess-1',
        ...overrides,
    };
}

class FakeApi {
    readonly failed: { jobId: number; reason: string }[] = [];

    /** pendingJobs(true) 가 돌려줄 스펙. */
    specs: JobSpec[] = [];

    /** true 면 스펙 조회가 실패한다. */
    throwOnFetch = false;

    lastIncludeActive: boolean | null = null;

    async quiet<T>(_label: string, fn: () => Promise<T>): Promise<T | null> {
        return fn();
    }

    async pendingJobs(includeActive = false): Promise<{ jobs: JobSpec[] }> {
        this.lastIncludeActive = includeActive;

        if (this.throwOnFetch) {
            throw new Error('네트워크 오류');
        }

        return { jobs: this.specs };
    }

    async fail(jobId: number, reason: string): Promise<unknown> {
        this.failed.push({ jobId, reason });

        return null;
    }
}

class FakeJobs {
    readonly enqueued: number[] = [];

    enqueue(s: JobSpec): void {
        this.enqueued.push(s.job_id);
    }
}

function build() {
    const api = new FakeApi();
    const jobs = new FakeJobs();

    const run = (ids: number[]) =>
        recoverActiveJobs(api as unknown as ApiClient, jobs as unknown as JobManager, ids);

    return { api, jobs, run };
}

test('활성 job 이 없으면 아무 요청도 하지 않는다', async () => {
    const b = build();

    await b.run([]);

    assert.equal(b.api.lastIncludeActive, null);
    assert.deepEqual(b.api.failed, []);
});

test('interactive 는 스펙을 받아 이어서 실행한다', async () => {
    const b = build();

    b.api.specs = [spec({ job_id: 7 })];
    await b.run([7]);

    assert.equal(b.api.lastIncludeActive, true, '활성 job 스펙을 요청해야 한다.');
    assert.deepEqual(b.jobs.enqueued, [7]);
    assert.deepEqual(b.api.failed, []);
});

test('batch 는 중복 실행을 피해 실패 처리한다', async () => {
    const b = build();

    // resume 이 실패하면 SDK 가 지시를 처음부터 다시 실행해 부작용이 두 번 일어난다.
    b.api.specs = [spec({ job_id: 8, mode: 'batch' })];
    await b.run([8]);

    assert.deepEqual(b.jobs.enqueued, []);
    assert.equal(b.api.failed.length, 1);
    assert.match(b.api.failed[0]?.reason ?? '', /중복 실행/);
});

test('스펙을 받지 못한 job 은 조용히 버리지 않고 실패 처리한다', async () => {
    const b = build();

    b.api.specs = [];
    await b.run([9]);

    // 여기서 아무것도 안 하면 서버는 running 인데 아무도 돌보지 않는 고아가 된다.
    assert.deepEqual(b.jobs.enqueued, []);
    assert.deepEqual(b.api.failed.map((f) => f.jobId), [9]);
});

test('스펙 조회 자체가 실패해도 고아를 남기지 않는다', async () => {
    const b = build();

    b.api.throwOnFetch = true;
    await b.run([9, 10]);

    assert.deepEqual(b.api.failed.map((f) => f.jobId), [9, 10]);
});

test('여러 건이 섞여 있으면 각각 다르게 처리한다', async () => {
    const b = build();

    b.api.specs = [spec({ job_id: 1 }), spec({ job_id: 2, mode: 'batch' })];
    await b.run([1, 2, 3]);

    assert.deepEqual(b.jobs.enqueued, [1]);
    assert.deepEqual(b.api.failed.map((f) => f.jobId), [2, 3]);
});
