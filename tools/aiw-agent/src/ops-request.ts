/**
 * 모델이 등록된 운영 명령을 실행해 달라고 요청하는 블록을 뽑아낸다.
 *
 * 형식(고정):
 *
 *     ```aiw-ops
 *     점검
 *     ```
 *
 * **이름만 적는다.** 명령 문자열은 관리자가 등록한 행에서만 오고, 여기서 오지
 * 않는다 — 그것이 이 기능의 안전장치 전부다. 모델이 임의의 셸 명령을 적어 보낼
 * 수 있으면 작업 폴더 경계도 승인 카드도 의미가 없어진다.
 *
 * 왜 필요한가: 운영 서버가 이상할 때 지금은 사람이 화면에서 버튼을 눌러야 한다.
 * 원격에 사람이 없다는 전제에서는 모델이 "점검을 돌려 보고 결과를 읽고 판단" 까지
 * 할 수 있어야 한다. 다만 할 수 있는 일은 관리자가 미리 정해 둔 목록 안이다.
 *
 * 블록은 본문에서 걷어낸다. 남겨 두면 사람이 같은 내용을 마크다운 원문으로 한 번
 * 더 보게 된다.
 */

/** 한 번에 요청할 수 있는 수. 한 턴에 서버를 여러 번 건드리게 두지 않는다. */
const MAX_REQUESTS = 2;

/** 이름 한 줄의 최대 길이. 등록된 이름은 100자 제한이다. */
const MAX_LENGTH = 100;

const BLOCK = /```aiw-ops\s*\n([\s\S]*?)```/g;

export interface ParsedOps {
    /** 블록을 걷어낸 본문. */
    text: string;

    /** 요청된 운영 명령 이름. 없으면 빈 배열. */
    names: string[];
}

export function parseOpsRequests(raw: string): ParsedOps {
    const names: string[] = [];

    const text = raw.replace(BLOCK, (_match, body: string) => {
        for (const line of String(body).split('\n')) {
            const name = line.replace(/^\s*[-*]\s*/, '').trim();

            if (name !== '' && names.length < MAX_REQUESTS) {
                names.push(name.slice(0, MAX_LENGTH));
            }
        }

        return '';
    });

    return { text: text.trim(), names };
}
