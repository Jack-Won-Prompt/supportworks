import './test-env.js';
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { TimeLimits, decideEnding, describeExpiry } from './time-limits.js';

const SPEC = { jobSec: 100, idleSec: 30, sessionSec: 1000 };
const S = 1000;

test('제한 안이면 아무것도 걸리지 않는다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);

    assert.equal(limits.check(10 * S), null);
});

test('실행 시간 누적이 상한을 넘으면 걸린다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);
    limits.activity(99 * S);

    assert.equal(limits.check(99 * S), null);
    assert.equal(limits.check(100 * S)?.kind, 'job');
});

test('사람을 기다린 시간은 작업 시간에 넣지 않는다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);

    // 50초 일하고, 500초 사람을 기다린 뒤, 다시 40초 일했다 = 실행 90초.
    limits.pause(50 * S);
    limits.resume(550 * S);
    limits.activity(590 * S);

    assert.equal(limits.activeMs(590 * S), 90 * S);
    assert.equal(limits.check(590 * S), null);

    // 10초를 더 일하면 100초가 되어 걸린다.
    assert.equal(limits.check(600 * S)?.kind, 'job');
});

test('실행 중 무응답이면 걸린다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);
    limits.activity(10 * S);

    assert.equal(limits.check(39 * S), null);
    assert.equal(limits.check(40 * S)?.kind, 'idle');
});

test('사람을 기다리는 동안의 침묵은 무응답이 아니다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);
    limits.pause(5 * S);

    // 담당자가 한참 뒤에 답해도 무응답으로 끊지 않는다.
    assert.equal(limits.check(900 * S), null);
});

test('재개하면 무응답 시계가 다시 시작한다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);
    limits.pause(5 * S);
    limits.resume(900 * S);

    assert.equal(limits.check(920 * S), null);
    assert.equal(limits.check(930 * S)?.kind, 'idle');
});

test('총 수명은 대기까지 포함해 잰다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);
    limits.pause(5 * S);

    // 실행은 5초뿐이고 무응답도 아니지만, 총 경과가 상한을 넘었다.
    assert.equal(limits.check(1000 * S)?.kind, 'session');
});

test('0 이하는 그 제한을 끈다', () => {
    const limits = new TimeLimits({ jobSec: 0, idleSec: 0, sessionSec: 0 });
    limits.start(0);

    assert.equal(limits.check(10 ** 9), null);
});

test('멈춘 뒤에는 더 걸리지 않는다', () => {
    const limits = new TimeLimits(SPEC);
    limits.start(0);
    limits.stop();

    assert.equal(limits.check(10 ** 9), null);
});

test('문구가 어떤 제한인지 알려 준다', () => {
    assert.match(describeExpiry({ kind: 'job', limitSec: 1800, elapsedSec: 1800 }), /작업 시간 제한\(30분\)/);
    assert.match(describeExpiry({ kind: 'idle', limitSec: 300, elapsedSec: 300 }), /5분 동안 아무 반응이 없어/);
    assert.match(describeExpiry({ kind: 'session', limitSec: 7200, elapsedSec: 7200 }), /최대 수명\(120분\)/);
});

// ── 어떻게 끝낼 것인가 ──────────────────────────────────────────────────────

test('답변을 기다리다 수명이 다하면 중단이 아니라 마친다', () => {
    // 모델은 제 턴을 마치고 보고까지 했다. 남은 것은 사람 차례였을 뿐이다.
    // 중단으로 적으면 작업 폴더가 되돌려지고 결과 반영 경로가 닫힌다 —
    // 끝난 일을 사람이 후속 지시로 다시 살려내야 했다.
    const ending = decideEnding({ kind: 'session', limitSec: 7200, elapsedSec: 7200 }, true);

    assert.equal(ending.as, 'completed');
    assert.match(ending.message, /세션을 마쳤습니다/);
    assert.match(ending.message, /결과 반영/);
});

test('확인한 사람이 없으므로 자동 배포는 잇지 않는다', () => {
    const waited = decideEnding({ kind: 'session', limitSec: 7200, elapsedSec: 7200 }, true);
    const ran    = decideEnding({ kind: 'session', limitSec: 7200, elapsedSec: 7200 }, false);

    assert.equal(waited.autoContinue, false);
    // 평소의 완료는 그대로 이어서 배포까지 간다. 이 변경이 그 길을 막으면 안 된다.
    assert.equal(ran.autoContinue, true);
});

test('사람을 기다린 것이 아니면 예전처럼 중단이다', () => {
    // 돌다가 수명이 다한 것은 폭주다. 하던 일을 되돌리는 쪽이 맞다.
    const ending = decideEnding({ kind: 'session', limitSec: 7200, elapsedSec: 7200 }, false);

    assert.equal(ending.as, 'cancelled');
    assert.match(ending.message, /자동 중단/);
});

test('실행 시간·무응답 만료는 기다리는 중이어도 중단이다', () => {
    // 이 둘은 애초에 사람을 기다리는 동안 세지 않는다. 그런데도 걸렸다면
    // 기다리는 상태가 아니라 무언가 잘못된 것이므로 되돌리는 편이 안전하다.
    for (const kind of ['job', 'idle'] as const) {
        const ending = decideEnding({ kind, limitSec: 1800, elapsedSec: 1800 }, true);

        assert.equal(ending.as, 'cancelled', kind);
    }
});
