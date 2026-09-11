import { mkdir, readFile, unlink, writeFile } from 'node:fs/promises';
import { join } from 'node:path';
import { config } from './config.js';

/**
 * 같은 PC 에서 데몬이 여러 개 뜨는 것을 막는다.
 *
 * 여러 인스턴스가 같은 토큰으로 붙으면 모두 같은 agent 채널을 구독해 같은 지시를
 * 받는다. 그 결과 같은 작업 폴더에서 git 이 동시에 돌아
 * "Unable to create '.git/index.lock': File exists" 로 터진다. 실제로 겪었다.
 *
 * 폴더별 직렬 실행은 프로세스 안에서만 보장되므로, 프로세스가 둘이면 그 보장이
 * 통째로 사라진다. 그래서 프로세스 경계에서 막는다.
 */

function lockPath(): string {
    return join(config.logDir, 'daemon.pid');
}

/** 그 PID 의 프로세스가 살아 있는가. 신호 0 은 존재 확인만 하고 아무것도 보내지 않는다. */
function isAlive(pid: number): boolean {
    try {
        process.kill(pid, 0);

        return true;
    } catch (error) {
        // EPERM 은 "있지만 내 권한으로 건드릴 수 없다" 이므로 살아 있는 것이다.
        return (error as NodeJS.ErrnoException)?.code === 'EPERM';
    }
}

export class AlreadyRunningError extends Error {}

/**
 * @throws AlreadyRunningError 다른 인스턴스가 이미 실행 중일 때
 */
export async function acquireLock(): Promise<void> {
    await mkdir(config.logDir, { recursive: true });

    const path = lockPath();
    let existing: number | null = null;

    try {
        const raw = (await readFile(path, 'utf8')).trim();
        const pid = Number(raw);

        if (Number.isInteger(pid) && pid > 0) {
            existing = pid;
        }
    } catch {
        // 파일이 없거나 읽을 수 없으면 잠금도 없는 것으로 본다.
    }

    if (existing !== null && existing !== process.pid && isAlive(existing)) {
        throw new AlreadyRunningError(
            `이미 데몬이 실행 중입니다 (PID ${existing}). 여러 인스턴스가 같은 지시를 받으면 `
            + '같은 폴더에서 git 이 충돌합니다. 기존 프로세스를 종료한 뒤 다시 시작하세요. '
            + `그 PID 가 데몬이 아니라고 확신하면 ${path} 를 지우세요.`,
        );
    }

    await writeFile(path, String(process.pid), 'utf8');
}

/** 정상 종료 시 잠금 해제. 강제 종료돼도 다음 기동이 PID 생존 여부로 판단한다. */
export async function releaseLock(): Promise<void> {
    try {
        const raw = (await readFile(lockPath(), 'utf8')).trim();

        // 남의 잠금을 지우지 않는다.
        if (Number(raw) !== process.pid) {
            return;
        }

        await unlink(lockPath());
    } catch {
        // 이미 없으면 할 일이 없다.
    }
}
