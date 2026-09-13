import './test-env.js';
import assert from 'node:assert/strict';
import { mkdtemp, mkdir, readFile, rm, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import { simpleGit } from 'simple-git';
import { checkSetup, cleanupWorkspace } from './workspace.js';

async function repo(): Promise<string> {
    const root = await mkdtemp(join(tmpdir(), 'aiw-ws-'));
    const git = simpleGit(root);

    await git.init(['--initial-branch=main']);
    await git.addConfig('user.email', 'test@example.com');
    await git.addConfig('user.name', 'test');
    await git.addConfig('commit.gpgsign', 'false');
    await git.addConfig('core.autocrlf', 'false');

    await writeFile(join(root, 'app.txt'), '원본\n', 'utf8');
    await git.add(['-A']);
    await git.commit('첫 커밋');

    return root;
}

const spec = (root: string, branch: string | null = 'main') => ({
    project_id: 1, local_path: root, default_branch: branch,
});

// ── 점검 ────────────────────────────────────────────────────────────────────

test('깨끗하면 ok', async () => {
    const root = await repo();

    assert.deepEqual(await checkSetup(spec(root)), { status: 'ok', message: null });

    await rm(root, { recursive: true, force: true });
});

test('폴더가 없으면 path_missing', async () => {
    const r = await checkSetup(spec(join(tmpdir(), 'aiw-없는폴더-xyz')));

    assert.equal(r.status, 'path_missing');
});

test('git 저장소가 아니면 not_git_repo', async () => {
    const root = await mkdtemp(join(tmpdir(), 'aiw-plain-'));

    assert.equal((await checkSetup(spec(root))).status, 'not_git_repo');

    await rm(root, { recursive: true, force: true });
});

test('기본 브랜치가 없으면 branch_missing', async () => {
    const root = await repo();
    const r = await checkSetup(spec(root, 'master'));

    assert.equal(r.status, 'branch_missing');
    assert.match(r.message ?? '', /master/);

    await rm(root, { recursive: true, force: true });
});

test('미커밋 변경이 있으면 dirty_tree 이고 건수와 파일을 알려 준다', async () => {
    const root = await repo();

    await writeFile(join(root, 'app.txt'), '고침\n', 'utf8');
    await writeFile(join(root, 'new.txt'), '새 파일\n', 'utf8');

    const r = await checkSetup(spec(root));

    assert.equal(r.status, 'dirty_tree');
    assert.match(r.message ?? '', /2건/);
    assert.match(r.message ?? '', /app\.txt/);

    await rm(root, { recursive: true, force: true });
});

// ── 정리 ────────────────────────────────────────────────────────────────────

test('정리하면 보관 브랜치로 옮기고 폴더가 깨끗해진다', async () => {
    const root = await repo();

    await writeFile(join(root, 'app.txt'), '작업 중\n', 'utf8');
    await mkdir(join(root, 'docs'), { recursive: true });
    await writeFile(join(root, 'docs', 'memo.md'), '메모\n', 'utf8');

    const result = await cleanupWorkspace(spec(root));

    assert.equal(result.setup.status, 'ok');
    assert.ok(result.branch?.startsWith('aiw/wip-'), result.branch ?? '(브랜치 없음)');

    const git = simpleGit(root);

    assert.equal((await git.branchLocal()).current, 'main');
    assert.ok((await git.status()).isClean());
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '원본\n');
    assert.equal(existsSync(join(root, 'docs', 'memo.md')), false);

    // 버리지 않았다. 추적되지 않던 폴더까지 보관 브랜치에 있다.
    await git.checkout(result.branch!);
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '작업 중\n');
    assert.equal(await readFile(join(root, 'docs', 'memo.md'), 'utf8'), '메모\n');

    await rm(root, { recursive: true, force: true });
});

test('정리할 것이 없으면 아무 브랜치도 만들지 않는다', async () => {
    const root = await repo();
    const result = await cleanupWorkspace(spec(root));

    assert.equal(result.branch, null);
    assert.equal(result.setup.status, 'ok');
    assert.match(result.message, /정리할 변경이 없었습니다/);

    const branches = await simpleGit(root).branchLocal();

    assert.deepEqual(branches.all, ['main']);

    await rm(root, { recursive: true, force: true });
});

test('두 번 정리해도 앞선 보관을 덮지 않는다', async () => {
    const root = await repo();
    const git = simpleGit(root);

    await writeFile(join(root, 'app.txt'), '첫 번째\n', 'utf8');
    const first = await cleanupWorkspace(spec(root));

    await writeFile(join(root, 'app.txt'), '두 번째\n', 'utf8');
    const second = await cleanupWorkspace(spec(root));

    assert.ok(first.branch && second.branch);
    // 같은 초에 두 번 눌리면 이름이 겹칠 수 있다 — 그때는 두 번째가 실패로 보고되고
    // 폴더는 그대로 남는다. 어느 쪽이든 첫 보관은 살아 있어야 한다.
    await git.checkout(first.branch!);
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '첫 번째\n');

    await rm(root, { recursive: true, force: true });
});

test('git 저장소가 아니면 정리하지 않고 이유를 돌려준다', async () => {
    const root = await mkdtemp(join(tmpdir(), 'aiw-plain2-'));
    const result = await cleanupWorkspace(spec(root));

    assert.equal(result.branch, null);
    assert.equal(result.setup.status, 'not_git_repo');

    await rm(root, { recursive: true, force: true });
});
