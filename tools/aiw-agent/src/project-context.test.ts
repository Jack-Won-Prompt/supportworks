import './test-env.js';
import assert from 'node:assert/strict';
import { mkdtemp, mkdir, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import { loadProjectRules } from './project-context.js';

async function scratch(): Promise<string> {
    return mkdtemp(join(tmpdir(), 'aiw-rules-'));
}

test('CLAUDE.md 가 없으면 null 이다', async () => {
    assert.equal(await loadProjectRules(await scratch()), null);
});

test('CLAUDE.md 를 읽어 출처와 함께 싣는다', async () => {
    const root = await scratch();

    await writeFile(join(root, 'CLAUDE.md'), '들여쓰기는 4칸', 'utf8');

    const rules = await loadProjectRules(root);

    assert.match(String(rules), /들여쓰기는 4칸/);
    assert.match(String(rules), /이 저장소의 규칙 \(CLAUDE\.md\)/, '어디서 온 규칙인지 밝혀야 한다.');
    assert.match(String(rules), /충돌하면 지시를 우선/, '규칙과 지시의 우선순위를 명시해야 한다.');
});

test('.claude/CLAUDE.md 도 후보다', async () => {
    const root = await scratch();

    await mkdir(join(root, '.claude'), { recursive: true });
    await writeFile(join(root, '.claude', 'CLAUDE.md'), '숨은 규칙', 'utf8');

    const rules = await loadProjectRules(root);

    assert.match(String(rules), /숨은 규칙/);
    assert.match(String(rules), /\.claude\/CLAUDE\.md/);
});

test('루트 CLAUDE.md 가 .claude 판본보다 우선한다', async () => {
    const root = await scratch();

    await mkdir(join(root, '.claude'), { recursive: true });
    await writeFile(join(root, 'CLAUDE.md'), '루트 규칙', 'utf8');
    await writeFile(join(root, '.claude', 'CLAUDE.md'), '숨은 규칙', 'utf8');

    const rules = await loadProjectRules(root);

    assert.match(String(rules), /루트 규칙/);
    assert.equal(String(rules).includes('숨은 규칙'), false, '먼저 찾은 하나만 쓴다.');
});

test('빈 파일은 다음 후보로 넘어간다', async () => {
    const root = await scratch();

    await mkdir(join(root, '.claude'), { recursive: true });
    await writeFile(join(root, 'CLAUDE.md'), '   \n\n  ', 'utf8');
    await writeFile(join(root, '.claude', 'CLAUDE.md'), '진짜 규칙', 'utf8');

    assert.match(String(await loadProjectRules(root)), /진짜 규칙/);
});

test('모든 후보가 비어 있으면 null 이다', async () => {
    const root = await scratch();

    await writeFile(join(root, 'CLAUDE.md'), '\n\n', 'utf8');

    assert.equal(await loadProjectRules(root), null);
});

test('긴 규칙 문서는 잘라서 싣고 잘렸음을 알린다', async () => {
    const root = await scratch();
    const long = 'X'.repeat(20 * 1024);

    await writeFile(join(root, 'CLAUDE.md'), long, 'utf8');

    const rules = String(await loadProjectRules(root));

    // 규칙 문서 하나가 컨텍스트를 다 먹어 인수인계를 유발하면 안 된다.
    assert.ok(rules.length < 20 * 1024, `너무 길다: ${rules.length}`);
    assert.match(rules, /앞부분만 전달됨/);
});
