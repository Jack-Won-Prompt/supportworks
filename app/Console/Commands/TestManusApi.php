<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class TestManusApi extends Command
{
    protected $signature   = 'manus:test {--create : task.create 및 폴링까지 실행}';
    protected $description = 'Manus API v2 연동 테스트';

    private string $apiKey;
    private string $base;

    public function handle(): int
    {
        $this->apiKey = config('services.manus.key')   ?: env('MANUS_API_KEY');
        $this->base   = rtrim(config('services.manus.endpoint') ?: env('MANUS_API_ENDPOINT', 'https://api.manus.ai/v2'), '/');

        if (!$this->apiKey) {
            $this->error('MANUS_API_KEY가 설정되지 않았습니다.');
            return 1;
        }

        $this->info("=== Manus API 연동 테스트 ===");
        $this->line("엔드포인트: {$this->base}");
        $this->line("API Key: " . substr($this->apiKey, 0, 12) . '...' . substr($this->apiKey, -6));
        $this->newLine();

        $results = [];

        // ── 1. skill.list ──────────────────────────────────────────────────
        $results['skill.list'] = $this->testGet('skill.list', [], function ($data) {
            $skills = $data['skills'] ?? $data['data'] ?? $data;
            $count  = is_array($skills) ? count($skills) : '?';
            $names  = is_array($skills) ? implode(', ', array_column($skills, 'name')) : '';
            $this->line("  → 스킬 {$count}개" . ($names ? ": {$names}" : ''));
        });

        // ── 2. connector.list ─────────────────────────────────────────────
        $results['connector.list'] = $this->testGet('connector.list', [], function ($data) {
            $items = $data['connectors'] ?? $data['data'] ?? $data;
            $count = is_array($items) ? count($items) : '?';
            $names = is_array($items) ? implode(', ', array_column($items, 'name')) : '';
            $this->line("  → 커넥터 {$count}개" . ($names ? ": {$names}" : ''));
        });

        // ── 3. project.list ───────────────────────────────────────────────
        $results['project.list'] = $this->testGet('project.list', [], function ($data) {
            $items = $data['projects'] ?? $data['data'] ?? $data;
            $count = is_array($items) ? count($items) : '?';
            $this->line("  → 프로젝트 {$count}개");
        });

        // ── 4. task.list ──────────────────────────────────────────────────
        $taskId = null;
        $results['task.list'] = $this->testGet('task.list', [], function ($data) use (&$taskId) {
            $items = $data['tasks'] ?? $data['data'] ?? $data;
            $count = is_array($items) ? count($items) : '?';
            $this->line("  → 태스크 {$count}개");
            if (is_array($items) && count($items) > 0) {
                $latest = $items[0];
                $taskId = $latest['task_id'] ?? $latest['id'] ?? null;
                $status = $latest['status'] ?? $latest['state'] ?? '?';
                $this->line("  → 최신 task_id: {$taskId}, status: {$status}");
            }
        });

        // ── 5. task.create (--create 플래그 있을 때만) ───────────────────
        if ($this->option('create')) {
            $this->newLine();
            $this->warn('⚡ task.create 실행 (실제 태스크 생성 및 과금 발생)');

            $payload  = [
                'message' => [
                    'role'    => 'user',
                    'content' => '1+1은 무엇인가요? 숫자만 답해주세요.',
                ],
            ];
            $response = $this->callPost('task.create', $payload);

            if ($response === null) {
                $results['task.create'] = false;
                $this->error('  ✗ task.create 실패');
            } else {
                $results['task.create'] = true;
                $createdTaskId = $response['task_id'] ?? $response['id'] ?? null;
                $this->info("  ✓ task.create 성공 — task_id: {$createdTaskId}");

                if ($createdTaskId) {
                    $this->newLine();
                    $this->line('  ⏳ task.listMessages 폴링 시작 (최대 180초)...');
                    $results['task.listMessages'] = $this->pollMessages($createdTaskId);
                }
            }
        } else {
            $this->line('<fg=gray>  (task.create 생략 — --create 옵션으로 실행)</fg=gray>');
        }

        // ── 결과 요약 ────────────────────────────────────────────────────
        $this->newLine();
        $this->line('=== 결과 요약 ===');
        $headers = ['엔드포인트', '결과'];
        $rows    = [];
        foreach ($results as $ep => $ok) {
            $rows[] = [$ep, $ok ? '<fg=green>✓ 성공</fg=green>' : '<fg=red>✗ 실패</fg=red>'];
        }
        $this->table($headers, $rows);

        return 0;
    }

    // ─────────────────────────────────────────────────────────────────────
    //  helpers
    // ─────────────────────────────────────────────────────────────────────

    private function testGet(string $ep, array $params, callable $onSuccess): bool
    {
        $this->line("► GET {$ep}");
        $res = Http::withOptions(['verify' => false])
            ->withHeaders(['x-manus-api-key' => $this->apiKey])
            ->timeout(15)
            ->get("{$this->base}/{$ep}", $params);

        if ($res->successful()) {
            $this->info("  ✓ HTTP {$res->status()}");
            $onSuccess($res->json() ?? []);
            return true;
        }

        $this->error("  ✗ HTTP {$res->status()} — " . $res->body());
        return false;
    }

    private function callPost(string $ep, array $payload): ?array
    {
        $this->line("► POST {$ep}");
        $res = Http::withOptions(['verify' => false])
            ->withHeaders(['x-manus-api-key' => $this->apiKey])
            ->timeout(30)
            ->post("{$this->base}/{$ep}", $payload);

        if ($res->successful()) {
            $this->info("  ✓ HTTP {$res->status()}");
            return $res->json() ?? [];
        }

        $this->error("  ✗ HTTP {$res->status()} — " . $res->body());
        return null;
    }

    private function pollMessages(string $taskId): bool
    {
        $deadline = time() + 180;
        $base     = $this->base;

        while (time() < $deadline) {
            sleep(3);
            $res = Http::withOptions(['verify' => false])
                ->withHeaders(['x-manus-api-key' => $this->apiKey])
                ->timeout(15)
                ->get("{$base}/task.listMessages", ['task_id' => $taskId]);

            if (!$res->successful()) {
                $this->warn("  폴링 HTTP {$res->status()} — " . $res->body());
                continue;
            }

            $body     = $res->json() ?? [];
            $messages = $body['messages'] ?? [];

            // messages 배열은 역순(최신→과거) — 첫 번째 status_update가 최신 상태
            $agentStatus = null;
            foreach ($messages as $msg) {
                if (($msg['type'] ?? '') === 'status_update') {
                    $agentStatus = $msg['status_update']['agent_status'] ?? null;
                    break;
                }
            }

            $this->line("  agent_status: " . ($agentStatus ?? '(없음)') . " — 메시지 " . count($messages) . "개");

            if ($agentStatus === 'stopped') {
                // assistant_message 타입의 마지막 항목에서 content 추출
                // 구조: message.assistant_message.content
                $answer = null;
                foreach (array_reverse($messages) as $msg) {
                    if (($msg['type'] ?? '') === 'assistant_message') {
                        $answer = $msg['assistant_message']['content'] ?? $msg['content'] ?? null;
                        break;
                    }
                }

                if ($answer !== null) {
                    $this->info("  ✓ 최종 답변: {$answer}");
                } else {
                    $this->warn("  assistant_message 없음.");
                }
                return true;
            }

            if (in_array($agentStatus, ['failed', 'error', 'cancelled'])) {
                $this->error("  ✗ 태스크 실패: {$agentStatus}");
                return false;
            }
        }

        $this->error("  ✗ 타임아웃 (180초 초과)");
        return false;
    }
}
