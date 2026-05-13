<?php

namespace App\Http\Controllers;

use App\Models\AiSetting;
use App\Models\Project;
use App\Models\ProjectUrs;
use App\Services\AiOrchestrator;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;

class UrsController extends Controller
{
    private function orchestrator(): AiOrchestrator
    {
        $s = AiSetting::current();
        return new AiOrchestrator($s->anthropicKey(), $s->openaiKey(), $s->manusKey(), $s->manusEndpoint());
    }

    private function authorizeProject(Project $project): void
    {
        $user = auth()->user();
        if ($user->isAdmin()) return;
        if (!$project->isMember($user)) abort(403);
    }

    public function index(Project $project)
    {
        $this->authorizeProject($project);
        $urs = $project->urs()->first();
        if ($urs) {
            return redirect()->route('projects.urs.show', [$project, $urs]);
        }
        return view('urs.index', compact('project'));
    }

    public function store(Request $request, Project $project)
    {
        $this->authorizeProject($project);

        $existing = $project->urs()->first();
        if ($existing) {
            return redirect()->route('projects.urs.show', [$project, $existing]);
        }

        $urs = $project->urs()->create([
            'created_by' => auth()->id(),
            'status'     => 'draft',
        ]);

        return redirect()->route('projects.urs.show', [$project, $urs]);
    }

    public function show(Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);

        $planningDoc = $project->planningDocs()->first();

        return view('urs.show', compact('project', 'urs', 'planningDoc'));
    }

    public function update(Request $request, Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);

        $request->validate(['content' => 'required|string']);

        $urs->update([
            'content' => $request->content,
            'status'  => 'completed',
        ]);

        return response()->json(['ok' => true]);
    }

    /**
     * 기획서 내용을 기반으로 URS Q&A 질문 목록 생성
     */
    public function startQA(Request $request, Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);

        $planningDoc    = $project->planningDocs()->first();
        $planningContent = $planningDoc?->content ?? '';
        $projectName     = $project->name;

        $systemPrompt = <<<PROMPT
당신은 소프트웨어 요구사항 명세서(URS: User Requirements Specification) 작성 전문가입니다.
주어진 기획서를 분석하여 URS 작성에 필요한 핵심 질문 10개를 생성하세요.
각 질문에는 기획서 내용을 기반으로 한 웍스 추천 답변도 함께 제공하세요.

반드시 아래 JSON 배열 형식만 반환하세요. 다른 텍스트는 절대 포함하지 마세요:
[
  {"q": "질문 내용", "ai_suggestion": "웍스 추천 답변"},
  ...
]

