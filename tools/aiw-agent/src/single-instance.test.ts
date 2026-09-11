import './test-env.js';
import assert from 'node:assert/strict';
import { spawn, ChildProcess } from 'node:child_process';
import { readFile, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { test } from 'node:test';
import { config } from './config.js';
import { AlreadyRunningError, acquireLock, releaseLock } from './single-instance.js';

const LOCK = join(config.logDir, 'daemon.pid');

/** 절대 존재하지 않을 PID. 리눅스 기본 상한을 넘는 값을 쓴다. */
const DEAD_PID = 4_194_305;

async function clear(): Promise<void> {
    await releaseLock();
    await writeFile(LOCK, '', 'utf8').catch(() => undefined);
}

/** 살아 있는 다른 PID 가 필요하다. PID 1 은 Windows 에 없어 쓸 수 없다. */
function spawnIdle(): ChildProcess {
    const child = spawn(process.execPath, ['-e', 'setTimeout(() => {}, 60000)'], {
        stdio: 'ignore',
    });

    child.unref();

    return child;
}

test('잠금이 없으면 획득하고 자기 PID 를 남긴다', async () => {
    await clear();
    await acquireLock();

    assert.equal((await readFile(LOCK, 'utf8')).trim(), String(process.pid));

    await releaseLock();
});

test('살아 있는 다른 인스턴스가 있으면 거부한다', async () => {
    await clear();

    const other = spawnIdle();

    try {
        assert.ok(other.pid, '자식 프로세스를 띄우지 못했습니다.');
        await writeFile(LOCK, String(other.pid), 'utf8');

        await assert.rejects(() => acquireLock(), (error: unknown) => {
            assert.ok(error instanceof AlreadyRunningError);
            assert.match((error as Error).message, /이미 데몬이 실행 중/);

            return true;
        });

        // 거부됐으면 남의 PID 를 덮어쓰지 않아야 한다.
        assert.equal((await readFile(LOCK, 'utf8')).trim(), String(other.pid));
    } finally {
        other.kill();
    }
});

test('죽은 PID 의 잠금은 넘겨받는다', async () => {
    await clear();
    await writeFile(LOCK, String(DEAD_PID), 'utf8');

    // 강제 종료로 잠금이 남았을 때 영원히 못 뜨면 곤란하다.
    await acquireLock();

    assert.equal((await readFile(LOCK, 'utf8')).trim(), String(process.pid));

    await releaseLock();
});

test('깨진 잠금 파일은 무시한다', async () => {
    await clear();
    await writeFile(LOCK, '이건 PID 가 아니다', 'utf8');

    await acquireLock();

    assert.equal((await readFile(LOCK, 'utf8')).trim(), String(process.pid));

    await releaseLock();
});

test('같은 프로세스가 다시 획득해도 막히지 않는다', async () => {
    await clear();
    await acquireLock();

    // 재시도가 자기 자신 때문에 실패하면 안 된다.
    await acquireLock();

    await releaseLock();
});

test('남의 잠금은 해제하지 않는다', async () => {
    await clear();

    const other = spawnIdle();

    try {
        await writeFile(LOCK, String(other.pid), 'utf8');
        await releaseLock();

        assert.equal(
            (await readFile(LOCK, 'utf8')).trim(),
            String(other.pid),
            '다른 인스턴스의 잠금을 지우면 안 된다.',
        );
    } finally {
        other.kill();
        await clear();
    }
});
