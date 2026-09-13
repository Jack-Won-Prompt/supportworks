import './test-env.js';
import assert from 'node:assert/strict';
import { test } from 'node:test';
import { parseOpsRequests } from './ops-request.js';

test('블록이 없으면 본문 그대로', () => {
    const r = parseOpsRequests('그냥 답변입니다.');

    assert.equal(r.text, '그냥 답변입니다.');
    assert.deepEqual(r.names, []);
});

test('이름을 뽑고 블록은 본문에서 걷어낸다', () => {
    const raw = [
        '배포가 실패한 것 같습니다. 서버 상태를 보겠습니다.',
        '```aiw-ops',
        '점검',
        '```',
    ].join('\n');

    const r = parseOpsRequests(raw);

    assert.deepEqual(r.names, ['점검']);
    // 남겨 두면 사람이 같은 내용을 마크다운 원문으로 한 번 더 본다.
    assert.ok(!r.text.includes('aiw-ops'));
    assert.ok(r.text.includes('서버 상태를 보겠습니다'));
});

test('목록 표시를 붙여도 이름만 남는다', () => {
    const r = parseOpsRequests('```aiw-ops\n- 점검\n- 캐시 재생성\n```');

    assert.deepEqual(r.names, ['점검', '캐시 재생성']);
});

test('한 턴에 두 개까지만 받는다', () => {
    // 한 턴에 서버를 여러 번 건드리게 두지 않는다.
    const r = parseOpsRequests('```aiw-ops\n가\n나\n다\n라\n```');

    assert.deepEqual(r.names, ['가', '나']);
});

test('빈 블록은 아무것도 요청하지 않는다', () => {
    const r = parseOpsRequests('확인했습니다.\n```aiw-ops\n\n```');

    assert.deepEqual(r.names, []);
    assert.equal(r.text, '확인했습니다.');
});
