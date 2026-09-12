/**
 * 시간으로 폭주를 막는 장치.
 *
 * 비용 상한은 사람이 끌 수 있고(제한 없음), 끄면 자동으로 멈추는 것이 아무것도
 * 남지 않는다. 세 가지 시간을 재서 그 구멍을 메운다.
 *
 * | 제한 | 재는 것 | 잡는 상황 |
 * |---|---|---|
 * | `jobSec` | **실행 시간 누적** (사람을 기다린 시간 제외) | 모델이 끝없이 도는 경우 |
 * | `idleSec` | 실행 중인데 아무 출력도 없는 시간 | 프로세스가 멎은 경우 |
 * | `sessionSec` | job 시작부터의 총 경과(대기 포함) | 위 둘을 빠져나간 장기 방치 |
 *
 * 사람의 입력이나 승인을 기다리는 동안은 `jobSec`·`idleSec` 을 세지 않는다.
 * 담당자가 점심을 먹고 와서 답하는 것은 폭주가 아니다. 그 시간까지 덮는 것은
 * `sessionSec` 하나뿐이고, 그래서 기본값이 훨씬 크다.
 *
 * 시간을 밖에서 받는다(`now`). 타이머를 흉내 내지 않고 값만 넣어 시험하기
 * 위해서다 — 30 분을 기다리는 테스트는 쓸 수 없다.
 */

export type LimitKind = 'job' | 'idle' | 'session';

export interface LimitSpec {
    /** 실행 시간 누적 상한(초). 0 이하면 끈다. */
    jobSec: number;
    /** 무응답 상한(초). 0 이하면 끈다. */
    idleSec: number;
    /** 총 경과 상한(초). 0 이하면 끈다. */
    sessionSec: number;
}

export interface Expiry {
    kind: LimitKind;
    limitSec: number;
    elapsedSec: number;
}

/** 화면과 로그에 그대로 쓰는 문구. 왜 멈췄는지 한 줄로 알 수 있어야 한다. */
export function describeExpiry(expiry: Expiry): string {
    const minutes = (sec: number) => `${Math.round(sec / 60)}분`;

    switch (expiry.kind) {
        case 'job':
            return `작업 시간 제한(${minutes(expiry.limitSec)})을 넘겨 자동으로 중단했습니다.`
                + ` 실제 실행 ${minutes(expiry.elapsedSec)} — 사람을 기다린 시간은 빼고 셉니다.`;
        case 'idle':
            return `${minutes(expiry.limitSec)} 동안 아무 반응이 없어 자동으로 중단했습니다.`
                + ' 작업 PC 나 Claude Code 가 멎었을 수 있습니다.';
        case 'session':
            return `작업이 시작된 지 ${minutes(expiry.elapsedSec)} 가 지나`
                + ` 최대 수명(${minutes(expiry.limitSec)})으로 자동 중단했습니다.`;
    }
}

export class TimeLimits {
    private startedAt = 0;

    /** 실행 구간의 시작 시각. null 이면 사람을 기다리는 중이다. */
    private runningSince: number | null = null;

    private accumulatedMs = 0;

    private lastActivityAt = 0;

    private stopped = true;

    constructor(private readonly spec: LimitSpec) {}

    start(now: number): void {
        this.startedAt = now;
        this.lastActivityAt = now;
        this.runningSince = now;
        this.accumulatedMs = 0;
        this.stopped = false;
    }

    /** 로그·출력이 있었다 = 살아 있다. */
    activity(now: number): void {
        this.lastActivityAt = now;
    }

    /** 사람을 기다리기 시작했다(입력 대기·승인 대기). */
    pause(now: number): void {
        if (this.runningSince === null) {
            return;
        }

        this.accumulatedMs += now - this.runningSince;
        this.runningSince = null;
    }

    /** 다시 돈다. 멈춰 있던 동안은 무응답으로 세지 않으므로 여기서 초기화한다. */
    resume(now: number): void {
        if (this.runningSince !== null) {
            return;
        }

        this.runningSince = now;
        this.lastActivityAt = now;
    }

    stop(): void {
        this.stopped = true;
    }

    /** 지금까지의 실행 시간 누적(ms). */
    activeMs(now: number): number {
        return this.accumulatedMs + (this.runningSince === null ? 0 : now - this.runningSince);
    }

    /**
     * 넘긴 제한이 있으면 알려 준다. 없으면 null.
     *
     * 총 수명 → 실행 시간 → 무응답 순으로 본다. 여럿이 동시에 걸렸다면 더 바깥의
     * 이유를 말하는 편이 사람에게 덜 헷갈린다.
     */
    check(now: number): Expiry | null {
        if (this.stopped) {
            return null;
        }

        const sec = (ms: number) => Math.round(ms / 1000);

        if (this.spec.sessionSec > 0 && now - this.startedAt >= this.spec.sessionSec * 1000) {
            return { kind: 'session', limitSec: this.spec.sessionSec, elapsedSec: sec(now - this.startedAt) };
        }

        const active = this.activeMs(now);

        if (this.spec.jobSec > 0 && active >= this.spec.jobSec * 1000) {
            return { kind: 'job', limitSec: this.spec.jobSec, elapsedSec: sec(active) };
        }

        // 사람을 기다리는 동안의 침묵은 무응답이 아니다.
        if (this.spec.idleSec > 0
            && this.runningSince !== null
            && now - this.lastActivityAt >= this.spec.idleSec * 1000) {
            return { kind: 'idle', limitSec: this.spec.idleSec, elapsedSec: sec(now - this.lastActivityAt) };
        }

        return null;
    }
}