다음 항목들을 커버하는 질문을 작성하세요:
1. 시스템/서비스 개요 및 목적
2. 주요 대상 사용자 및 역할
3. 핵심 기능 요구사항 (3~5개)
4. 비기능 요구사항 (성능, 보안, 가용성)
5. 시스템 제약 조건 (기술 스택, 플랫폼 등)
6. 외부 인터페이스/연동 요구사항
7. 데이터 요구사항 (데이터 모델, 보존 정책)
8. 품질 속성 (사용성, 유지보수성 등)
9. 인수 기준 (테스트 및 완료 조건)
10. 우선순위 및 단계별 구현 계획
PROMPT;

        $messages = [[
            'role'    => 'user',
            'content' => "프로젝트명: {$projectName}\n\n기획서 내용:\n{$planningContent}\n\n위 기획서를 분석하여 URS 작성 질문 10개와 각각의 웍스 추천 답변을 JSON 배열로 반환하세요.",
        ]];

        try {
            $result = $this->orchestrator()->chatRaw($messages, $systemPrompt);
            $text   = trim($result['text']);

            // JSON 추출 (마크다운 코드블록 제거)
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);

            $questions = json_decode($text, true);

            if (!is_array($questions) || empty($questions)) {
                throw new \RuntimeException('웍스 질문 생성 실패: 올바른 JSON 응답을 받지 못했습니다.');
            }

            // 필드 정규화
            $questions = array_map(fn($q) => [
                'q'             => $q['q'] ?? $q['question'] ?? '질문',
                'ai_suggestion' => $q['ai_suggestion'] ?? $q['suggestion'] ?? '',
                'answer'        => null,
            ], $questions);

            $urs->update([
                'status'          => 'qa_in_progress',
                'qa_questions'    => $questions,
                'current_q_index' => 0,
            ]);

            return response()->json([
                'ok'           => true,
                'question'     => $questions[0]['q'],
                'ai_suggestion'=> $questions[0]['ai_suggestion'],
                'index'        => 0,
                'total'        => count($questions),
                'provider'     => $result['provider'],
            ]);

        } catch (\Throwable $e) {
            Log::error('[URS] startQA 실패: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * 현재 질문에 답변 저장 → 다음 질문 반환 또는 완료
     */
    public function answerQA(Request $request, Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);

        $request->validate([
            'answer' => 'required|string|max:2000',
            'index'  => 'required|integer|min:0',
        ]);

        $questions = $urs->qa_questions ?? [];
        $idx       = (int) $request->index;

        if (!isset($questions[$idx])) {
            return response()->json(['ok' => false, 'message' => '잘못된 인덱스'], 422);
        }

        $questions[$idx]['answer'] = $request->answer;
        $nextIdx = $idx + 1;

        $urs->update([
            'qa_questions'    => $questions,
            'current_q_index' => $nextIdx,
        ]);

        if ($nextIdx >= count($questions)) {
            $urs->update(['status' => 'generating']);
            return response()->json(['ok' => true, 'done' => true, 'total' => count($questions)]);
        }

        return response()->json([
            'ok'            => true,
            'done'          => false,
            'question'      => $questions[$nextIdx]['q'],
            'ai_suggestion' => $questions[$nextIdx]['ai_suggestion'],
            'index'         => $nextIdx,
            'total'         => count($questions),
        ]);
    }

    /**
     * Q&A 결과를 바탕으로 URS Markdown 생성
     */
    public function generateURS(Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);

        $questions   = $urs->qa_questions ?? [];
        $projectName = $project->name;
        $today       = now()->format('Y년 m월 d일');

        $qaPairs = collect($questions)->map(function ($q, $i) {
            $answer = $q['answer'] ?? $q['ai_suggestion'] ?? '';
            return 'Q' . ($i + 1) . '. ' . $q['q'] . "\n답변: " . $answer;
        })->implode("\n\n");

        $systemPrompt = <<<PROMPT
당신은 소프트웨어 요구사항 명세서(URS) 작성 전문가입니다.
아래 Q&A 내용을 바탕으로 완전하고 전문적인 URS 문서를 Markdown 형식으로 작성하세요.

문서 구조를 반드시 아래와 같이 구성하세요:
# URS: [프로젝트명]
**문서 버전**: 1.0  **작성일**: [날짜]  **상태**: 초안

---

## 1. 문서 개요
### 1.1 목적
### 1.2 범위
### 1.3 용어 정의

## 2. 시스템 개요
### 2.1 시스템 배경 및 목적
### 2.2 시스템 경계

## 3. 이해관계자 및 사용자 정의
### 3.1 이해관계자
### 3.2 사용자 역할 및 특성

## 4. 기능 요구사항
> 각 요구사항: **ID**, 설명, 우선순위, 검증 방법

## 5. 비기능 요구사항
### 5.1 성능 요구사항
### 5.2 보안 요구사항
### 5.3 가용성 및 신뢰성
### 5.4 유지보수성

## 6. 제약 조건
### 6.1 기술적 제약
### 6.2 비즈니스 제약

## 7. 인터페이스 요구사항
### 7.1 외부 시스템 연동
### 7.2 API 요구사항

## 8. 데이터 요구사항
### 8.1 데이터 모델 개요
### 8.2 데이터 보존 정책

## 9. 인수 기준
### 9.1 기능 인수 기준
### 9.2 성능 인수 기준

## 10. 구현 우선순위
> Phase 1 / Phase 2 / Phase 3 등으로 구분

---
*본 문서는 웍스 보조 작성 도구를 활용하여 생성되었습니다.*

Markdown만 반환하세요. 코드블록 없이 그대로 반환하세요.
PROMPT;

        $messages = [[
            'role'    => 'user',
            'content' => "프로젝트명: {$projectName}\n작성일: {$today}\n\n=== Q&A 내용 ===\n{$qaPairs}\n\n위 내용을 바탕으로 완전한 URS Markdown 문서를 작성하세요.",
        ]];

        try {
            $result  = $this->orchestrator()->chatRawLarge($messages, $systemPrompt);
            $content = trim($result['text']);

            // 혹시 코드블록으로 감싸진 경우 제거
            $content = preg_replace('/^```(?:markdown)?\s*/i', '', $content);
            $content = preg_replace('/\s*```$/', '', $content);

            $urs->update([
                'content' => $content,
                'status'  => 'completed',
            ]);

            return response()->json([
                'ok'       => true,
                'content'  => $content,
                'provider' => $result['provider'],
            ]);

        } catch (\Throwable $e) {
            Log::error('[URS] generateURS 실패: ' . $e->getMessage());
            $urs->update(['status' => 'qa_in_progress']);
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * URS 초기화 (다시 시작)
     */
    public function reset(Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);

        $urs->update([
            'status'          => 'draft',
            'qa_questions'    => null,
            'current_q_index' => 0,
            'content'         => null,
        ]);

        return response()->json(['ok' => true]);
    }

    // ── Word 다운로드 ───────────────────────────────────────────────
    public function downloadWord(Request $request, Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);
        abort_if(!$urs->content, 404, 'URS 문서가 없습니다.');

        $isEn = $request->input('lang', 'ko') === 'en';
        $font = $isEn ? 'Calibri' : '맑은 고딕';

        if ($isEn) {
            abort_if(!$urs->content_en, 422, '영어 번역본이 없습니다. 먼저 번역을 생성해주세요.');
        }

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName($font);
        $phpWord->setDefaultFontSize(11);

        $phpWord->addTitleStyle(0, ['name'=>$font,'size'=>22,'bold'=>true,'color'=>'312E81'], ['alignment'=>Jc::CENTER,'spaceAfter'=>280]);
        $phpWord->addTitleStyle(1, ['name'=>$font,'size'=>16,'bold'=>true,'color'=>'4F46E5'], ['spaceBefore'=>320,'spaceAfter'=>140,'borderBottomColor'=>'4F46E5','borderBottomSize'=>8]);
        $phpWord->addTitleStyle(2, ['name'=>$font,'size'=>13,'bold'=>true,'color'=>'0891B2'], ['spaceBefore'=>240,'spaceAfter'=>100]);
        $phpWord->addTitleStyle(3, ['name'=>$font,'size'=>11,'bold'=>true,'color'=>'374151'], ['spaceBefore'=>160,'spaceAfter'=>60]);
        $phpWord->addTitleStyle(4, ['name'=>$font,'size'=>10,'bold'=>true,'color'=>'4B5563'], ['spaceBefore'=>120,'spaceAfter'=>40]);

        $phpWord->addNumberingStyle('numList', [
            'type'   => 'multilevel',
            'levels' => [['pStyle'=>'List','format'=>'decimal','text'=>'%1.','left'=>360,'hanging'=>360,'tabPos'=>360]],
        ]);

        $section = $phpWord->addSection([
            'marginTop'    => 1440,
            'marginBottom' => 1440,
            'marginLeft'   => 1440,
            'marginRight'  => 1440,
        ]);
        $contentWidth = 9360;

        $footer = $section->addFooter();
        $footer->addPreserveText(
            'SupportWorks URS  |  ' . now()->format('Y.m.d') . '  |  {PAGE} / {NUMPAGES}',
            ['name'=>$font,'size'=>9,'color'=>'9CA3AF'],
            ['alignment'=>Jc::RIGHT]
        );

        $phpWord->addTableStyle('_UrsHeader', [
            'borderSize'=>0, 'borderColor'=>'4F46E5',
            'cellMarginTop'=>500,'cellMarginBottom'=>500,'cellMarginLeft'=>600,'cellMarginRight'=>600,
        ]);
        $htbl  = $section->addTable('_UrsHeader');
        $htbl->addRow(2000);
        $hcell = $htbl->addCell($contentWidth, ['bgColor'=>'4338CA','valign'=>'center']);
        $hcell->addText('SupportWorks', ['name'=>$font,'size'=>9,'color'=>'C7D2FE'], ['spaceAfter'=>60]);
        $hcell->addText('User Requirements Specification', ['name'=>$font,'size'=>20,'bold'=>true,'color'=>'FFFFFF'], ['spaceAfter'=>80]);
        $hcell->addText(
            $this->sanitizeXml($project->name . '  ·  ' . now()->format('Y.m.d') . '  ·  v1.0'),
            ['name'=>$font,'size'=>9,'color'=>'C7D2FE']
        );

        $section->addTextBreak(1);

        $phpWord->addTableStyle('_UrsTable', [
            'borderSize'=>8,'borderColor'=>'C4B5FD',
            'cellMarginTop'=>100,'cellMarginBottom'=>100,'cellMarginLeft'=>120,'cellMarginRight'=>120,
        ]);

        $content = $isEn ? ($urs->content_en ?? '') : ($urs->content ?? '');
        $lines   = array_map(fn($l) => $this->sanitizeXml(rtrim($l)), explode("\n", $content));
        $n = count($lines);
        $i = 0;

        while ($i < $n) {
            $line = $lines[$i];

            if (str_starts_with($line, '|')) {
                $tableLines = [];
                while ($i < $n && str_starts_with($lines[$i], '|')) {
                    $tableLines[] = $lines[$i];
                    $i++;
                }
                $this->renderUrsTable($section, $tableLines, $contentWidth, $phpWord);
                continue;
            }

            if (str_starts_with($line, '#### '))      { $section->addTitle(ltrim(substr($line, 5)), 4); }
            elseif (str_starts_with($line, '### '))   { $section->addTitle(ltrim(substr($line, 4)), 3); }
            elseif (str_starts_with($line, '## '))    { $section->addTitle(ltrim(substr($line, 3)), 2); }
            elseif (str_starts_with($line, '# '))     { $section->addTitle(ltrim(substr($line, 2)), 1); }
            elseif (str_starts_with($line, '- ') || str_starts_with($line, '* ')) {
                $run = $section->addListItemRun(0);
                $this->addInlineMarkdown($run, substr($line, 2));
            } elseif (preg_match('/^\d+\. (.+)$/', $line, $m)) {
                $run = $section->addListItemRun(0, 'numList');
                $this->addInlineMarkdown($run, $m[1]);
            } elseif (str_starts_with($line, '> ')) {
                $qRun = $section->addTextRun(['indent'=>360,'spaceBefore'=>60,'spaceAfter'=>60,'borderLeftColor'=>'7C3AED','borderLeftSize'=>12]);
                $this->addInlineMarkdown($qRun, substr($line, 2));
            } elseif (preg_match('/^-{3,}$/', $line) || preg_match('/^\*{3,}$/', $line)) {
                $section->addTextBreak(1);
            } elseif ($line === '') {
                $section->addTextBreak(1);
            } else {
                $run = $section->addTextRun(['spaceAfter'=>60,'lineHeight'=>1.5]);
                $this->addInlineMarkdown($run, $line);
            }

            $i++;
        }

        $safeName = preg_replace('/[^\w\s가-힣-]/u', '', $project->name) ?: 'URS';
        $langSuffix = $isEn ? '_EN' : '';
        $fileName = $safeName . '_URS' . $langSuffix . '_' . now()->format('Ymd') . '.docx';
        $tmpPath  = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'urs_' . uniqid() . '.docx';

        $writer = WordIOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmpPath);

        return response()->download($tmpPath, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ])->deleteFileAfterSend(true);
    }

    // ── 영어 번역 생성 ──────────────────────────────────────────────
    public function translateToEnglish(Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);
        abort_if(!$urs->content, 422, 'URS 문서가 없습니다.');

        $systemPrompt = <<<'PROMPT'
