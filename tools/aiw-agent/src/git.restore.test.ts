import './test-env.js';
import assert from 'node:assert/strict';
import { mkdtemp, readFile, writeFile, rm } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import { simpleGit } from 'simple-git';
import { GitWorkspace } from './git.js';

/**
 * 실제 저장소를 만들어 시험한다. 중단 복구는 git 의 실제 동작(체크아웃이 작업
 * 폴더를 되돌리는 것)에 기대고 있어, 흉내 낸 객체로는 아무것도 증명하지 못한다.
 */
async function repo(): Promise<string> {
    const root = await mkdtemp(join(tmpdir(), 'aiw-restore-'));
    const git = simpleGit(root);

    await git.init(['--initial-branch=master']);
    await git.addConfig('user.email', 'test@example.com');
    await git.addConfig('user.name', 'test');
    await git.addConfig('commit.gpgsign', 'false');
    // Windows 의 전역 autocrlf 가 체크아웃에서 줄바꿈을 바꾸면 내용 비교가 흔들린다.
    // 이 테스트가 보려는 것은 줄바꿈이 아니라 복구다.
    await git.addConfig('core.autocrlf', 'false');

    await writeFile(join(root, 'app.txt'), '원본\n', 'utf8');
    await git.add(['-A']);
    await git.commit('첫 커밋');

    return root;
}

test('중단되면 작업 폴더가 수정 이전으로 돌아가고 내용은 브랜치에 남는다', async () => {
    const root = await repo();
    const ws = new GitWorkspace(root);
    const branch = 'aiw/job-1';

    const base = await ws.prepareBranch(branch, 'master');

    // AI 가 고치는 중이었다 — 기존 파일 수정 + 새 파일 생성.
    await writeFile(join(root, 'app.txt'), '고치다 만 내용\n', 'utf8');
    await writeFile(join(root, 'new.txt'), '새로 만든 파일\n', 'utf8');

    const result = await ws.restoreAfterAbort({ branch, base, commitMessage: 'wip: 중단' });

    assert.equal(result.kind, 'restored');
    assert.equal(result.kind === 'restored' && result.baseBranch, 'master');
    assert.deepEqual(
        result.kind === 'restored' ? [...result.files].sort() : [],
        ['app.txt', 'new.txt'],
    );

    // 작업 폴더는 수정 이전 상태다.
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '원본\n');
    assert.equal(existsSync(join(root, 'new.txt')), false);

    const git = simpleGit(root);

    assert.equal((await git.branchLocal()).current, 'master');
    assert.ok((await git.status()).isClean(), '다음 지시가 시작될 수 있게 깨끗해야 한다');

    // 버리지 않았다. 작업 브랜치에 그대로 있다.
    await git.checkout(branch);
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '고치다 만 내용\n');
    assert.equal(await readFile(join(root, 'new.txt'), 'utf8'), '새로 만든 파일\n');

    await rm(root, { recursive: true, force: true });
});

test('AI 가 스스로 커밋해 둔 것도 브랜치에 남고 폴더는 되돌아간다', async () => {
    const root = await repo();
    const ws = new GitWorkspace(root);
    const branch = 'aiw/job-2';

    const base = await ws.prepareBranch(branch, 'master');

    const git = simpleGit(root);

    await writeFile(join(root, 'app.txt'), '커밋된 변경\n', 'utf8');
    await git.add(['-A']);
    await git.commit('AI 가 직접 커밋');

    const result = await ws.restoreAfterAbort({ branch, base, commitMessage: 'wip: 중단' });

    // 미커밋 변경은 없으니 보관 커밋은 만들지 않지만, 폴더는 되돌려야 한다.
    assert.equal(result.kind, 'restored');
    assert.equal(result.kind === 'restored' && result.commitSha, null);
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '원본\n');
    assert.equal((await git.branchLocal()).current, 'master');

    await git.checkout(branch);
    assert.equal(await readFile(join(root, 'app.txt'), 'utf8'), '커밋된 변경\n');

    await rm(root, { recursive: true, force: true });
});

test('고친 것이 없으면 되돌릴 것도 없다', async () => {
    const root = await repo();
    const ws = new GitWorkspace(root);
    const branch = 'aiw/job-3';

    const base = await ws.prepareBranch(branch, 'master');
    const result = await ws.restoreAfterAbort({ branch, base, commitMessage: 'wip: 중단' });

    assert.equal(result.kind, 'nothing');
    assert.equal((await simpleGit(root).branchLocal()).current, 'master');

    await rm(root, { recursive: true, force: true });
});

test('사람이 다른 브랜치로 옮겨 놨으면 건드리지 않는다', async () => {
    const root = await repo();
    const ws = new GitWorkspace(root);
    const branch = 'aiw/job-4';

    const base = await ws.prepareBranch(branch, 'master');

    await writeFile(join(root, 'app.txt'), '작업 중\n', 'utf8');

    const git = simpleGit(root);

    await git.add(['-A']);
    await git.commit('작업 중 커밋');
    await git.checkout('master');

    // 사람이 master 에서 자기 작업을 하고 있다. 여기서 되돌리면 그것을 지운다.
    await writeFile(join(root, 'mine.txt'), '내 작업\n', 'utf8');

    const result = await ws.restoreAfterAbort({ branch, base, commitMessage: 'wip: 중단' });

    assert.equal(result.kind, 'skipped');
    assert.equal(await readFile(join(root, 'mine.txt'), 'utf8'), '내 작업\n');

    await rm(root, { recursive: true, force: true });
});

test('.gitignore 에 걸린 파일은 되돌리지 않는다', async () => {
    const root = await repo();
    const git = simpleGit(root);

    await writeFile(join(root, '.gitignore'), 'secret.env\n', 'utf8');
    await git.add(['-A']);
    await git.commit('ignore 추가');

    const ws = new GitWorkspace(root);
    const branch = 'aiw/job-5';
    const base = await ws.prepareBranch(branch, 'master');

    // 저장소가 무시하기로 한 파일은 애초에 이 작업의 산출물로 볼 수 없다.
    await writeFile(join(root, 'secret.env'), 'KEY=1\n', 'utf8');
    await writeFile(join(root, 'app.txt'), '고치다 만 내용\n', 'utf8');

    const result = await ws.restoreAfterAbort({ branch, base, commitMessage: 'wip: 중단' });

    assert.equal(result.kind, 'restored');
    assert.deepEqual(result.kind === 'restored' ? result.files : [], ['app.txt']);
    assert.equal(await readFile(join(root, 'secret.env'), 'utf8'), 'KEY=1\n');

    await rm(root, { recursive: true, force: true });
});
