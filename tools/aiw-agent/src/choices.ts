/**
 * 사용자에게 선택지를 제시하는 블록을 뽑아낸다.
 *
 * 형식(고정):
 *
 *     ```aiw-choices
 *     - 관리자 화면에서 직접 켜기
 *     - 마이그레이션으로 강제로 켜기
 *     ```
 *
 * 왜 별도 형식인가: 본문에서 번호 목록을 찾아 추측하면 "1. 원인 2. 조치" 같은
 * 평범한 설명까지 버튼이 된다. 모델이 명시적으로 표시했을 때만 버튼을 만든다.
 *
 * 블록은 본문에서 제거한다. 남겨 두면 사용자가 같은 선택지를 마크다운 원문으로
 * 한 번 더 보게 된다.
 */

/** 한 번에 제시할 수 있는 선택지 수. 넘치면 앞에서 자른다. */
const MAX_CHOICES = 6;

/** 선택지 한 줄의 최대 길이. 버튼에 들어가야 한다. */
const MAX_LENGTH = 200;

const BLOCK = /```aiw-choices\s*\n([\s\S]*?)```/g;

export interface ParsedMessage {
    /** 선택지 블록을 걷어낸 본문. */
    text: string;

    /** 없으면 빈 배열. 화면은 비어 있으면 버튼을 그리지 않는다. */
    choices: string[];
}

export function parseChoices(raw: string): ParsedMessage {
    const choices: string[] = [];

    const text = raw.replace(BLOCK, (_match, body: string) => {
        for (const line of String(body).split('\n')) {
            const item = line.replace(/^\s*[-*]\s*/, '').trim();

            if (item !== '') {
                choices.push(item.slice(0, MAX_LENGTH));
            }
        }

        return '';
    });

    return {
        text: text.trim(),
        // 중복은 버튼으로서 의미가 없다(같은 답을 두 번 보낼 이유가 없다).
        choices: [...new Set(choices)].slice(0, MAX_CHOICES),
    };
}
