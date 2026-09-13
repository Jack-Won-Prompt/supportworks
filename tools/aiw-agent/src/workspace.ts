import { access } from 'node:fs/promises';
import { join } from 'node:path';
import { simpleGit } from 'simple-git';

/**
 * 매핑된 폴더가 지시를 받을 수 있는 상태인지 보고, 필요하면 정리한다.
 *
 * 점검은 원래 check-setup.ps1 에만 있었다. 그것은 데몬이 뜰 때 한 번 도는 것이라,
 * 사람이 폴더를 정리해도 화면은 몇 시간 전 상태를 계속 보여 줬다 — "고쳤는데 왜
 * 아직 막혀 있지?" 가 된다. 같은 판정을 데몬 안에 두어 화면에서 다시 점검을
 * 누를 수 있게 한다. 두 곳의 판정 기준은 같아야 하므로 문구까지 맞춰 둔다.
 *
 * 정리(cleanup)는 **아무것도 버리지 않는다.** 남아 있던 변경을 보관 브랜치에
 * 커밋으로 옮기고 작업 폴더만 깨끗하게 만든다. 화면의 목록만 보고는 그것이
 * 누구의 작업인지 알 수 없기 때문이다 — 실제로 "미정리 변경 6건" 이 중단된
 * 작업이 만들던 기능이었던 적이 있다.
 */

export type SetupStatus = 'ok' | 'path_missing' | 'not_git_repo' | 'branch_missing' | 'dirty_tree' | 'unknown';

export interface MappingSpec {
    project_id: number;
    local_path: string;
    default_branch: string | null;
}

export interface SetupResult {
    status: SetupStatus;
    message: string | null;
}

/** git 이 인덱스 잠금을 잡지 않게 한다. 점검은 읽기만 하므로 데몬의 커밋과 부딪힐 이유가 없다. */
const READ_ONLY = ['--no-optional-locks'];

async function exists(path: string): Promise<boolean> {
    try {
        await access(path);

        return true;
    } catch {
        return false;
    }
}

export async function checkSetup(mapping: MappingSpec): Promise<SetupResult> {
    const path = mapping.local_path;

    if (!path || !(await exists(path))) {
        return {
            status: 'path_missing',
            message: `폴더가 없습니다: ${path} — 매핑의 소스 경로를 고치거나 그 위치에 저장소를 두세요.`,
        };
    }

    if (!(await exists(join(path, '.git')))) {
        return {
            status: 'not_git_repo',
            message: `git 저장소가 아닙니다: ${path} — 브랜치 분리·커밋·배포가 모두 git 위에서 돕니다.`
                + ' git init 과 원격 연결이 필요합니다.',
        };
    }

    const git = simpleGit(path);

    try {
        const want = mapping.default_branch;

        if (want) {
            const local = await git.branchLocal();

            if (!local.all.includes(want)) {
                return {
                    status: 'branch_missing',
                    message: `기본 브랜치 '${want}' 가 없습니다. 현재 '${local.current}',`
                        + ` 있는 브랜치: ${local.all.join(', ')}`,
                };
            }
        }

        const status = await git.raw([...READ_ONLY, 'status', '--porcelain']);
        const dirty = status.split('\n').map((l) => l.trim()).filter(Boolean);

        if (dirty.length > 0) {
            const sample = dirty.slice(0, 5).join(' / ');

            return {
                status: 'dirty_tree',
                message: `커밋되지 않은 변경 ${dirty.length}건: ${sample}`
                    + ' — 커밋하거나 .gitignore 로 정리해야 지시를 시작할 수 있습니다.',
            };
        }

        return { status: 'ok', message: null };
    } catch (error) {
        return { status: 'unknown', message: `확인 중 오류: ${String((error as Error).message ?? error)}` };
    }
}

export interface CleanupResult {
    /** 화면에 그대로 보여 줄 문구. */
    message: string;
    /** 정리 후 다시 점검한 결과. 화면은 이것으로 배지를 갱신한다. */
    setup: SetupResult;
    /** 보관한 브랜치. 옮길 것이 없었으면 null. */
    branch: string | null;
}

/**
 * 미커밋 변경을 보관 브랜치로 옮기고 작업 폴더를 깨끗하게 만든다.
 *
 * 되돌릴 수 없는 일은 하지 않는다 — reset --hard 도 clean 도 쓰지 않는다.
 * 보관 브랜치 이름에 시각을 넣어, 여러 번 눌러도 앞선 보관을 덮지 않게 한다.
 */
export async function cleanupWorkspace(mapping: MappingSpec): Promise<CleanupResult> {
    const setupBefore = await checkSetup(mapping);

    if (setupBefore.status === 'path_missing' || setupBefore.status === 'not_git_repo') {
        // 정리로 풀 수 있는 문제가 아니다. 사람이 경로를 고쳐야 한다.
        return { message: setupBefore.message ?? '정리할 수 없는 상태입니다.', setup: setupBefore, branch: null };
    }

    const git = simpleGit(mapping.local_path);

    try {
        const before = (await git.branchLocal()).current;
        const status = await git.raw([...READ_ONLY, 'status', '--porcelain']);
        const files = status.split('\n').map((l) => l.trim()).filter(Boolean);

        if (files.length === 0) {
            return {
                message: '정리할 변경이 없었습니다. 지금 상태 그대로 지시를 시작할 수 있습니다.',
                setup: await checkSetup(mapping),
                branch: null,
            };
        }

        const stamp = new Date().toISOString().replace(/[-:T]/g, '').slice(0, 14);
        const branch = `aiw/wip-${stamp}`;

        await git.checkoutLocalBranch(branch);
        await git.add(['-A']);

        const commit = await git.commit(`wip(aiw): 작업 폴더 정리 — ${before} 의 미커밋 변경 보관`);

        // 여기서 작업 폴더가 원래 상태로 돌아온다. 변경은 보관 브랜치에 남아 있다.
        await git.checkout(mapping.default_branch ?? before);

        const sha = commit.commit ? ` (커밋 \`${commit.commit.slice(0, 8)}\`)` : '';

        return {
            message: `미커밋 변경 ${files.length}건을 \`${branch}\` 브랜치에 보관하고 작업 폴더를 정리했습니다${sha}.`
                + ' 내용을 확인하려면 작업 PC 에서 그 브랜치를 체크아웃하세요.',
            setup: await checkSetup(mapping),
            branch,
        };
    } catch (error) {
        const reason = String((error as Error).message ?? error);

        return {
            message: `정리하지 못했습니다: ${reason} 작업 PC 에서 직접 확인해 주세요.`,
            setup: await checkSetup(mapping),
            branch: null,
        };
    }
}
