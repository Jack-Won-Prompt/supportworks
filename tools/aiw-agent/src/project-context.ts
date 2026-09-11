import { readFile } from 'node:fs/promises';
import { join } from 'node:path';
import { log } from './logger.js';

/** 프롬프트에 실을 프로젝트 규칙의 최대 크기. 넘치면 앞부분만 쓴다. */
const MAX_BYTES = 16 * 1024;

/** 저장소가 프로젝트 규칙을 두는 관습적 위치들. 먼저 찾은 하나만 쓴다. */
const CANDIDATES = ['CLAUDE.md', '.claude/CLAUDE.md'];

/**
 * 프로젝트 규칙(CLAUDE.md)을 읽어 프롬프트에 넣을 조각을 만든다.
 *
 * SDK 의 settingSources 를 열면 CLAUDE.md 가 자동 로드되지만, 그러면 그 PC 의
 * ~/.claude/settings.json 에 쌓인 허용 규칙까지 함께 들어와 승인 흐름이
 * 흔들린다(실측으로 확인했다). 설정은 격리한 채 규칙만 직접 읽어 전달한다.
 */
export async function loadProjectRules(root: string): Promise<string | null> {
    for (const candidate of CANDIDATES) {
        try {
            const raw = await readFile(join(root, candidate), 'utf8');
            const trimmed = raw.trim();

            if (trimmed === '') {
                continue;
            }

            const body = trimmed.length > MAX_BYTES
                ? trimmed.slice(0, MAX_BYTES) + '\n\n... (규칙 문서가 길어 앞부분만 전달됨)'
                : trimmed;

            log('info', '프로젝트 규칙을 프롬프트에 포함합니다.', { file: candidate, bytes: body.length });

            return [
                `--- 이 저장소의 규칙 (${candidate}) ---`,
                body,
                '--- 규칙 끝 ---',
                '',
                '위 규칙을 지키되, 규칙과 이 작업 지시가 충돌하면 지시를 우선하고 그 사실을 알려 주세요.',
            ].join('\n');
        } catch {
            // 없으면 다음 후보로. 규칙 문서는 없어도 작업은 진행된다.
        }
    }

    return null;
}
