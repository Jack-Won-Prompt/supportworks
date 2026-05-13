<?php

namespace App\Services;

use App\Models\AiMessage;
use App\Models\AiProjectFile;
use App\Models\AiSession;
use Illuminate\Support\Collection;

class AiFileService
{
    private const MAX_CONTEXT_CHARS = 6000;

    /**
     * 웍스 메시지에서 생성된 코드를 프로젝트 파일로 저장.
     * 같은 파일명이 이미 있으면 버전 증가 후 덮어씀.
     *
     * @return AiProjectFile[]
     */
    public function saveFromMessage(AiMessage $message, AiSession $session): array
    {
        if (!$session->project_id) return [];

        $uid  = $session->user_id;
        $pid  = $session->project_id;
        $sid  = $session->id;
        $lang = $message->code_lang ?? 'web';
        $saved = [];

        $files = $this->extractFiles($message, $lang);

        foreach ($files as $fileName => $data) {
            $existing = AiProjectFile::where('user_id', $uid)
                ->where('project_id', $pid)
                ->where('file_name', $fileName)
                ->first();

            if ($existing) {
                $existing->update([
                    'content'    => $data['content'],
                    'lang'       => $data['lang'],
                    'session_id' => $sid,
                    'version'    => $existing->version + 1,
                ]);
                $saved[] = $existing->fresh();
            } else {
                $saved[] = AiProjectFile::create([
                    'user_id'    => $uid,
                    'project_id' => $pid,
                    'session_id' => $sid,
                    'file_name'  => $fileName,
                    'lang'       => $data['lang'],
                    'content'    => $data['content'],
                    'version'    => 1,
                ]);
            }
        }

        return $saved;
    }

    /**
     * 프로젝트 파일 목록을 웍스 시스템 프롬프트용 컨텍스트 문자열로 변환.
     *
     * @param string $mode  'all'|'current'|'none'
     */
    public function buildContext(
        int $userId,
        int $projectId,
        string $mode = 'all',
        ?int $sessionId = null
    ): string {
        if ($mode === 'none') return '';

        $query = AiProjectFile::where('user_id', $userId)
            ->where('project_id', $projectId);

        if ($mode === 'current' && $sessionId) {
            $query->where('session_id', $sessionId);
        }

        $files = $query->orderByDesc('updated_at')->get();
        if ($files->isEmpty()) return '';

        $parts   = ["## 프로젝트 기존 소스 파일 ({$files->count()}개)"];
        $charSum = 0;

        foreach ($files as $file) {
            if ($charSum >= self::MAX_CONTEXT_CHARS) {
                $parts[] = "\n_(용량 초과로 이후 파일 생략)_";
                break;
            }
            $content  = mb_substr($file->content, 0, self::MAX_CONTEXT_CHARS - $charSum);
            $charSum += mb_strlen($content);
            $parts[]  = "\n### {$file->file_name} (v{$file->version}, {$file->lang})";
            $parts[]  = "```{$file->lang}\n{$content}\n```";
        }

        return implode("\n", $parts);
    }

    /**
     * 프로젝트에 저장된 파일 목록 반환 (UI 표시용).
     */
    public function getFiles(int $userId, int $projectId): Collection
    {
        return AiProjectFile::where('user_id', $userId)
            ->where('project_id', $projectId)
            ->orderBy('file_name')
            ->get(['id', 'file_name', 'lang', 'version', 'session_id', 'updated_at']);
    }

    // ── Private ───────────────────────────────────────────────

    private function extractFiles(AiMessage $message, string $lang): array
    {
        $files = [];

        if ($lang === 'web' || $lang === 'html') {
            if ($message->html_output) $files['index.html'] = ['content' => $message->html_output, 'lang' => 'html'];
            if ($message->css_output)  $files['style.css']  = ['content' => $message->css_output,  'lang' => 'css'];
            if ($message->js_output)   $files['script.js']  = ['content' => $message->js_output,   'lang' => 'js'];
        } elseif ($message->html_output) {
            $ext = $this->langToExt($lang);
            $files["main.{$ext}"] = ['content' => $message->html_output, 'lang' => $lang];
            if ($message->css_output) $files['style.css'] = ['content' => $message->css_output, 'lang' => 'css'];
        }

        return $files;
    }

    private function langToExt(string $lang): string
    {
        return match($lang) {
            'php'        => 'php',
            'python'     => 'py',
            'javascript' => 'js',
            'typescript' => 'ts',
            'java'       => 'java',
            'sql'        => 'sql',
            'bash'       => 'sh',
            'css'        => 'css',
            default      => $lang,
        };
    }
}
