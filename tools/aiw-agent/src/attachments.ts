import { ApiClient } from './api.js';
import { log } from './logger.js';

/** 서버가 알려주는 첨부. 내용은 id 로 따로 내려받는다. */
export interface AttachmentRef {
    id: number;
    mime: string;
}

/** Anthropic Messages API 의 content 블록. */
export type ContentBlock =
    | { type: 'text'; text: string }
    | { type: 'image'; source: { type: 'base64'; media_type: string; data: string } };

/** 모델이 받는 형식. 그 밖의 것은 넣지 않는다. */
const SUPPORTED = new Set(['image/png', 'image/jpeg', 'image/webp', 'image/gif']);

/**
 * 첨부를 프롬프트에 넣을 수 있는 형태로 만든다.
 *
 * 파일은 메모리에서만 다룬다 — 작업 폴더에 쓰면 샌드박스 경계 안에 외부 파일이
 * 생기고, git diff 에도 섞인다.
 *
 * 한 장이라도 실패하면 그 장만 빼고 진행한다. 이미지를 못 받았다고 지시 자체를
 * 버리는 것은 과하다. 대신 무엇이 빠졌는지 본문에 남겨 모델이 오해하지 않게 한다.
 */
export async function buildContent(
    api: ApiClient,
    jobId: number,
    text: string,
    attachments: AttachmentRef[] = [],
): Promise<string | ContentBlock[]> {
    if (attachments.length === 0) {
        return text;
    }

    const blocks: ContentBlock[] = [];
    const failed: number[] = [];

    for (const ref of attachments) {
        if (!SUPPORTED.has(ref.mime)) {
            failed.push(ref.id);
            log('warn', '지원하지 않는 첨부 형식 — 건너뜁니다.', { jobId, id: ref.id, mime: ref.mime });

            continue;
        }

        try {
            const data = await api.attachment(jobId, ref.id);

            blocks.push({
                type: 'image',
                source: { type: 'base64', media_type: ref.mime, data },
            });
        } catch (error) {
            failed.push(ref.id);
            log('warn', '첨부를 내려받지 못했습니다 — 이 이미지는 빼고 진행합니다.', {
                jobId,
                id: ref.id,
                error: String(error),
            });
        }
    }

    if (blocks.length === 0) {
        return text;
    }

    // 이미지를 먼저 두고 지시를 뒤에 붙인다. 모델이 "무엇을 보고 무엇을 하라"의
    // 순서로 읽게 하기 위해서다.
    const note = failed.length > 0
        ? `\n\n(첨부 이미지 ${failed.length}장을 가져오지 못했습니다. 보이는 것만으로 판단하고, 부족하면 물어보세요.)`
        : '';

    return [...blocks, { type: 'text' as const, text: text + note }];
}
