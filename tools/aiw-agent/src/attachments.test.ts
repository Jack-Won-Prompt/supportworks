import './test-env.js';
import assert from 'node:assert/strict';
import { existsSync } from 'node:fs';
import { mkdir, mkdtemp, readFile, rm } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { test } from 'node:test';
import type { ApiClient } from './api.js';
import { buildContent, ContentBlock, INBOX_DIR } from './attachments.js';

class FakeApi {
    readonly fetched: number[] = [];

    /** 이 id 는 내려받기에 실패한다. */
    failing = new Set<number>();

    /** id 별 원본 내용(문자열). 없으면 기본값을 돌려준다. */
    readonly contents = new Map<number, string>();

    async attachment(_jobId: number, id: number): Promise<string> {
        this.fetched.push(id);

        if (this.failing.has(id)) {
            throw new Error('404');
        }

        const raw = this.contents.get(id);

        return raw === undefined
            ? `base64-of-${id}`
            : Buffer.from(raw, 'utf8').toString('base64');
    }
}

const build = (
    api: FakeApi,
    text: string,
    refs: { id: number; mime: string; name?: string }[] = [],
    root?: string,
) => buildContent(api as unknown as ApiClient, 1, text, refs, root);

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

test('짧은 텍스트 첨부는 본문에 그대로 넣는다', async () => {
    // 파일로 풀면 모델이 한 번 더 열어야 한다.
    const api = new FakeApi();

    api.contents.set(1, '첫 줄\n둘째 줄');

    const content = await build(api, '지시', [{ id: 1, mime: 'text/plain', name: '메모.txt' }]);

    assert.equal(typeof content, 'string');
    assert.ok((content as string).includes('메모.txt'));
    assert.ok((content as string).includes('둘째 줄'));
});

test('문서 첨부는 작업 폴더에 풀고 경로를 알려 준다', async () => {
    const api = new FakeApi();
    const root = await mkdtemp(join(tmpdir(), 'aiw-inbox-'));

    api.contents.set(9, 'fake-xlsx-bytes');

    const content = await build(
        api,
        '이 표를 정리해 주세요',
        [{ id: 9, mime: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', name: '견적.xlsx' }],
        root,
    );

    assert.equal(typeof content, 'string');
    const body = content as string;

    assert.ok(body.includes(INBOX_DIR + '/견적.xlsx'), body);
    assert.ok(body.includes('openpyxl'), '무엇으로 열 수 있는지 알려 준다.');
    assert.equal(existsSync(join(root, '.aiw', 'inbox', '견적.xlsx')), true);

    await rm(root, { recursive: true, force: true });
});

test('문서를 풀면 저장소가 그 폴더를 무시하게 한다', async () => {
    // 이게 없으면 첨부를 풀자마자 dirty_tree 가 되어 다음 지시가 시작조차 못 한다.
    const api = new FakeApi();
    const root = await mkdtemp(join(tmpdir(), 'aiw-inbox-'));

    await mkdir(join(root, '.git', 'info'), { recursive: true });
    api.contents.set(5, 'pdf');

    await build(api, 'x', [{ id: 5, mime: 'application/pdf', name: '계약.pdf' }], root);

    const exclude = await readFile(join(root, '.git', 'info', 'exclude'), 'utf8');
    const lines = (t: string) => t.split('\n').filter((l) => l.trim() === '.aiw/').length;

    assert.equal(lines(exclude), 1);

    // 두 번 넣지 않는다.
    await build(api, 'x', [{ id: 5, mime: 'application/pdf', name: '계약2.pdf' }], root);

    assert.equal(lines(await readFile(join(root, '.git', 'info', 'exclude'), 'utf8')), 1);

    await rm(root, { recursive: true, force: true });
});

test('작업 폴더를 모르면 문서는 빼고 알린다', async () => {
    const api = new FakeApi();

    const content = await build(api, '지시', [{ id: 1, mime: 'application/pdf', name: 'a.pdf' }]);

    assert.ok((content as string).includes('가져오지 못했습니다'));
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
    assert.match(body.text, /1개를 가져오지 못했습니다/);
    assert.match(body.text, /^지시/);
});

test('전부 실패하면 지시만 보낸다', async () => {
    const api = new FakeApi();
    api.failing.add(1);

    // 지시 자체를 버리는 것은 과하다. 다만 모델이 "다 봤다"고 오해하면 안 되므로
    // 무엇이 빠졌는지는 알려 준다.
    const content = await build(api, '지시', [{ id: 1, mime: 'image/png' }]);

    assert.equal(typeof content, 'string');
    assert.ok((content as string).startsWith('지시'));
    assert.ok((content as string).includes('가져오지 못했습니다'));
});
