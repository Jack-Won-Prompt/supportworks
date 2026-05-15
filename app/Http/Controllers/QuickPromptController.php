<?php

namespace App\Http\Controllers;

use App\Models\PromptSuffix;
use App\Models\QuickPrompt;
use App\Services\PromptRefiner\Llm\Exceptions\AllProvidersFailedException;
use App\Services\PromptRefiner\Llm\Exceptions\LlmFatalException;
use App\Services\PromptRefiner\Llm\LlmRequest;
use App\Services\PromptRefiner\Llm\LlmRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class QuickPromptController extends Controller
{
    private const SYSTEM_PROMPT = <<<PROMPT
당신은 사용자의 거친 자연어 요청을 명확하고 실행 가능한 작업 지시 프롬프트로 다시 정리하는 전문가입니다.

[변환 원칙]
1. 사용자의 의도를 보존하되, 모호한 표현은 구체적으로 다듬어 주세요.
2. 작업 목표 → 요구사항 → 기대 결과 순으로 자연스럽게 구성하세요.
3. 필요한 경우 단계별 진행 순서를 명시하세요.
4. 한국어로 작성하되, 기술 용어는 원문 표기를 유지하세요.
5. 사용자 입력에 없는 새로운 사실이나 임의 가정을 만들어내지 마세요.

[출력 형식]
반드시 아래 JSON 형식으로만 응답하세요. 다른 텍스트, 머리말, 코드블록은 절대 포함하지 마세요.

{
  "refined_prompt": "정제된 프롬프트 본문(여러 줄 가능, 줄바꿈은 \\n 으로 표현)"
}
PROMPT;

    public function __construct(private LlmRouter $llmRouter) {}

    public function index(Request $request)
    {
        $list = QuickPrompt::where('user_id', auth()->id())
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn ($q) => $this->toData($q));

        return response()->json($list);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'original_input' => 'required|string|max:5000',
            'suffix_ids'     => 'sometimes|array',
            'suffix_ids.*'   => 'integer',
        ]);

        $userId     = auth()->id();
        $requestId  = 'qp_' . Str::random(10);
        $suffixIds  = array_values(array_unique(array_map('intval', $data['suffix_ids'] ?? [])));

        // 사용자 소유의 suffix만 적용 (정렬 순서 유지)
        $suffixes = $suffixIds
            ? PromptSuffix::where('user_id', $userId)
                ->whereIn('id', $suffixIds)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
            : collect();

        try {
            $llm = $this->llmRouter->execute(
                new LlmRequest(
                    systemPrompt: self::SYSTEM_PROMPT,
                    userMessage:  $data['original_input'],
                    maxTokens:    2000,
                    temperature:  0.3,
                ),
                requestId: $requestId,
            );
        } catch (LlmFatalException $e) {
            Log::error('quick_prompt_llm_fatal', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'ok'      => false,
                'message' => '요청 형식이 올바르지 않습니다. 다시 시도해주세요.',
            ], 422);
        } catch (AllProvidersFailedException $e) {
            Log::error('quick_prompt_all_providers_failed', [
                'request_id' => $requestId,
                'error'      => $e->getMessage(),
            ]);
            return response()->json([
                'ok'      => false,
                'message' => '웍스 서비스에 일시적 장애가 있습니다. 잠시 후 다시 시도해주세요.',
            ], 503);
        }

        $parsed  = json_decode($llm->content, true);
        $refined = is_array($parsed) ? trim((string)($parsed['refined_prompt'] ?? '')) : '';
        if ($refined === '') {
            Log::warning('quick_prompt_empty_refined', [
                'request_id' => $requestId,
                'raw'        => substr($llm->content, 0, 500),
            ]);
            return response()->json([
                'ok'      => false,
                'message' => '웍스 응답을 해석하지 못했습니다. 다시 시도해주세요.',
            ], 422);
        }

        $base       = $refined;          // 순수 AI 출력 (suffix 부착 전)
        $appliedIds = [];
        foreach ($suffixes as $suffix) {
            $body = trim($suffix->body);
            if ($body === '') continue;
            $refined     .= "\n\n" . $body;
            $appliedIds[] = $suffix->id;
        }

        $row = QuickPrompt::create([
            'user_id'             => $userId,
            'original_input'      => $data['original_input'],
            'base_refined_prompt' => $base,
            'refined_prompt'      => $refined,
            'append_confirmation' => count($appliedIds) > 0,
            'applied_suffix_ids'  => $appliedIds ?: null,
            'provider_used'       => $llm->providerUsed,
            'model_used'          => $llm->modelUsed,
            'fallback_reason'     => $llm->fallbackReason,
            'elapsed_ms'          => $llm->elapsedMs,
        ]);

        return response()->json([
            'ok'   => true,
            'item' => $this->toData($row),
        ], 201);
    }

    /**
     * 결과 카드의 추가 문구를 멱등하게 토글한다.
     * 항상 base_refined_prompt 에서부터 applied_suffix_ids 를 다시 조립하므로
     * 동일 suffix 의 반복 추가나 우발적 본문 손상이 발생하지 않는다.
     */
    public function toggleSuffix(Request $request, QuickPrompt $quickPrompt)
    {
        abort_if($quickPrompt->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'suffix_id' => 'required|integer',
        ]);
        $suffixId = (int) $data['suffix_id'];

        // 사용자 본인의 suffix 인지 검증
        $suffix = PromptSuffix::where('user_id', auth()->id())
            ->where('id', $suffixId)
            ->first();
        abort_if(!$suffix, 404, '추가 문구를 찾을 수 없습니다.');

        $applied = array_values(array_map('intval', $quickPrompt->applied_suffix_ids ?? []));

        if (in_array($suffixId, $applied, true)) {
            $applied = array_values(array_diff($applied, [$suffixId]));
        } else {
            $applied[] = $suffixId;
        }

        // base 가 비어있는 legacy row 방어
        $base = $quickPrompt->base_refined_prompt ?? $quickPrompt->refined_prompt ?? '';

        // applied 순서는 sort_order 기준으로 재정렬해 일관성 유지
        $orderedSuffixes = $applied
            ? PromptSuffix::where('user_id', auth()->id())
                ->whereIn('id', $applied)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
            : collect();

        $refined        = $base;
        $finalApplied   = [];
        foreach ($orderedSuffixes as $sfx) {
            $body = trim($sfx->body);
            if ($body === '') continue;
            $refined       .= "\n\n" . $body;
            $finalApplied[] = $sfx->id;
        }

        $quickPrompt->update([
            'refined_prompt'      => $refined,
            'applied_suffix_ids'  => $finalApplied ?: null,
            'append_confirmation' => count($finalApplied) > 0,
        ]);

        return response()->json([
            'ok'   => true,
            'item' => $this->toData($quickPrompt->fresh()),
        ]);
    }

    public function destroy(QuickPrompt $quickPrompt)
    {
        abort_if($quickPrompt->user_id !== auth()->id(), 403);
        $quickPrompt->delete();

        return response()->json(['ok' => true]);
    }

    private function toData(QuickPrompt $q): array
    {
        $appliedIds = $q->applied_suffix_ids ?? [];
        $appliedLabels = [];
        if (!empty($appliedIds)) {
            $appliedLabels = PromptSuffix::whereIn('id', $appliedIds)
                ->pluck('label', 'id')
                ->toArray();
        }

        return [
            'id'                  => $q->id,
            'original_input'      => $q->original_input,
            'refined_prompt'      => $q->refined_prompt,
            'applied_suffix_ids'  => array_values(array_map('intval', $appliedIds ?: [])),
            'applied_suffixes'    => array_map(
                fn ($id) => ['id' => (int) $id, 'label' => $appliedLabels[$id] ?? '(삭제됨)'],
                $appliedIds ?: []
            ),
            'provider_used'       => $q->provider_used,
            'model_used'          => $q->model_used,
            'fallback_reason'     => $q->fallback_reason,
            'elapsed_ms'          => $q->elapsed_ms,
            'created_at'          => $q->created_at?->diffForHumans(),
        ];
    }
}
