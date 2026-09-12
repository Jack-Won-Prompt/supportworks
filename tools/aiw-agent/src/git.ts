import { simpleGit, SimpleGit } from 'simple-git';
import { JobSetupError } from './errors.js';

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
            const dirty = [...new Set([...status.files.map((f) => f.path), ...status.not_added])];
            const sample = dirty.slice(0, 5).join(', ');

            throw new JobSetupError(
                'dirty_tree',
                `브랜치 분리를 켜면 작업 폴더가 깨끗해야 합니다. 지금 정리되지 않은 항목이 `
                + `${status.files.length}건 있습니다: ${sample}`
                + (status.files.length > 5 ? ' 외' : '')
                + '. 커밋하거나 .gitignore 에 넣어 정리하세요. '
                + '또는 새 지시를 등록할 때 "별도 브랜치에서 작업" 체크를 해제하면 현재 브랜치에서 바로 진행합니다.',
                { files: dirty.slice(0, 30), count: status.files.length },
            );
        }

        if (defaultBranch) {
            // 없는 브랜치로 checkout 하면 raw git 오류가 그대로 화면에 나간다
            // ("pathspec 'master' did not match ..."). 설정이 틀렸다는 것을 알아볼 수 없다.
            const local = await this.git.branchLocal();

            if (!local.all.includes(defaultBranch)) {
                throw new JobSetupError(
                    'missing_branch',
                    `설정된 기본 브랜치 '${defaultBranch}' 가 이 저장소에 없습니다. `
                    + `있는 브랜치: ${local.all.join(', ')}. `
                    + '관리자 › 담당자 화면에서 매핑의 기본 브랜치를 고치거나 비워 두세요'
                    + `(비우면 현재 브랜치 '${local.current}' 에서 분기합니다).`,
                    { configured: defaultBranch, available: local.all, current: local.current },
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

            return;
        }

        try {
            await this.git.checkoutLocalBranch(branch);
        } catch (error) {
            // 같은 job 의 브랜치가 이미 있는 것은 오류가 아니다. 앞선 시도가 남겼거나,
            // 목록을 읽은 뒤 만들어졌을 수 있다. 그대로 이어서 쓴다.
            if (!/already exists/i.test(String(error))) {
                throw error;
            }

            await this.git.checkout(branch);
        }
    }

    /**
     * 작업 브랜치를 기본 브랜치에 합치고 원격으로 올린다.
     *
     * 이 메서드는 Claude 가 아니라 데몬이 직접 호출한다. 샌드박스는 Claude 의
     * 툴 호출을 통제하는 것이고, 사람이 화면에서 누른 버튼은 다른 신뢰 경로다.
     *
     * 실패하면 저장소를 원래 자리로 되돌린다 — 충돌 상태로 남겨 두면 다음 작업이
     * 시작조차 못 하고, 사람이 그 PC 앞에 가야만 풀 수 있다.
     */
    /**
     * 인덱스를 쓰지 못해 실패했을 때만 다시 시도한다.
     *
     * 작업 폴더는 사람도 쓰는 곳이다 — 편집기가 상태를 읽거나, 백신이 파일을
     * 훑거나, 누군가 명령을 하나 돌리는 것만으로 인덱스가 잠긴다. 그 순간에
     * 부딪혔다고 커밋·푸시를 실패로 끝내면, 사람은 버튼을 다시 눌러야 하는
     * 이유를 알 수 없다.
     *
     * 같은 원인인데 git 이 내는 말이 여러 가지다. 'index.lock' 만 보고 있었더니
     * 'could not write index / stash failed' 로 온 경합을 놓쳐 실패로 남았다.
     * 실제로 그렇게 배포가 멈췄다.
     */
    /**
     * 커밋·머지·푸시는 여러 단계가 이어진 일이라 통째로 다시 돌릴 수 없다.
     * (실제로 그렇게 했다가 이미 커밋된 상태에서 재실행되어
     *  "cannot lock ref 'HEAD'" 로 더 나빠졌다.)
     *
     * 대신 실패했다고 보고하기 전에 **결과를 확인한다.** 인덱스 경합처럼
     * 잠깐 부딪힌 경우, 앞 단계는 이미 끝나 있고 반영 자체는 완료된 채로
     * 뒤 단계만 오류를 낸 것일 수 있다. 그때 실패로 적으면 자동 배포가
     * "푸시가 실패해 배포하지 않습니다" 로 멈춘다 — 실제로 그렇게 멈췄다.
     */
    async publish(options: {
        sourceBranch: string;
        targetBranch: string;
        commitMessage: string;
        onProgress?: (line: string) => void;
    }): Promise<{ commitSha: string | null; output: string }> {
        try {
            return await this.publishOnce(options);
        } catch (error) {
            const landed = await this.alreadyLanded(options).catch(() => null);

            if (!landed) {
                throw error;
            }

            return {
                commitSha: landed,
                output: [
                    `진행 중 오류가 있었지만 확인 결과 반영은 완료되었습니다: ${landed}`,
                    `오류: ${String((error as Error)?.message ?? error).slice(0, 300)}`,
                ].join('\n'),
            };
        }
    }

    /**
     * 작업 브랜치가 기본 브랜치에 합쳐졌고 원격까지 같은가.
     *
     * @return 반영됐으면 기본 브랜치의 커밋, 아니면 null
     */
    private async alreadyLanded(options: {
        sourceBranch: string;
        targetBranch: string;
    }): Promise<string | null> {
        const local = await this.git.branchLocal();
        const target = options.targetBranch === 'HEAD'
            ? await this.defaultBranch(local.all)
            : options.targetBranch;

        if (!local.all.includes(target) || !local.all.includes(options.sourceBranch)) {
            return null;
        }

        // 작업 브랜치가 기본 브랜치 안에 들어 있는가.
        try {
            await this.git.raw(['merge-base', '--is-ancestor', options.sourceBranch, target]);
        } catch {
            return null;
        }

        const head = (await this.git.revparse([target])).trim();

        // 원격까지 같아야 "올렸다" 고 말할 수 있다. 로컬만 합쳐 두고 성공이라
        // 적으면 서버는 옛 코드를 받아 가면서 배포는 성공한 것처럼 보인다.
        try {
            await this.git.fetch('origin', target);
        } catch {
            return null;
        }

        const remote = (await this.git.revparse([`origin/${target}`])).trim();

        return head === remote ? head : null;
    }

    private async publishOnce(options: {
        sourceBranch: string;
        targetBranch: string;
        commitMessage: string;
        onProgress?: (line: string) => void;
    }): Promise<{ commitSha: string | null; output: string }> {
        const out: string[] = [];
        const say = (line: string) => {
            out.push(line);
            options.onProgress?.(line);
        };

        const local = await this.git.branchLocal();

        if (!local.all.includes(options.sourceBranch)) {
            throw new Error(`작업 브랜치 '${options.sourceBranch}' 가 없습니다. 이미 정리되었을 수 있습니다.`);
        }

        // HEAD 는 "기본 브랜치를 지정하지 않았다"는 뜻. 지금 기본 브랜치를 쓴다.
        const target = options.targetBranch === 'HEAD'
            ? await this.defaultBranch(local.all)
            : options.targetBranch;

        if (!local.all.includes(target)) {
            throw new Error(`기본 브랜치 '${target}' 가 없습니다. 있는 브랜치: ${local.all.join(', ')}`);
        }

        if (target === options.sourceBranch) {
            throw new Error('작업 브랜치와 기본 브랜치가 같습니다.');
        }

        let commitSha: string | null = null;

        // 1) 작업 브랜치의 변경을 커밋한다.
        await this.git.checkout(options.sourceBranch);
        const status = await this.git.status();

        if (status.isClean()) {
            say('커밋할 변경이 없습니다. 기존 커밋만 합칩니다.');
        } else {
            await this.git.add(['-A']);
            const commit = await this.git.commit(options.commitMessage);
            commitSha = commit.commit || null;
            say(`커밋: ${commitSha ?? '(없음)'} — ${status.files.length}개 파일`);
        }

        // 2) 기본 브랜치로 옮겨 최신화한다.
        await this.git.checkout(target);
        say(`기본 브랜치로 전환: ${target}`);

        try {
            await this.git.pull();
            say('원격 최신화 완료');
        } catch (error) {
            // 원격이 없거나 접근 불가일 수 있다. 머지는 계속한다.
            say(`원격 최신화 건너뜀: ${String(error).slice(0, 200)}`);
        }

        // 3) 합친다. 충돌하면 되돌리고 사람에게 넘긴다.
        try {
            await this.git.merge(['--no-ff', options.sourceBranch, '-m', options.commitMessage]);
            say(`머지 완료: ${options.sourceBranch} → ${target}`);
        } catch (error) {
            await this.git.raw(['merge', '--abort']).catch(() => undefined);
            await this.git.checkout(options.sourceBranch).catch(() => undefined);

            throw new Error(
                `머지 충돌로 중단했습니다(변경은 그대로 있습니다). ${String(error).slice(0, 500)}`,
            );
        }

        // 4) 올린다.
        try {
            await this.git.push('origin', target);
            say(`푸시 완료: origin/${target}`);
        } catch (error) {
            throw new Error(
                `푸시에 실패했습니다(머지는 로컬에 남아 있습니다). ${String(error).slice(0, 500)}`,
            );
        }

        return { commitSha, output: out.join('\n') };
    }

    /** main / master 중 실제로 있는 것. 둘 다 없으면 현재 브랜치. */
    private async defaultBranch(all: string[]): Promise<string> {
        for (const name of ['main', 'master']) {
            if (all.includes(name)) {
                return name;
            }
        }

        return (await this.git.branchLocal()).current;
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
