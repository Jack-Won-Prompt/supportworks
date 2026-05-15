<?php

namespace App\Http\Controllers;

use App\Models\PromptSuffix;
use Illuminate\Http\Request;

class PromptSuffixController extends Controller
{
    private const DEFAULT_BODY = "작업을 진행하시기 전에, 각 단계에서 사용자의 확인이 필요한 시점에는 임의로 진행하지 마시고 반드시 사용자에게 확인을 요청한 후에 다음 단계로 진행";

    public function index()
    {
        $userId = auth()->id();

        // 라이브러리가 비어있으면 기본 문구 자동 시드 (마이그레이션 이후 가입한 사용자 대비)
        if (PromptSuffix::where('user_id', $userId)->doesntExist()) {
            PromptSuffix::create([
                'user_id'    => $userId,
                'label'      => '확인 진행 추가',
                'body'       => self::DEFAULT_BODY,
                'sort_order' => 0,
            ]);
        }

        $list = PromptSuffix::where('user_id', $userId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn ($s) => $this->toData($s));

        return response()->json($list);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:100',
            'body'  => 'required|string|max:2000',
        ]);

        $userId   = auth()->id();
        $maxOrder = (int) PromptSuffix::where('user_id', $userId)->max('sort_order');

        $suffix = PromptSuffix::create([
            'user_id'    => $userId,
            'label'      => $data['label'],
            'body'       => $data['body'],
            'sort_order' => $maxOrder + 1,
        ]);

        return response()->json([
            'ok'   => true,
            'item' => $this->toData($suffix),
        ], 201);
    }

    public function update(Request $request, PromptSuffix $promptSuffix)
    {
        abort_if($promptSuffix->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'label' => 'sometimes|required|string|max:100',
            'body'  => 'sometimes|required|string|max:2000',
        ]);

        $promptSuffix->update($data);

        return response()->json([
            'ok'   => true,
            'item' => $this->toData($promptSuffix),
        ]);
    }

    public function destroy(PromptSuffix $promptSuffix)
    {
        abort_if($promptSuffix->user_id !== auth()->id(), 403);

        $userId   = auth()->id();
        $suffixId = $promptSuffix->id;

        // 이 suffix 가 적용된 quick_prompts 들에서 ID 제거 + refined_prompt 를 base 기준으로 재조립
        $affected = \App\Models\QuickPrompt::where('user_id', $userId)
            ->whereNotNull('applied_suffix_ids')
            ->get()
            ->filter(fn ($qp) => in_array($suffixId, array_map('intval', $qp->applied_suffix_ids ?? []), true));

        foreach ($affected as $qp) {
            $remaining = array_values(array_diff(
                array_map('intval', $qp->applied_suffix_ids ?? []),
                [$suffixId]
            ));

            $base = $qp->base_refined_prompt ?? $qp->refined_prompt ?? '';
            $orderedSuffixes = $remaining
                ? PromptSuffix::where('user_id', $userId)
                    ->whereIn('id', $remaining)
                    ->orderBy('sort_order')
                    ->orderBy('id')
                    ->get()
                : collect();

            $refined = $base;
            $finalIds = [];
            foreach ($orderedSuffixes as $sfx) {
                $body = trim($sfx->body);
                if ($body === '') continue;
                $refined  .= "\n\n" . $body;
                $finalIds[] = $sfx->id;
            }

            $qp->update([
                'refined_prompt'      => $refined,
                'applied_suffix_ids'  => $finalIds ?: null,
                'append_confirmation' => count($finalIds) > 0,
            ]);
        }

        $promptSuffix->delete();

        return response()->json(['ok' => true]);
    }

    private function toData(PromptSuffix $s): array
    {
        return [
            'id'         => $s->id,
            'label'      => $s->label,
            'body'       => $s->body,
            'sort_order' => $s->sort_order,
        ];
    }
}
