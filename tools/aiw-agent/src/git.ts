import { simpleGit, SimpleGit } from 'simple-git';

const DIFF_MAX_BYTES = 500 * 1024;

export class GitWorkspace {
    private readonly git: SimpleGit;

    constructor(private readonly root: string) {
        this.git = simpleGit(root);
    }

    async isRepo(): Promise<boolean> {
        return this.git.checkIsRepo();
    }

    /**
     * 작업 브랜치를 준비한다.
     *
     * dirty 면 진행하지 않는다 — 남의 미커밋 변경 위에 AI 가 작업하면 diff 가
     * 뒤섞여 무엇이 이 작업의 결과인지 구분할 수 없다.
     */
    async prepareBranch(branch: string, defaultBranch: string | null): Promise<void> {
        const status = await this.git.status();

        if (!status.isClean()) {
            throw new Error(
                `작업 폴더에 커밋되지 않은 변경이 있습니다(${status.files.length}건). ` +
                '정리한 뒤 다시 시도하세요.',
            );
        }

        if (defaultBranch) {
            await this.git.checkout(defaultBranch);

            try {
                await this.git.pull();
            } catch {
                // 원격이 없거나 접근 불가일 수 있다. 브랜치 생성은 계속한다.
            }
        }

        const branches = await this.git.branchLocal();

        if (branches.all.includes(branch)) {
            await this.git.checkout(branch);
        } else {
            await this.git.checkoutLocalBranch(branch);
        }
    }

    async changedFiles(): Promise<string[]> {
        const status = await this.git.status();

        return [...new Set([...status.files.map((f) => f.path), ...status.not_added])];
    }

    /** 커밋되지 않은 변경 + 기준 브랜치 대비 커밋을 함께 본다. 500KB 초과 시 절단. */
    async collectDiff(defaultBranch: string | null): Promise<string> {
        const parts: string[] = [];

        try {
            parts.push(await this.git.diff());
            parts.push(await this.git.diff(['--staged']));

            if (defaultBranch) {
                parts.push(await this.git.diff([`${defaultBranch}...HEAD`]));
            }
        } catch {
            // diff 수집 실패가 작업 완료 보고를 막지 않는다.
        }

        const joined = parts.filter(Boolean).join('\n');

        return joined.length > DIFF_MAX_BYTES
            ? joined.slice(0, DIFF_MAX_BYTES) + '\n\n... (500KB 초과로 절단됨)'
            : joined;
    }
}
