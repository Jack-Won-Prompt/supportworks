import './test-env.js';
import assert from 'node:assert/strict';
import { test } from 'node:test';
import type { ApiClient } from './api.js';
import { buildContent, ContentBlock } from './attachments.js';

class FakeApi {
    readonly fetched: number[] = [];

    /** 이 id 는 내려받기에 실패한다. */
    failing = new Set<number>();

    async attachment(_jobId: number, id: number): Promise<string> {
        this.fetched.push(id);

        if (this.failing.has(id)) {
            throw new Error('404');
        }

        return `base64-of-${id}`;
    }
}

const build = (api: FakeApi, text: string, refs: { id: number; mime: string }[] = []) =>
    buildContent(api as unknown as ApiClient, 1, text, refs);

const images = (blocks: ContentBlock[]) => blocks.filter((b) => b.type === 'image');
const texts = (blocks: ContentBlock[]) => blocks.filter((b) => b.type === 'text');

test('첨부가 없으면 문자열 그대로 보낸다', async () => {
    const api = new FakeApi();

    // 블록 배열로 감싸면 이미지 없는 평범한 지시까지 형태가 바뀐다.
    assert.equal(await build(api, '그냥 지시'), '그냥 지시');
    assert.deepEqual(api.fetched, []);
});

test('이미지를 먼저 두고 지시를 뒤에 붙인다', async () => {
    const api = new FakeApi();

    const content = await build(api, '이 화면을 고쳐 주세요', [
        { id: 7, mime: 'image/png' },
        { id: 8, mime: 'image/jpeg' },
    ]);

    assert.ok(Array.isArray(content));
    const blocks = content as ContentBlock[];

    assert.equal(blocks.length, 3);
    assert.equal(blocks[0]?.type, 'image');
    assert.equal(blocks[1]?.type, 'image');
    // 모델이 "무엇을 보고 무엇을 하라" 순서로 읽게 한다.
    assert.equal(blocks[2]?.type, 'text');
    assert.deepEqual(api.fetched, [7, 8]);
});

test('base64 와 media_type 을 SDK 형식으로 싣는다', async () => {
    const api = new FakeApi();

    const blocks = (await build(api, 'x', [{ id: 3, mime: 'image/webp' }])) as ContentBlock[];
    const first = images(blocks)[0];

    assert.deepEqual(first, {
        type: 'image',
        source: { type: 'base64', media_type: 'image/webp', data: 'base64-of-3' },
    });
});

test('지원하지 않는 형식은 건너뛴다', async () => {
    const api = new FakeApi();

    const content = await build(api, '지시', [{ id: 1, mime: 'application/pdf' }]);

    // 하나뿐인 첨부가 걸러졌으므로 문자열로 돌아간다.
    assert.equal(content, '지시');
    assert.deepEqual(api.fetched, [], '거를 것은 내려받지도 않는다.');
});

test('일부가 실패하면 나머지로 진행하고 본문에 알린다', async () => {
    const api = new FakeApi();
    api.failing.add(2);

    const blocks = (await build(api, '지시', [
        { id: 1, mime: 'image/png' },
        { id: 2, mime: 'image/png' },
    ])) as ContentBlock[];

    assert.equal(images(blocks).length, 1, '받은 것만 넣는다.');

    const body = texts(blocks)[0];

    assert.ok(body && body.type === 'text');
    // 모델이 "이미지를 다 봤다"고 오해하면 안 된다.
    assert.match(body.text, /1장을 가져오지 못했습니다/);
    assert.match(body.text, /^지시/);
});

test('전부 실패하면 지시만 보낸다', async () => {
    const api = new FakeApi();
    api.failing.add(1);

    // 이미지를 못 받았다고 지시 자체를 버리는 것은 과하다.
    assert.equal(await build(api, '지시', [{ id: 1, mime: 'image/png' }]), '지시');
});
