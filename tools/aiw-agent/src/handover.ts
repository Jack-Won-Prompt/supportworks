import { mkdir, readFile, writeFile } from 'node:fs/promises';
import { dirname, join } from 'node:path';
import { config } from './config.js';

/** 인수인계 문서에 반드시 있어야 하는 섹션. 검증 기준이자 다음 세션의 목차다. */
export const REQUIRED_SECTIONS = [
    '## 작업 목표',
    '## 완료한 것',
    '## 변경 파일',
    '## 남은 작업',
    '## 결정 사항과 이유',
    '## 주의사항·함정',
    '## 다음 세션이 바로 실행할 첫 작업',
] as const;

export function handoverPath(root: string, jobId: number, index: number): string {
    return join(root, config.handoverDir, `job-${jobId}-${index}.md`);
}

export function handoverPrompt(jobId: number, index: number): string {
    const relative = `${config.handoverDir}/job-${jobId}-${index}.md`;

    return [
        `컨텍스트 정리를 위해 세션을 교체합니다. 아래 파일을 작성하세요: ${relative}`,
        '',
        '섹션(고정, 순서와 제목을 그대로 사용):',
        ...REQUIRED_SECTIONS.map((s) => s),
        '',
        '각 섹션은 사실만 적고, 파일 경로와 함수명은 정확히 쓰세요.',
        '추측이나 계획이 아닌 "지금까지 실제로 일어난 일"과 "다음에 할 일"만 담습니다.',
        '작성 후 "HANDOVER_DONE"만 출력하세요.',
    ].join('\n');
}

export interface HandoverValidation {
    ok: boolean;
    missing: string[];
    content: string;
}

export async function validateHandover(path: string): Promise<HandoverValidation> {
    let content: string;

    try {
        content = await readFile(path, 'utf8');
    } catch {
        return { ok: false, missing: [...REQUIRED_SECTIONS], content: '' };
    }

    const missing = REQUIRED_SECTIONS.filter((section) => !content.includes(section));

    return { ok: missing.length === 0, missing, content };
}

/**
 * Claude 가 문서를 제대로 못 쓴 경우의 폴백.
 *
 * 최근 assistant 메시지로 최소한의 요약을 데몬이 직접 만든다. 인수인계 없이
 * 세션을 갈아치우면 맥락이 통째로 사라지므로, 빈약해도 없는 것보다 낫다.
 * daemon-generated 표기를 남겨 사람이 품질을 오해하지 않게 한다.
 */
export async function writeFallbackHandover(
    path: string,
    jobId: number,
    instruction: string,
    recentAssistant: string[],
): Promise<string> {
    const body = [
        `<!-- daemon-generated: true -->`,
        `# 인수인계 (작업 #${jobId})`,
        '',
        '> Claude 가 형식에 맞는 인수인계 문서를 작성하지 못해 데몬이 자동 생성했습니다.',
        '> 내용이 빈약할 수 있으니 다음 세션 결과를 특히 주의해서 확인하세요.',
        '',
        '## 작업 목표',
        instruction.slice(0, 2000),
        '',
        '## 완료한 것',
        '(자동 생성 — 아래 최근 대화 참조)',
        '',
        '## 변경 파일',
        '(자동 생성 — git diff 로 확인 필요)',
        '',
        '## 남은 작업',
        '(자동 생성 — 원 지시문과 아래 대화를 비교해 판단 필요)',
        '',
        '## 결정 사항과 이유',
        '(자동 생성 — 기록 없음)',
        '',
        '## 주의사항·함정',
        '(자동 생성 — 기록 없음)',
        '',
        '## 다음 세션이 바로 실행할 첫 작업',
        '원 지시문을 다시 읽고, 아래 최근 대화에서 마지막으로 하던 일을 이어서 진행하세요.',
        '',
        '---',
        '',
        '## 최근 대화 (최대 10건)',
        ...recentAssistant.slice(-10).map((t, i) => `\n### ${i + 1}\n\n${t.slice(0, 1500)}`),
    ].join('\n');

    await mkdir(dirname(path), { recursive: true });
    await writeFile(path, body, 'utf8');

    return body;
}

/** 교체된 새 세션의 시작 프롬프트. resume 을 쓰지 않고 문서로만 맥락을 잇는다. */
export function resumePrompt(instruction: string, handover: string): string {
    return [
        '원 지시문:',
        instruction,
        '',
        '이전 세션 인수인계:',
        handover,
        '',
        "'다음 세션이 바로 실행할 첫 작업'부터 이어가세요.",
    ].join('\n');
}
