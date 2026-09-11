import { realpath } from 'node:fs/promises';
import { isAbsolute, relative, resolve } from 'node:path';
import { findBlockedRule } from './sandbox.rules.js';

export interface SandboxVerdict {
    allowed: boolean;
    reason?: string;
    ruleId?: string;
}

const ALLOW: SandboxVerdict = { allowed: true };

/**
 * 경로·명령을 코드로 강제하는 샌드박스.
 *
 * v2 는 "never modify files outside this repository" 를 프롬프트 문장으로만 두었다.
 * LLM 의 준수는 보장되지 않으므로 데몬이 막는다. 프롬프트의 같은 문장은 Claude 가
 * 헛수고하지 않게 하는 안내일 뿐이고, 실제 통제는 여기다.
 */
export class Sandbox {
    private constructor(readonly root: string) {}

    /** job 시작 시 realpath 로 ROOT 를 고정한다(심볼릭 링크 이탈 차단의 기준점). */
    static async create(localPath: string): Promise<Sandbox> {
        const root = await realpath(localPath);

        return new Sandbox(root);
    }

    /**
     * 대상 경로가 ROOT 하위인지 검사한다.
     *
     * 존재하지 않는 파일(새로 만들 파일)은 realpath 가 실패하므로, 부모를 거슬러
     * 올라가며 존재하는 조상을 realpath 한 뒤 나머지를 이어붙여 판정한다.
     * 이렇게 하지 않으면 "새 파일 쓰기"가 전부 막힌다.
     */
    async isInside(target: string): Promise<boolean> {
        const absolute = isAbsolute(target) ? target : resolve(this.root, target);
        const resolved = await this.resolveExistingAncestor(absolute);
        const rel = relative(this.root, resolved);

        return rel === '' || (!rel.startsWith('..') && !isAbsolute(rel));
    }

    private async resolveExistingAncestor(absolute: string): Promise<string> {
        let current = absolute;
        const tail: string[] = [];

        // 최대 깊이를 제한해 비정상 경로에서 무한 루프에 빠지지 않게 한다.
        for (let i = 0; i < 64; i++) {
            try {
                const real = await realpath(current);

                return tail.length ? resolve(real, ...tail.reverse()) : real;
            } catch {
                const parent = resolve(current, '..');

                if (parent === current) {
                    return absolute;   // 루트까지 갔는데 없다 — 원본으로 판정
                }

                tail.push(relative(parent, current));
                current = parent;
            }
        }

        return absolute;
    }

    /** 파일 계열 툴(Read/Edit/Write/Glob/Grep)의 경로 인자 검사. */
    async checkPaths(toolName: string, input: unknown): Promise<SandboxVerdict> {
        for (const candidate of collectPaths(input)) {
            if (!(await this.isInside(candidate))) {
                return {
                    allowed: false,
                    ruleId: 'path-escape',
                    reason:
                        `작업 폴더(${this.root}) 밖의 경로에는 접근할 수 없습니다: ${candidate}. ` +
                        '이 지시는 해당 저장소 안에서만 수행할 수 있습니다.',
                };
            }
        }

        return ALLOW;
    }

    /** Bash 명령 문자열 검사. 승인 여부와 무관하게 여기서 걸리면 무조건 거부다. */
    checkCommand(command: string): SandboxVerdict {
        const rule = findBlockedRule(command);

        if (rule) {
            return { allowed: false, ruleId: rule.id, reason: rule.reason };
        }

        return ALLOW;
    }

    /**
     * 툴 호출 전체 검사. canUseTool 에서 서버에 묻기 전에 가장 먼저 통과해야 한다.
     *
     * 명령 검사는 툴 '이름'이 아니라 입력 '모양'으로 판단한다. Claude Code 는
     * 환경에 따라 Bash 외에도 셸 실행 툴을 노출한다(Windows 의 PowerShell 등).
     * 이름으로 분기하면 우리가 모르는 셸 툴이 검사를 통째로 건너뛴다 — 실측으로
     * PowerShell 이 git 명령을 실행하는 것을 확인했다.
     */
    async check(toolName: string, input: unknown): Promise<SandboxVerdict> {
        const command = (input as { command?: unknown })?.command;

        if (typeof command === 'string' && command.trim() !== '') {
            const verdict = this.checkCommand(command);

            if (!verdict.allowed) {
                return verdict;
            }
        }

        return this.checkPaths(toolName, input);
    }
}

/** 툴 입력에서 경로처럼 보이는 문자열을 모은다. 키 이름 기준이라 과탐이 적다. */
function collectPaths(input: unknown): string[] {
    const found: string[] = [];
    const pathKeys = /^(?:file_path|path|notebook_path|target_file|filePath|dir|directory)$/i;

    const walk = (value: unknown, key?: string): void => {
        if (typeof value === 'string') {
            if (key && pathKeys.test(key) && value.trim() !== '') {
                found.push(value);
            }

            return;
        }

        if (Array.isArray(value)) {
            value.forEach((v) => walk(v, key));

            return;
        }

        if (value && typeof value === 'object') {
            for (const [k, v] of Object.entries(value)) {
                walk(v, k);
            }
        }
    };

    walk(input);

    return found;
}
