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
            // 무엇이 걸렸는지 보여 준다. "정리하세요" 만으로는 어느 파일인지 알 수 없어
            // 같은 실패를 반복하게 된다.
            const sample = [...new Set([...status.files.map((f) => f.path), ...status.not_added])]
                .slice(0, 5)
                .join(', ');

            throw new Error(
                `브랜치 분리를 켜면 작업 폴더가 깨끗해야 합니다. 지금 정리되지 않은 항목이 `
                + `${status.files.length}건 있습니다: ${sample}`
                + (status.files.length > 5 ? ' 외' : '')
                + '. 커밋하거나 .gitignore 에 넣어 정리하세요. '
                + '또는 새 지시를 등록할 때 "별도 브랜치에서 작업" 체크를 해제하면 현재 브랜치에서 바로 진행합니다.',
            );
        }

        if (defaultBranch) {
            // 없는 브랜치로 checkout 하면 raw git 오류가 그대로 화면에 나간다
            // ("pathspec 'master' did not match ..."). 설정이 틀렸다는 것을 알아볼 수 없다.
            const local = await this.git.branchLocal();

            if (!local.all.includes(defaultBranch)) {
                throw new Error(
                    `설정된 기본 브랜치 '${defaultBranch}' 가 이 저장소에 없습니다. `
                    + `있는 브랜치: ${local.all.join(', ')}. `
                    + '관리자 › 담당자 화면에서 매핑의 기본 브랜치를 고치거나 비워 두세요'
                    + `(비우면 현재 브랜치 '${local.current}' 에서 분기합니다).`,
                );
            }

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