You are a professional technical document translator.
Translate the given Korean Markdown fragment into English.

CRITICAL rules:
- Translate EVERY Korean word/sentence — heading text, paragraph body, list items, table cells, bold/italic text, dates (e.g. "2026년 05월 08일" → "May 8, 2026"), status labels. Leave absolutely NO Korean characters in the output.
- Preserve ALL Markdown syntax characters exactly (##, ###, **, *, |, -, >, backticks, etc.)
- Do NOT summarize, shorten, or omit any content
- Do NOT add explanations or commentary
- Do NOT wrap output in code fences
- Return only the translated Markdown fragment
PROMPT;

        $raw    = $urs->content;
        $chunks = $this->splitIntoTranslatableChunks($raw);

        $translatedParts = [];

        try {
            foreach ($chunks as $chunk) {
                if (trim($chunk) === '') continue;

                $messages = [['role' => 'user', 'content' => trim($chunk)]];

                $result = $this->orchestrator()->chatRawLarge($messages, $systemPrompt);
                $part   = trim($result['text']);
                $part   = preg_replace('/^```(?:markdown)?\s*/iu', '', $part);
                $part   = preg_replace('/\s*```\s*$/u', '', $part);

                $translatedParts[] = $part;
            }

            $contentEn = implode("\n\n", $translatedParts);
            $urs->update(['content_en' => $contentEn]);

            return response()->json(['ok' => true]);
        } catch (\Throwable $e) {
            Log::error('[URS] translateToEnglish 실패: ' . $e->getMessage());
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * URS 마크다운을 ### 소섹션 단위로 분할.
     * 각 청크가 웍스 한 번 호출에 완전히 번역될 수 있는 크기로 유지.
     */
    private function splitIntoTranslatableChunks(string $raw): array
    {
        // ### 경계마다 분할 (lookahead 사용 → 구분자 자체가 각 청크 앞에 포함됨)
        $parts = preg_split('/(?=\n###? )/u', $raw, -1, PREG_SPLIT_NO_EMPTY);

        $chunks  = [];
        $current = '';

        foreach ($parts as $part) {
            // 약 2000자 초과 시 현재 청크를 확정하고 새로 시작
            if ($current !== '' && (mb_strlen($current) + mb_strlen($part)) > 2000) {
                $chunks[] = $current;
                $current  = $part;
            } else {
                $current .= ($current === '' ? '' : "\n") . ltrim($part, "\n");
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return $chunks;
    }

    // ── PDF 다운로드 ────────────────────────────────────────────────
    public function downloadPdf(Project $project, ProjectUrs $urs)
    {
        $this->authorizeProject($project);
        abort_if($urs->project_id !== $project->id, 404);
        abort_if(!$urs->content, 404, 'URS 문서가 없습니다.');

        $content     = $urs->content ?? '';
        $projectName = $project->name;
        $today       = now()->format('Y년 m월 d일');

        $html = $this->buildPdfHtml($content, $projectName, $today);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('chroot', base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $safeName = preg_replace('/[^\w\s가-힣-]/u', '', $projectName) ?: 'URS';
        $fileName = $safeName . '_URS_' . now()->format('Ymd') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function buildPdfHtml(string $mdContent, string $projectName, string $today): string
    {
        $body = $this->markdownToHtml($mdContent);

        return <<<HTML
<!DOCTYPE html>
<html lang="ko">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  @page { margin: 18mm 16mm 18mm 16mm; }
  body { font-family: "DejaVu Sans", sans-serif; font-size: 9.5pt; color: #1f2937; line-height: 1.7; }

  /* 헤더 블록 */
  .doc-header { background: #4338ca; color: #fff; padding: 18pt 22pt; margin-bottom: 18pt; border-radius: 4pt; }
  .doc-header .sys { font-size: 7.5pt; color: #c7d2fe; margin-bottom: 4pt; }
  .doc-header h1 { font-size: 18pt; font-weight: bold; color: #fff; margin-bottom: 6pt; }
  .doc-header .meta { font-size: 7.5pt; color: #c7d2fe; }

  /* 제목 */
  h1 { font-size: 16pt; font-weight: bold; color: #312e81; border-bottom: 2pt solid #4f46e5; padding-bottom: 5pt; margin: 18pt 0 10pt; }
  h2 { font-size: 12pt; font-weight: bold; color: #1d4ed8; border-bottom: 1pt solid #bfdbfe; padding-bottom: 4pt; margin: 16pt 0 8pt; }
  h3 { font-size: 10.5pt; font-weight: bold; color: #374151; margin: 12pt 0 5pt; }
  h4 { font-size: 9.5pt; font-weight: bold; color: #4b5563; margin: 8pt 0 4pt; }

  p { margin-bottom: 6pt; }
  ul, ol { padding-left: 16pt; margin-bottom: 6pt; }
  li { margin-bottom: 2pt; }

  strong { font-weight: bold; color: #111827; }
  em { font-style: italic; }
  code { background: #f3f4f6; padding: 1pt 4pt; border-radius: 2pt; font-size: 8.5pt; color: #7c3aed; }

  blockquote { border-left: 3pt solid #7c3aed; padding: 5pt 10pt; background: #faf9ff; color: #6b7280; margin: 6pt 0; font-size: 9pt; }

  hr { border: none; border-top: 1pt solid #e5e7eb; margin: 12pt 0; }

  /* 테이블 - 인쇄 폭에 꽉 차게 */
  table { width: 100%; border-collapse: collapse; margin: 8pt 0 12pt; font-size: 8.5pt; page-break-inside: avoid; }
  thead tr { background: #ede9fe; }
  th { padding: 6pt 8pt; text-align: left; font-weight: bold; color: #4c1d95; border: 0.5pt solid #c4b5fd; }
  td { padding: 5pt 8pt; border: 0.5pt solid #e5e7eb; color: #374151; vertical-align: top; }
  tr:nth-child(even) td { background: #faf9ff; }

  /* 페이지 번호 푸터 */
  .page-footer { position: fixed; bottom: 8mm; right: 16mm; font-size: 7.5pt; color: #9ca3af; }
</style>
</head>
<body>
<div class="doc-header">
  <div class="sys">SupportWorks</div>
  <h1>User Requirements Specification</h1>
  <div class="meta">{$projectName} &nbsp;·&nbsp; {$today} &nbsp;·&nbsp; v1.0</div>
</div>
{$body}
</body>
</html>
HTML;
    }

    private function markdownToHtml(string $md): string
    {
        $lines  = explode("\n", $md);
        $html   = '';
        $i      = 0;
        $n      = count($lines);

        while ($i < $n) {
            $line = rtrim($lines[$i]);

            // 테이블 블록
            if (str_starts_with($line, '|')) {
                $tLines = [];
                while ($i < $n && str_starts_with(rtrim($lines[$i]), '|')) {
                    $tLines[] = rtrim($lines[$i]);
                    $i++;
                }
                $html .= $this->buildHtmlTable($tLines);
                continue;
            }

            if (preg_match('/^#{4} (.+)$/', $line, $m))      { $html .= '<h4>' . $this->inlineHtml($m[1]) . '</h4>'; }
            elseif (preg_match('/^#{3} (.+)$/', $line, $m))  { $html .= '<h3>' . $this->inlineHtml($m[1]) . '</h3>'; }
            elseif (preg_match('/^#{2} (.+)$/', $line, $m))  { $html .= '<h2>' . $this->inlineHtml($m[1]) . '</h2>'; }
            elseif (preg_match('/^# (.+)$/', $line, $m))     { $html .= '<h1>' . $this->inlineHtml($m[1]) . '</h1>'; }
            elseif (preg_match('/^[-*] (.+)$/', $line, $m))  { $html .= '<ul><li>' . $this->inlineHtml($m[1]) . '</li></ul>'; }
            elseif (preg_match('/^\d+\. (.+)$/', $line, $m)) { $html .= '<ol><li>' . $this->inlineHtml($m[1]) . '</li></ol>'; }
            elseif (preg_match('/^> (.+)$/', $line, $m))     { $html .= '<blockquote>' . $this->inlineHtml($m[1]) . '</blockquote>'; }
            elseif (preg_match('/^-{3,}$/', $line))           { $html .= '<hr>'; }
            elseif ($line === '')                             { $html .= '<p>&nbsp;</p>'; }
            else                                             { $html .= '<p>' . $this->inlineHtml($line) . '</p>'; }

            $i++;
        }

        // 인접한 <ul><li> 병합
        $html = preg_replace('/<\/ul>\s*<ul>/', '', $html);
        $html = preg_replace('/<\/ol>\s*<ol>/', '', $html);

        return $html;
    }

    private function buildHtmlTable(array $lines): string
    {
        $rows = [];
        foreach ($lines as $line) {
            if (preg_match('/^\|[-|: ]+\|$/', $line)) continue;
            $cells = array_map('trim', explode('|', trim($line, '|')));
            if (array_filter($cells)) $rows[] = $cells;
        }
        if (!$rows) return '';

        $html = '<table><thead><tr>';
        foreach ($rows[0] as $cell) {
            $html .= '<th>' . htmlspecialchars($cell, ENT_QUOTES) . '</th>';
        }
        $html .= '</tr></thead><tbody>';
        foreach (array_slice($rows, 1) as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . $this->inlineHtml($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        return $html . '</tbody></table>';
    }

    private function inlineHtml(string $text): string
    {
        $text = htmlspecialchars($text, ENT_QUOTES);
        $text = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        $text = preg_replace('/\*(.+?)\*/',     '<em>$1</em>',         $text);
        $text = preg_replace('/`(.+?)`/',       '<code>$1</code>',     $text);
        return $text;
    }

    private function renderUrsTable(object $section, array $lines, int $contentWidth, PhpWord $phpWord): void
    {
        $rows = [];
        foreach ($lines as $line) {
            if (preg_match('/^\|[-|: ]+\|$/', $line)) continue;
            $cells = array_map('trim', explode('|', trim($line, '|')));
            if (array_filter($cells)) $rows[] = $cells;
        }
        if (!$rows) return;

        $colCount = max(array_map('count', $rows));
        if ($colCount === 0) return;
        $colWidth = (int)($contentWidth / $colCount);

        $tblStyle = '_UrsTable_' . uniqid();
        $phpWord->addTableStyle($tblStyle, [
            'borderSize'=>8,'borderColor'=>'C4B5FD',
            'cellMarginTop'=>100,'cellMarginBottom'=>100,'cellMarginLeft'=>120,'cellMarginRight'=>120,
        ]);
        $table = $section->addTable($tblStyle);

        foreach ($rows as $ri => $cells) {
            $isHeader = ($ri === 0);
            $table->addRow(400);
            for ($ci = 0; $ci < $colCount; $ci++) {
                $cellText = $this->sanitizeXml($cells[$ci] ?? '');
                $bgColor  = $isHeader ? 'EDE9FE' : (($ri % 2 === 1) ? 'FAF9FF' : 'FFFFFF');
                $wcell    = $table->addCell($colWidth, ['bgColor'=>$bgColor,'borderSize'=>8,'borderColor'=>'C4B5FD']);
                $font     = $isHeader
                    ? ['name'=>'맑은 고딕','size'=>10,'bold'=>true,'color'=>'4C1D95']
                    : ['name'=>'맑은 고딕','size'=>10,'color'=>'374151'];
                $wcell->addText($cellText, $font, ['spaceAfter'=>0]);
            }
        }
        $section->addTextBreak(1);
    }

    private function sanitizeXml(string $text): string
    {
        return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $text) ?? $text;
    }

    private function addInlineMarkdown(object $run, string $text): void
    {
        $text  = $this->sanitizeXml($text);
        $parts = preg_split('/(\*\*[^*]+\*\*|\*[^*]+\*|`[^`]+`)/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $base  = ['name'=>'맑은 고딕','size'=>11,'color'=>'1F2937'];

        foreach ($parts as $part) {
            if (preg_match('/^\*\*(.+)\*\*$/s', $part, $m))     { $run->addText($m[1], array_merge($base, ['bold'=>true])); }
            elseif (preg_match('/^\*(.+)\*$/s', $part, $m))     { $run->addText($m[1], array_merge($base, ['italic'=>true])); }
            elseif (preg_match('/^`(.+)`$/s', $part, $m))       { $run->addText($m[1], array_merge($base, ['name'=>'Courier New','color'=>'6D28D9'])); }
            else                                                 { $run->addText($part, $base); }
        }
    }
}
