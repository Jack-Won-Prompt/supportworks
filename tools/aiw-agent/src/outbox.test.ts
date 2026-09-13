import './test-env.js';
import assert from 'node:assert/strict';
import { mkdir, mkdtemp, readdir, writeFile } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import type { ApiClient } from './api.js';
import { flushOutbox, OUTBOX_DIR } from './outbox.js';

class FakeApi {
    readonly uploaded: { messageId: number; filename: string; mime: string; bytes: number }[] = [];

    /** 이 파일 이름은 업로드가 실패한다. */
    failing = new Set<string>();

    async uploadAttachment(
        _jobId: number,
        messageId: number,
        filename: string,
        mime: string,
        data: Buffer,
    ): Promise<{ attachment_id: number | null }> {
        if (this.failing.has(filename)) {
            throw new Error('업로드 실패');
        }

        this.uploaded.push({ messageId, filename, mime, bytes: data.length });

        return { attachment_id: this.uploaded.length };
    }
}

async function workspace(files: Record<string, string> = {}): Promise<string> {
    const root = await mkdtemp(join(tmpdir(), 'aiw-outbox-'));

    if (Object.keys(files).length > 0) {
        await mkdir(join(root, OUTBOX_DIR), { recursive: true });
    }

    for (const [name, body] of Object.entries(files)) {
        await writeFile(join(root, OUTBOX_DIR, name), body, 'utf8');
    }

    return root;
}

const flush = (api: FakeApi, root: string, messageId = 3) =>
    flushOutbox(api as unknown as ApiClient, 1, root, messageId);

const remaining = (root: string) => readdir(join(root, OUTBOX_DIR)).catch(() => []);

test('출력함이 없으면 아무 일도 하지 않는다', async () => {
    const api = new FakeApi();
    const root = await workspace();

    // 담당자가 만들 때만 생기는 폴더다. 없는 것이 정상이다.
    assert.deepEqual(await flush(api, root), { uploaded: 0, skipped: [] });
    assert.deepEqual(api.uploaded, []);
});

test('이미지를 올리고 그 발언에 붙인다', async () => {
    const api = new FakeApi();
    const root = await workspace({ 'after.png': 'PNG-DATA' });

    const result = await flush(api, root, 7);

    assert.equal(result.uploaded, 1);
    assert.equal(api.uploaded[0]?.messageId, 7, '메시지가 먼저 있어야 그 id 로 붙일 수 있다.');
    assert.equal(api.uploaded[0]?.mime, 'image/png');
    assert.equal(api.uploaded[0]?.filename, 'after.png');
});

test('확장자로 형식을 정한다', async () => {
    const api = new FakeApi();
    const root = await workspace({ 'a.jpg': 'x', 'b.webp': 'x', 'c.gif': 'x' });

    await flush(api, root);

    assert.deepEqual(
        api.uploaded.map((u) => u.mime).sort(),
        ['image/gif', 'image/jpeg', 'image/webp'],
    );
});

test('문서도 올린다 — 표·문서·발표자료는 파일이어야 의미가 있다', async () => {
    const api = new FakeApi();
    const root = await workspace({ 'report.xlsx': 'x', 'note.md': '글', 'shot.png': 'x' });

    const result = await flush(api, root);

    assert.equal(result.uploaded, 3);
    assert.deepEqual(result.skipped, []);
    assert.deepEqual(await remaining(root), []);
});

test('알 수 없는 확장자는 건너뛰고 치운다', async () => {
    const api = new FakeApi();
    const root = await workspace({ 'dump.bin': 'x', 'shot.png': 'x' });

    // 남겨 두면 매 턴 다시 훑는다.
    const result = await flush(api, root);

    assert.equal(result.uploaded, 1);
    assert.deepEqual(result.skipped, ['dump.bin']);
    assert.deepEqual(await remaining(root), []);
});

test('올린 파일은 지운다', async () => {
    const api = new FakeApi();
    const root = await workspace({ 'shot.png': 'x' });

    await flush(api, root);

    // 남기면 다음 턴에 같은 캡처가 또 올라간다.
    assert.deepEqual(await remaining(root), []);
});

test('업로드에 실패해도 파일을 남기지 않는다', async () => {
    const api = new FakeApi();
    const root = await workspace({ 'bad.png': 'x', 'good.png': 'x' });
    api.failing.add('bad.png');

    const result = await flush(api, root);

    // 남기면 매 턴 같은 실패를 반복하고 사용자는 원인 모를 지연만 겪는다.
    assert.equal(result.uploaded, 1);
    assert.deepEqual(result.skipped, ['bad.png']);
    assert.deepEqual(await remaining(root), []);
});

test('한 턴에 5장까지만 올린다', async () => {
    const api = new FakeApi();
    const files: Record<string, string> = {};

    for (let i = 0; i < 8; i++) {
        files[`shot-${i}.png`] = 'x';
    }

    const root = await workspace(files);
    const result = await flush(api, root);

    assert.equal(result.uploaded, 5);
    assert.equal(result.skipped.length, 3);
    assert.deepEqual(await remaining(root), [], '넘친 것도 치운다.');
});
