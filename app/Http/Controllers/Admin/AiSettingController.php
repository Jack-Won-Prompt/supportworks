<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiSetting;
use Illuminate\Http\Request;

class AiSettingController extends Controller
{
    public function index()
    {
        $setting = AiSetting::current();

        return view('admin.ai-settings.index', compact('setting'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'anthropic_key'  => 'nullable|string|max:200',
            'openai_key'     => 'nullable|string|max:200',
            'figma_token'    => 'nullable|string|max:200',
            'manus_key'      => 'nullable|string|max:200',
            'manus_endpoint' => 'nullable|url|max:300',
        ]);

        $s = AiSetting::current();
        $mask = '••••••••••';

        if ($request->filled('anthropic_key') && $request->anthropic_key !== $mask) {
            $s->anthropic_key = encrypt(trim($request->anthropic_key));
        }
        if ($request->filled('openai_key') && $request->openai_key !== $mask) {
            $s->openai_key = encrypt(trim($request->openai_key));
        }
        if ($request->filled('figma_token') && $request->figma_token !== $mask) {
            $s->figma_token = encrypt(trim($request->figma_token));
        }
        if ($request->filled('manus_key') && $request->manus_key !== $mask) {
            $s->manus_key = encrypt(trim($request->manus_key));
        }
        if ($request->has('manus_endpoint')) {
            $s->manus_endpoint = trim($request->manus_endpoint) ?: null;
        }

        // 삭제 요청 처리
        if ($request->boolean('clear_anthropic'))  $s->anthropic_key  = null;
        if ($request->boolean('clear_openai'))     $s->openai_key     = null;
        if ($request->boolean('clear_figma'))      $s->figma_token    = null;
        if ($request->boolean('clear_manus'))      $s->manus_key      = null;

        $s->save();

        return redirect()->route('admin.ai-settings.index')
            ->with('success', '웍스 API 키 설정이 저장되었습니다.');
    }
}
