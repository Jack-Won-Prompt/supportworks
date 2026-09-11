import { readdir, readFile, unlink } from 'node:fs/promises';
import { extname, join } from 'node:path';
import { ApiClient } from './api.js';
import { log } from './logger.js';

/**
 * 담당자가 사람에게 "보여 줄" 파일을 놓는 곳.
 *
 * 글로 설명할 수 있는 것은 답변에 쓰면 된다. 이 경로는 화면 캡처처럼 봐야만
 * 아는 것을 위한 것이다 — 사람이 눈으로 확인한 뒤 배포를 결정할 수 있게 한다.
 *
 * 턴이 끝날 때마다 비운다. 남겨 두면 다음 턴에 같은 파일이 다시 올라간다.
 */
export const OUTBOX_DIR = 'docs/aiw/outbox';

/** 이미지만 올린다. 글은 답변에 쓰는 것이 맞다. */
const MIME_BY_EXT: Record<string, string> = {
    '.png': 'image/png',
    '.jpg': 'image/jpeg',
    '.jpeg': 'image/jpeg',
    '.webp': 'image/webp',
    '.gif': 'image/gif',
};

/** 한 턴에 올릴 수 있는 장수. 넘치면 앞에서 자르고 나머지는 지운다. */
const MAX_PER_TURN = 5;

export interface OutboxResult {
    uploaded: number;
    skipped: string[];
}

/**
 * 출력함을 비우고 서버에 올린다.
 *
 * 업로드에 실패해도 파일은 지운다 — 남기면 매 턴 같은 실패를 반복하고,
 * 그 사이 작업은 계속 진행되어 사용자는 원인을 알 수 없는 지연만 겪는다.
 * 대신 무엇이 실패했는지 로그로 남긴다.
 */
export async function flushOutbox(
    api: ApiClient,
    jobId: number,
    root: string,
    /** 파일을 붙일 발언. messages() 응답으로 받은 id 다. */
    messageId: number,
): Promise<OutboxResult> {
    const dir = join(root, OUTBOX_DIR);
    let entries: string[];

    try {
        entries = await readdir(dir);
    } catch {
        // 폴더가 없는 것이 정상이다. 담당자가 만들 때만 생긴다.
        return { uploaded: 0, skipped: [] };
    }

    const result: OutboxResult = { uploaded: 0, skipped: [] };
    const images: string[] = [];

    for (const name of entries.sort()) {
        const mime = MIME_BY_EXT[extname(name).toLowerCase()];

        if (mime) {
            images.push(name);
        } else {
            result.skipped.push(name);
        }
    }

    for (const name of images.slice(MAX_PER_TURN)) {
        result.skipped.push(name);
    }

    for (const name of images.slice(0, MAX_PER_TURN)) {
        const path = join(dir, name);

        try {
            const data = await readFile(path);
            await api.uploadAttachment(jobId, messageId, name, MIME_BY_EXT[extname(name).toLowerCase()]!, data);
            result.uploaded++;
        } catch (error) {
            log('warn', '결과물 업로드 실패 — 이 파일은 건너뜁니다.', { jobId, file: name, error: String(error) });
            result.skipped.push(name);
        }

        await unlink(path).catch(() => undefined);
    }

    // 이미지가 아닌 것도 치운다. 그대로 두면 매 턴 다시 훑게 된다.
    for (const name of result.skipped) {
        await unlink(join(dir, name)).catch(() => undefined);
    }

    if (result.uploaded > 0 || result.skipped.length > 0) {
        log('info', '결과물 전달', { jobId, uploaded: result.uploaded, skipped: result.skipped.length });
    }

    return result;
}
