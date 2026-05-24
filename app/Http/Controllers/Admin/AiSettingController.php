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
        // WITHWORKS Git 연결: 프로젝트별 ID + path_prefix 맵
        $links = \App\Models\ProjectGitLink::where('source', 'withworks')->get(['project_id', 'path_prefix']);
        $linkedProjectIds = $links->pluck('project_id')->all();
        $projectPrefixMap = $links->pluck('path_prefix', 'project_id')->all();
        $allProjects      = \App\Models\Project::orderBy('name')->get(['id', 'name']);

        return view('admin.ai-settings.index', compact('setting', 'linkedProjectIds', 'projectPrefixMap', 'allProjects'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'anthropic_key'          => 'nullable|string|max:200',
            'openai_key'             => 'nullable|string|max:200',
            'figma_token'            => 'nullable|string|max:200',
            'manus_key'              => 'nullable|string|max:200',
            'manus_endpoint'         => 'nullable|url|max:300',
            'withworks_github_token' => 'nullable|string|max:300',
        ]);

        $s = AiSetting::current();
        // view 가 보내는 sentinel (변경되지 않은 마스킹 입력)
        $mask = '__MASKED__';

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
        if ($request->filled('withworks_github_token') && $request->withworks_github_token !== $mask) {
            $s->withworks_github_token = encrypt(trim($request->withworks_github_token));
        }
        if ($request->has('manus_endpoint')) {
            $s->manus_endpoint = trim($request->manus_endpoint) ?: null;
        }

        // 삭제 요청 처리
        if ($request->boolean('clear_anthropic'))  $s->anthropic_key           = null;
        if ($request->boolean('clear_openai'))     $s->openai_key              = null;
        if ($request->boolean('clear_figma'))      $s->figma_token             = null;
        if ($request->boolean('clear_manus'))      $s->manus_key               = null;
        if ($request->boolean('clear_withworks'))  $s->withworks_github_token  = null;

        $s->save();

        return redirect()->route('admin.ai-settings.index')
            ->with('success', '웍스 API 키 설정이 저장되었습니다.');
    }
}
