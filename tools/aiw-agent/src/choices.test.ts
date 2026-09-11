import assert from 'node:assert/strict';
import { test } from 'node:test';
import { parseChoices } from './choices.js';

/** 백틱 3개. 소스에 그대로 쓰면 이 파일의 마크다운 표시가 깨진다. */
const F = '`'.repeat(3);

const block = (body: string) => `${F}aiw-choices\n${body}\n${F}`;

test('선택지 블록을 뽑고 본문에서 제거한다', () => {
    const raw = `어떻게 할지 정해 주세요.\n\n${block('- 관리자 화면에서 켜기\n- 마이그레이션으로 켜기')}\n\n골라 주시면 진행합니다.`;

    const parsed = parseChoices(raw);

    assert.deepEqual(parsed.choices, ['관리자 화면에서 켜기', '마이그레이션으로 켜기']);
    assert.equal(parsed.text.includes('aiw-choices'), false, '원문 블록이 남으면 안 된다.');
    assert.match(parsed.text, /어떻게 할지 정해 주세요/);
    assert.match(parsed.text, /골라 주시면 진행합니다/);
});

test('블록이 없으면 본문 그대로, 선택지는 빈 배열', () => {
    const raw = '작업을 끝냈습니다.\n\n1. 원인은 설정값\n2. 조치는 DB 수정';

    const parsed = parseChoices(raw);

    // 평범한 번호 목록을 버튼으로 착각하면 안 된다.
    assert.deepEqual(parsed.choices, []);
    assert.equal(parsed.text, raw);
});

test('- 와 * 를 모두 받고 빈 줄은 무시한다', () => {
    const parsed = parseChoices(block('- 하나\n\n* 둘\n   - 셋  '));

    assert.deepEqual(parsed.choices, ['하나', '둘', '셋']);
});

test('불릿 없는 줄도 선택지로 받는다', () => {
    // 모델이 형식을 살짝 어겨도 의도는 분명하다.
    assert.deepEqual(parseChoices(block('하나\n둘')).choices, ['하나', '둘']);
});

test('중복은 하나로 합친다', () => {
    assert.deepEqual(parseChoices(block('- 같음\n- 같음\n- 다름')).choices, ['같음', '다름']);
});

test('6개를 넘으면 앞에서 자른다', () => {
    const many = Array.from({ length: 10 }, (_, i) => `- 선택 ${i}`).join('\n');

    assert.equal(parseChoices(block(many)).choices.length, 6);
});

test('너무 긴 선택지는 잘라 버튼에 들어가게 한다', () => {
    const parsed = parseChoices(block('- ' + 'X'.repeat(500)));

    assert.equal(parsed.choices[0]?.length, 200);
});

test('블록이 여러 개면 모두 모은다', () => {
    const raw = `${block('- 하나')}\n중간 설명\n${block('- 둘')}`;

    const parsed = parseChoices(raw);

    assert.deepEqual(parsed.choices, ['하나', '둘']);
    assert.equal(parsed.text, '중간 설명');
});

test('빈 블록은 선택지를 만들지 않는다', () => {
    const parsed = parseChoices(`설명\n${block('')}`);

    assert.deepEqual(parsed.choices, []);
    assert.equal(parsed.text, '설명');
});

test('본문이 블록뿐이면 빈 문자열이 된다', () => {
    const parsed = parseChoices(block('- 하나'));

    assert.equal(parsed.text, '');
    assert.deepEqual(parsed.choices, ['하나']);
});
