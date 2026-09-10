import { appendFile, mkdir } from 'node:fs/promises';
import { join } from 'node:path';
import { config } from './config.js';

type Level = 'info' | 'warn' | 'error';

function stamp(): string {
    return new Date().toISOString();
}

export function log(level: Level, message: string, context?: Record<string, unknown>): void {
    const line = `[${stamp()}] ${level.toUpperCase()} ${message}`;
    const extra = context && Object.keys(context).length ? ' ' + JSON.stringify(context) : '';

    if (level === 'error') {
        console.error(line + extra);
    } else if (level === 'warn') {
        console.warn(line + extra);
    } else {
        console.log(line + extra);
    }
}

/**
 * stream-json 원문을 job 별 파일에 그대로 남긴다.
 *
 * 서버로 보내는 raw 는 4KB 로 잘리므로, 사후 조사에 쓸 전문은 여기에만 있다.
 * 로컬 파일 쓰기가 실패해도 작업을 멈추지 않는다 — 로그는 작업의 부산물이다.
 */
export class JobLogWriter {
    private ready: Promise<void>;

    private readonly path: string;

    constructor(private readonly jobId: number) {
        this.path = join(config.logDir, `job-${jobId}.jsonl`);
        this.ready = mkdir(config.logDir, { recursive: true }).then(() => undefined);
    }

    async write(payload: unknown): Promise<void> {
        try {
            await this.ready;
            await appendFile(this.path, JSON.stringify({ at: stamp(), payload }) + '\n', 'utf8');
        } catch (error) {
            log('warn', '로컬 로그 기록 실패', { jobId: this.jobId, error: String(error) });
        }
    }
}
