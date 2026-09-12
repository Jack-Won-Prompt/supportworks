import './test-env.js';
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { describeExpiry, TimeLimits } from './time-limits.js';

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
