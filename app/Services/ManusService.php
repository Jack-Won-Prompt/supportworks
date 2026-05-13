<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ManusService
{
    // 요청 분류용 키워드
    private const DOC_PATTERNS = [
        'pptx', 'ppt', '파워포인트', '프레젠테이션', '발표자료', '발표 자료', '슬라이드',
        'xlsx', 'excel', '엑셀', '스프레드시트',
        'docx', 'word', '워드',
        '.pdf', 'pdf 파일', 'pdf로',
        '텍스트 파일', '.txt', 'txt 파일',
        '보고서 파일', '제안서 파일', '기획서 파일', '문서 파일',
        '파일로 만들어', '파일로 작성', '파일로 생성',
    ];

    private const CODE_PATTERNS = [
        'html', 'css', 'javascript', 'typescript', 'jsx', 'tsx', 'vue',
        'php', 'python', 'java', 'sql', 'bash', 'shell',
        '소스코드', '소스 코드', '컴포넌트', '함수', '클래스',
        '코드 작성', '코드 생성', '코드 리뷰', '코드 수정',
        'api 만들어', 'api 작성', '구현해', '개발해',
    ];

    public function __construct(
        private string $apiKey,
        private string $endpoint,
    ) {}

    /**
     * 요청 분류: 'code' | 'document' | 'mixed'
     */
    public static function classifyRequest(string $userMessage): string
    {
        $lower   = mb_strtolower($userMessage);
        $isDoc   = false;
        $isCode  = false;

        foreach (self::DOC_PATTERNS as $kw) {
            if (str_contains($lower, $kw)) { $isDoc = true; break; }
        }
        foreach (self::CODE_PATTERNS as $kw) {
            if (str_contains($lower, $kw)) { $isCode = true; break; }
        }

        if ($isDoc && $isCode) return 'mixed';
        if ($isDoc)            return 'document';
        return 'code';
    }

    /**
     * 요청 텍스트에서 문서 유형 추출
     */
    public static function detectDocType(string $message): string
    {
        $lower = mb_strtolower($message);
        if (preg_match('/pptx?|파워포인트|프레젠테이션|발표자료|발표 자료|슬라이드/', $lower)) return 'pptx';
        if (preg_match('/xlsx?|엑셀|스프레드시트/', $lower))                                   return 'xlsx';
        if (preg_match('/docx?|워드/', $lower))                                                return 'docx';
        if (preg_match('/\.pdf|pdf 파일|pdf로/', $lower))                                      return 'pdf';
        return 'txt';
    }

    /**
     * Manus API로 문서 생성 요청
     * 반환: [file_name, file_type, download_url, status, task_id]
     */
    public function createDocument(string $prompt, string $docType): array
    {
        $ext  = $this->extFromType($docType);
        $name = $this->suggestFileName($prompt, $ext);

        $response = Http::withOptions(['verify' => false])
            ->withHeaders(['x-manus-api-key' => $this->apiKey])
            ->timeout(120)
            ->post(rtrim($this->endpoint, '/') . '/task.create', [
                'prompt'   => $prompt,
                'doc_type' => $docType,
                'format'   => $ext,
            ]);

        if (!$response->successful()) {
            Log::error('[ManusService] API 오류', ['status' => $response->status(), 'body' => $response->body()]);
            throw new \RuntimeException('Manus API 오류 (' . $response->status() . '): ' . $response->body());
        }

        $data = $response->json();

        return [
            'file_name'    => $data['file_name']    ?? $name,
            'file_type'    => $docType,
            'download_url' => $data['download_url'] ?? $data['url']  ?? '',
            'status'       => $data['status']        ?? 'completed',
            'task_id'      => $data['task_id']       ?? $data['id']  ?? '',
        ];
    }

    /**
     * OpenAI 호환 엔드포인트로 텍스트 응답을 반환합니다.
     */
    public function chatRaw(array $messages, string $systemPrompt): string
    {
        return $this->doChat($messages, $systemPrompt, 180);
    }

    public function chatRawTranslate(array $messages, string $systemPrompt): string
    {
        return $this->doChat($messages, $systemPrompt, 20);
    }

    public function chatRawLarge(array $messages, string $systemPrompt): string
    {
        return $this->doChat($messages, $systemPrompt, 300);
    }

    private function doChat(array $messages, string $systemPrompt, int $timeout): string
    {
        $userContent = collect($messages)
            ->filter(fn($m) => ($m['role'] ?? '') === 'user')
            ->map(fn($m) => $m['content'])
            ->last() ?? '';

        $payload = [
            'message' => ['role' => 'user', 'content' => $userContent],
        ];
        if ($systemPrompt) {
            $payload['system'] = $systemPrompt;
        }

        $res = Http::withOptions(['verify' => false])
            ->withHeaders(['x-manus-api-key' => $this->apiKey])
            ->timeout($timeout)
            ->post(rtrim($this->endpoint, '/') . '/task.create', $payload);

        if (!$res->successful()) {
            $errRaw = $res->json('error') ?? $res->json('message') ?? $res->body();
            $err = is_array($errRaw) ? json_encode($errRaw, JSON_UNESCAPED_UNICODE) : (string) $errRaw;
            throw new \RuntimeException("Manus API 오류: {$err}");
        }

        $data = $res->json();

        // 동기 응답
        if (isset($data['result']))  return is_string($data['result'])  ? $data['result']  : json_encode($data['result']);
        if (isset($data['output']))  return is_string($data['output'])  ? $data['output']  : json_encode($data['output']);
        if (isset($data['content'])) return is_string($data['content']) ? $data['content'] : json_encode($data['content']);

        // 비동기: task_id 반환 시 폴링
        $taskId = $data['task_id'] ?? $data['id'] ?? null;
        if ($taskId) {
            return $this->pollTask((string) $taskId, $timeout);
        }

        throw new \RuntimeException('Manus API가 텍스트 응답을 반환하지 않았습니다: ' . json_encode($data));
    }

    private function pollTask(string $taskId, int $timeout): string
    {
        $deadline = time() + $timeout;
        $base     = rtrim($this->endpoint, '/');

        while (time() < $deadline) {
            sleep(3);
            $res = Http::withOptions(['verify' => false])
                ->withHeaders(['x-manus-api-key' => $this->apiKey])
                ->timeout(15)
                ->get("{$base}/task.listMessages", ['task_id' => $taskId]);

            if (!$res->successful()) continue;

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

            Log::debug('[ManusService] poll', ['task_id' => $taskId, 'agent_status' => $agentStatus]);

            if ($agentStatus === 'stopped') {
                return $this->extractFromMessages($messages);
            }

            if (in_array($agentStatus, ['failed', 'error', 'cancelled'])) {
                throw new \RuntimeException("Manus 태스크 실패: {$agentStatus}");
            }
        }

        throw new \RuntimeException("Manus API 타임아웃: task_id={$taskId}");
    }

    private function extractFromMessages(array $messages): string
    {
        // assistant_message 타입의 마지막 항목 → assistant_message.content
        foreach (array_reverse($messages) as $msg) {
            if (($msg['type'] ?? '') === 'assistant_message') {
                $content = $msg['assistant_message']['content'] ?? $msg['content'] ?? null;
                if ($content !== null) {
                    return is_string($content) ? $content : json_encode($content, JSON_UNESCAPED_UNICODE);
                }
            }
        }

        Log::warning('[ManusService] extractFromMessages: no assistant_message found', [
            'types' => array_column($messages, 'type'),
        ]);
        throw new \RuntimeException('Manus 응답에서 텍스트를 추출할 수 없습니다.');
    }

    private function extFromType(string $type): string
    {
        return match($type) {
            'pptx'  => 'pptx',
            'xlsx'  => 'xlsx',
            'docx'  => 'docx',
            'pdf'   => 'pdf',
            default => 'txt',
        };
    }

    private function suggestFileName(string $prompt, string $ext): string
    {
        $base = mb_substr(preg_replace('/[^\w가-힣\s]/', '', $prompt), 0, 30);
        $base = trim(preg_replace('/\s+/', '_', $base)) ?: 'document';
        return $base . '.' . $ext;
    }
}
