<?php

namespace App\Http\Controllers;

use App\Models\Agent\AiAgentArtifact;
use App\Models\Project;
use App\Services\Agent\VersioningService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AiVersionController extends Controller
{
    public function __construct(private VersioningService $versioning) {}

    public function history(Project $project, AiAgentArtifact $artifact): JsonResponse
    {
        abort_if($artifact->project_id !== $project->id, 404);

        $snapshots = $this->versioning->history($artifact);

        $content = $artifact->content;
        $current = [
            'version'        => $artifact->version,
            'is_current'     => true,
            'change_summary' => null,
            'created_at'     => $artifact->updated_at?->format('Y-m-d H:i:s'),
            'created_by_id'  => $artifact->updated_by ?? $artifact->created_by,
            'content_length' => mb_strlen(is_string($content) ? $content : json_encode($content)),
        ];

        $history = $snapshots->map(fn($v) => [
            'version'        => $v->version,
            'is_current'     => false,
            'change_summary' => $v->change_summary,
            'created_at'     => $v->created_at?->format('Y-m-d H:i:s'),
            'created_by_id'  => $v->created_by,
            'content_length' => mb_strlen(is_string($v->content) ? $v->content : json_encode($v->content)),
        ])->all();

        return response()->json([
            'artifact' => [
                'id'      => $artifact->id,
                'title'   => $artifact->title,
                'version' => $artifact->version,
            ],
            'versions' => array_merge([$current], $history),
        ]);
    }

    public function show(Project $project, AiAgentArtifact $artifact, int $version): JsonResponse
    {
        abort_if($artifact->project_id !== $project->id, 404);

        if ($artifact->version === $version) {
            $content = $artifact->content;
            return response()->json([
                'version'        => $version,
                'is_current'     => true,
                'content'        => is_string($content) ? $content : json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                'change_summary' => null,
                'created_at'     => $artifact->updated_at?->format('Y-m-d H:i:s'),
            ]);
        }

        $snapshot = $this->versioning->getVersion($artifact, $version);
        abort_if(!$snapshot, 404);

        $content = $snapshot->content;
        return response()->json([
            'version'        => $version,
            'is_current'     => false,
            'content'        => is_string($content) ? $content : json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'change_summary' => $snapshot->change_summary,
            'created_at'     => $snapshot->created_at?->format('Y-m-d H:i:s'),
        ]);
    }

    public function restore(Project $project, AiAgentArtifact $artifact, int $version): JsonResponse
    {
        abort_if($artifact->project_id !== $project->id, 404);
        abort_if($artifact->version === $version, 422, '이미 현재 버전입니다.');

        $artifact = $this->versioning->restore($artifact, $version, (int) auth()->user()?->id);

        return response()->json([
            'success'       => true,
            'new_version'   => $artifact->version,
            'restored_from' => $version,
        ]);
    }

    // ── Demo endpoints ────────────────────────────────────────────────

    public function demo(): View
    {
        return view('ai-agent.demo.version-traceability-demo');
    }

    public function demoHistory(int $artifactId): JsonResponse
    {
        // Artifact 2 = single-version (newly created artifact)
        if ($artifactId === 2) {
            return response()->json([
                'artifact' => ['id' => 2, 'title' => 'TO-BE 목표 정의서', 'version' => 1],
                'versions' => [
                    ['version' => 1, 'is_current' => true, 'change_summary' => null, 'created_at' => '2024-03-15 10:00:00', 'created_by_id' => 1, 'content_length' => 512],
                ],
            ]);
        }

        return response()->json([
            'artifact' => [
                'id'      => $artifactId,
                'title'   => 'AS-IS 업무 분석 보고서',
                'version' => 5,
            ],
            'versions' => [
                ['version' => 5, 'is_current' => true,  'change_summary' => null,                                          'created_at' => '2024-03-15 14:30:00', 'created_by_id' => 1, 'content_length' => 2847],
                ['version' => 4, 'is_current' => false, 'change_summary' => 'AS-IS 프로세스 분석 상세화 및 개선사항 도출',  'created_at' => '2024-03-14 16:15:00', 'created_by_id' => 1, 'content_length' => 2534],
                ['version' => 3, 'is_current' => false, 'change_summary' => '현행 시스템 제약사항 추가',                   'created_at' => '2024-03-13 11:20:00', 'created_by_id' => 2, 'content_length' => 2102],
                ['version' => 2, 'is_current' => false, 'change_summary' => '인터뷰 결과 반영 및 업무 흐름도 보완',         'created_at' => '2024-03-12 09:45:00', 'created_by_id' => 1, 'content_length' => 1876],
                ['version' => 1, 'is_current' => false, 'change_summary' => '초안 작성',                                  'created_at' => '2024-03-11 14:00:00', 'created_by_id' => 1, 'content_length' => 1245],
            ],
        ]);
    }

    public function demoVersionDetail(int $artifactId, int $version): JsonResponse
    {
        $contents = [
            5 => "# AS-IS 업무 분석 보고서\n\n## 1. 개요\n현행 업무 프로세스를 분석하여 개선 방향을 도출합니다.\n\n## 2. 현황 분석\n### 2.1 업무 흐름\n- 요청 접수 → 담당자 배정 → 처리 → 완료 확인\n- 평균 처리 시간: 3.2일\n- 병목 구간: 담당자 배정 단계 (평균 1.1일 소요)\n\n### 2.2 문제점\n1. 수동 배정으로 인한 지연\n2. 실시간 진행 현황 파악 불가\n3. 히스토리 관리 미흡\n4. 다중 채널 요청 통합 불가\n\n## 3. 개선 방향\n- 자동 배정 알고리즘 도입\n- 실시간 대시보드 구축\n- 통합 이력 관리 시스템 구축\n\n## 4. 제약사항\n- 레거시 시스템과의 연동 필요\n- 6개월 이내 구축 완료 요구",
            4 => "# AS-IS 업무 분석 보고서\n\n## 1. 개요\n현행 업무 프로세스를 분석하여 개선 방향을 도출합니다.\n\n## 2. 현황 분석\n### 2.1 업무 흐름\n- 요청 접수 → 담당자 배정 → 처리 → 완료 확인\n- 평균 처리 시간: 3.2일\n- 병목 구간: 담당자 배정 단계 (평균 1.1일 소요)\n\n### 2.2 문제점\n1. 수동 배정으로 인한 지연\n2. 실시간 진행 현황 파악 불가\n3. 히스토리 관리 미흡\n\n## 3. 개선 방향\n- 자동 배정 알고리즘 도입\n- 실시간 대시보드 구축\n- 통합 이력 관리 시스템 구축\n\n## 4. 제약사항\n- 레거시 시스템과의 연동 필요\n- 6개월 이내 구축 완료 요구",
            3 => "# AS-IS 업무 분석 보고서\n\n## 1. 개요\n현행 업무 프로세스를 분석합니다.\n\n## 2. 현황 분석\n### 2.1 업무 흐름\n- 요청 접수 → 담당자 배정 → 처리 → 완료 확인\n- 평균 처리 시간: 3.2일\n\n### 2.2 문제점\n1. 수동 배정으로 인한 지연\n2. 실시간 진행 현황 파악 불가\n\n## 3. 개선 방향\n- 자동 배정 알고리즘 도입\n- 실시간 대시보드 구축\n\n## 4. 제약사항\n- 레거시 시스템과의 연동 필요",
            2 => "# AS-IS 업무 분석 보고서\n\n## 1. 개요\n현행 업무 프로세스를 분석합니다.\n\n## 2. 현황 분석\n### 2.1 업무 흐름\n- 요청 접수 → 담당자 배정 → 처리 → 완료 확인\n- 평균 처리 시간: 3.5일\n\n### 2.2 문제점\n1. 수동 배정으로 인한 지연\n2. 실시간 진행 현황 파악 불가\n\n## 3. 개선 방향\n- 자동 배정 알고리즘 도입",
            1 => "# AS-IS 업무 분석 보고서\n\n## 1. 개요\n현행 업무 프로세스를 분석합니다.\n\n## 2. 현황 분석\n### 2.1 업무 흐름\n- 요청 접수 → 처리 → 완료\n- 평균 처리 시간: 4일\n\n## 3. 문제점\n1. 처리 지연\n2. 관리 어려움",
        ];

        return response()->json([
            'version'        => $version,
            'is_current'     => $version === 5,
            'content'        => $contents[$version] ?? '내용 없음',
            'change_summary' => $version === 5 ? null : '변경 요약 v' . $version,
            'created_at'     => '2024-03-' . sprintf('%02d', 10 + $version) . ' 14:00:00',
        ]);
    }
}
