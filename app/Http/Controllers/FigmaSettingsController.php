<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentUserCredential;
use App\Services\Agent\Figma\Exceptions\FigmaTokenNotConfiguredException;
use App\Services\Agent\Figma\FigmaClientFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FigmaSettingsController extends Controller
{
    public function __construct(
        private readonly FigmaClientFactory $factory,
    ) {}

    public function index(): View
    {
        /** @var \App\Models\User $user */
        $user       = Auth::user();
        $credential = AiAgentUserCredential::where('user_id', $user->id)->first();

        $hasToken     = $credential && $credential->hasPat();
        $status       = $credential?->figma_pat_validation_status;
        $lastValidated = $credential?->figma_pat_validated_at;
        $maskedPat    = $hasToken ? $credential->maskedPat() : null;

        return view('ai-agent.settings.figma.index', compact(
            'hasToken', 'status', 'lastValidated', 'maskedPat'
        ));
    }

    public function save(Request $request): JsonResponse
    {
        $request->validate([
            'pat' => ['required', 'string', 'min:30', 'max:200'],
        ]);

        /** @var \App\Models\User $user */
        $user       = Auth::user();
        $credential = AiAgentUserCredential::firstOrNew(['user_id' => $user->id]);
        $credential->setFigmaPat($request->pat);
        $credential->save();

        // 저장 즉시 검증
        return $this->validateToken();
    }

    public function validateToken(): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user   = Auth::user();
            $client = $this->factory->forUser($user);
            $valid  = $client->validateToken();

            AiAgentUserCredential::where('user_id', $user->id)->update([
                'figma_pat_validated_at'      => now(),
                'figma_pat_validation_status' => $valid ? 'valid' : 'invalid',
            ]);

            return response()->json([
                'valid'   => $valid,
                'message' => $valid ? '유효한 토큰입니다.' : '토큰이 유효하지 않습니다. Figma에서 토큰을 확인해 주세요.',
            ]);
        } catch (FigmaTokenNotConfiguredException) {
            return response()->json(['valid' => false, 'message' => '토큰이 설정되지 않았습니다.'], 400);
        }
    }

    public function delete(): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user       = Auth::user();
        $credential = AiAgentUserCredential::where('user_id', $user->id)->first();

        if ($credential) {
            $credential->update([
                'figma_pat_encrypted'         => null,
                'figma_pat_validated_at'       => null,
                'figma_pat_validation_status'  => null,
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Figma 토큰이 삭제되었습니다.']);
    }
}
