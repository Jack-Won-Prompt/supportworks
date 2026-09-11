import './test-env.js';
import assert from 'node:assert/strict';
import { mkdtemp, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import { REQUIRED_SECTIONS, handoverPrompt, resumePrompt, validateHandover, writeFallbackHandover } from './handover.js';

async function scratch(): Promise<string> {
    return mkdtemp(join(tmpdir(), 'aiw-handover-'));
}

test('인수인계 문서는 7개 섹션을 모두 갖춰야 통과한다', async () => {
    const dir = await scratch();
    const path = join(dir, 'doc.md');

    await writeFile(path, REQUIRED_SECTIONS.join('\n내용\n') + '\n내용\n', 'utf8');

    const ok = await validateHandover(path);
    assert.equal(ok.ok, true);
    assert.deepEqual(ok.missing, []);
});

test('섹션이 빠지면 무엇이 빠졌는지 알려준다', async () => {
    const dir = await scratch();
    const path = join(dir, 'doc.md');

    // 마지막 두 섹션을 뺀다.
    await writeFile(path, REQUIRED_SECTIONS.slice(0, 5).join('\n내용\n'), 'utf8');

    const result = await validateHandover(path);
    assert.equal(result.ok, false);
    assert.equal(result.missing.length, 2);
    assert.ok(result.missing.includes('## 다음 세션이 바로 실행할 첫 작업'));
});

test('파일이 없으면 전 섹션 누락으로 본다', async () => {
    const dir = await scratch();

    const result = await validateHandover(join(dir, 'none.md'));
    assert.equal(result.ok, false);
    assert.equal(result.missing.length, REQUIRED_SECTIONS.length);
});

test('폴백 문서는 7개 섹션을 갖추고 자동 생성 표기를 남긴다', async () => {
    const dir = await scratch();
    // writeFallbackHandover 가 상위 디렉터리를 직접 만들어야 한다.
    const path = join(dir, 'docs', 'aiw', 'fallback.md');

    await writeFallbackHandover(path, 42, '원 지시문입니다', ['첫 응답', '두 번째 응답']);

    const result = await validateHandover(path);
    assert.equal(result.ok, true, '폴백도 검증을 통과해야 다음 세션이 쓸 수 있다.');
    assert.ok(result.content.includes('daemon-generated: true'), '사람이 품질을 오해하지 않게 표기가 있어야 한다.');
    assert.ok(result.content.includes('원 지시문입니다'));
    assert.ok(result.content.includes('두 번째 응답'));
});

test('인수인계 프롬프트가 고정 섹션과 완료 신호를 담는다', () => {
    const prompt = handoverPrompt(7, 2);

    for (const section of REQUIRED_SECTIONS) {
        assert.ok(prompt.includes(section), `섹션 누락: ${section}`);
    }

    assert.ok(prompt.includes('job-7-2.md'));
    assert.ok(prompt.includes('HANDOVER_DONE'));
});

test('교체 세션 프롬프트는 원 지시문과 인수인계를 함께 싣는다', () => {
    const prompt = resumePrompt('원래 할 일', '## 작업 목표\n...');

    assert.ok(prompt.includes('원래 할 일'));
    assert.ok(prompt.includes('## 작업 목표'));
    // 다음 세션이 어디서 이어갈지 명시해야 한다.
    assert.ok(prompt.includes('다음 세션이 바로 실행할 첫 작업'));
});
