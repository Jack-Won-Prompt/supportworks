<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AiAgentController extends Controller
{
    // ─────────────────────────────────────────────────────────────────────────
    // 대시보드 (T15)
    // ─────────────────────────────────────────────────────────────────────────

    public function dashboard(): View
    {
        $projects = auth()->user()->isAdmin()
            ? Project::orderBy('name')->get()
            : auth()->user()->projects()->orderBy('name')->get();

        return view('ai-agent.dashboard', compact('projects'));
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 프로젝트 홈 (T15)
    // ─────────────────────────────────────────────────────────────────────────

    public function projectHome(Project $project): View|RedirectResponse
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI Agent 프로젝트 홈',
            'stageLabel'  => null,
            'description' => '프로젝트의 AI Agent 개발 워크플로우 진입점. 각 단계(기획→디자인→개발 준비→개발→릴리즈)로 이동합니다.',
            'taskId'      => 'T15',
            'specSection' => '3.1',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 기획 단계
    // ─────────────────────────────────────────────────────────────────────────

    public function planningIndex(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '기획 단계',
            'stageLabel'  => '단계 1: 기획',
            'description' => 'AS-IS 분석부터 AI 기획서 작성, 화면 흐름도, 목업까지 기획 산출물 전체를 AI로 생성합니다.',
            'taskId'      => 'T16',
            'specSection' => '3.2',
        ]);
    }

    public function asIs(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AS-IS 분석',
            'stageLabel'  => '단계 1: 기획',
            'description' => '현황 자료(텍스트/이미지/Excel/PPT/PDF)를 AI로 분석하여 핵심 이슈와 문제점을 추출합니다.',
            'taskId'      => 'T18',
            'specSection' => '3.2.2',
        ]);
    }

    public function toBe(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'TO-BE 요구사항 분석',
            'stageLabel'  => '단계 1: 기획',
            'description' => 'AS-IS 분석 결과를 기반으로 TO-BE 요구사항을 REQ-XXX ID로 자동 추출하고 MoSCoW 우선순위를 분류합니다.',
            'taskId'      => 'T19',
            'specSection' => '3.2.3',
        ]);
    }

    public function gap(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'Gap 분석',
            'stageLabel'  => '단계 1: 기획',
            'description' => 'AS-IS와 TO-BE를 비교하여 개선이 필요한 갭을 AI로 분석하고 우선순위를 제시합니다.',
            'taskId'      => 'T20',
            'specSection' => '3.2.4',
        ]);
    }

    public function document(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI 기획서 작성',
            'stageLabel'  => '단계 1: 기획',
            'description' => 'AS-IS·TO-BE·Gap 분석 결과를 종합하여 표준 기획서를 자동 작성하고 편집·승인합니다.',
            'taskId'      => 'T22',
            'specSection' => '3.2.5',
        ]);
    }

    public function ia(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'IA / 화면 흐름도',
            'stageLabel'  => '단계 1: 기획',
            'description' => '승인된 기획서를 기반으로 IA 구조와 화면 흐름도(Mermaid)를 자동 생성합니다.',
            'taskId'      => 'T23',
            'specSection' => '3.2.6',
        ]);
    }

    public function planningPrompts(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '화면 생성 프롬프트',
            'stageLabel'  => '단계 1: 기획',
            'description' => 'SCR-XXX 화면 ID별로 AI 목업 생성에 사용할 프롬프트를 자동 작성하고 편집합니다.',
            'taskId'      => 'T24',
            'specSection' => '3.2.7',
        ]);
    }

    public function mockups(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI 샘플 화면(목업)',
            'stageLabel'  => '단계 1: 기획',
            'description' => '화면 프롬프트를 기반으로 프로젝트 스택(HTML/React/Vue)에 맞는 목업을 자동 생성하고 피드백으로 개선합니다.',
            'taskId'      => 'T25',
            'specSection' => '3.2.8',
        ]);
    }

    public function planningApproval(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '기획 단계 승인',
            'stageLabel'  => '단계 1: 기획',
            'description' => '기획 단계 전체 산출물을 검토하고 프로젝트 매니저가 승인하면 디자인 단계가 활성화됩니다.',
            'taskId'      => 'T26',
            'specSection' => '3.2.9',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 디자인 단계
    // ─────────────────────────────────────────────────────────────────────────

    public function designIndex(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '디자인 단계',
            'stageLabel'  => '단계 2: 디자인',
            'description' => 'Figma 연동으로 Design Token, 컴포넌트 명세서, 디자인 시스템 문서를 자동 생성합니다.',
            'taskId'      => 'T27',
            'specSection' => '3.3',
        ]);
    }

    public function designTokens(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'Design Token',
            'stageLabel'  => '단계 2: 디자인',
            'description' => 'Figma에서 Color, Typography, Spacing 등 디자인 토큰을 JSON으로 자동 추출하고 관리합니다.',
            'taskId'      => 'T28',
            'specSection' => '3.3.1',
        ]);
    }

    public function designComponents(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'Component 명세서',
            'stageLabel'  => '단계 2: 디자인',
            'description' => 'Figma 컴포넌트 라이브러리에서 Props, Variants, 사용 가이드를 자동 문서화합니다.',
            'taskId'      => 'T29',
            'specSection' => '3.3.2',
        ]);
    }

    public function designLayout(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '표준 Layout / Grid',
            'stageLabel'  => '단계 2: 디자인',
            'description' => '프로젝트 표준 레이아웃과 그리드 시스템을 정의하고 각 화면에 적용합니다.',
            'taskId'      => 'T30',
            'specSection' => '3.3.3',
        ]);
    }

    public function designScreens(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '화면별 디자인 매핑',
            'stageLabel'  => '단계 2: 디자인',
            'description' => 'SCR-XXX 화면 ID와 Figma 프레임을 매핑하여 추적성 링크를 자동 생성합니다.',
            'taskId'      => 'T31',
            'specSection' => '3.3.4',
        ]);
    }

    public function designValidation(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '디자인 일관성 AI 검수',
            'stageLabel'  => '단계 2: 디자인',
            'description' => '디자인 토큰 이탈, 미사용 컴포넌트, 레이아웃 이탈 항목을 AI로 자동 검출하고 리포트를 생성합니다.',
            'taskId'      => 'T32',
            'specSection' => '3.3.5',
        ]);
    }

    public function designSystem(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '디자인 시스템 문서',
            'stageLabel'  => '단계 2: 디자인',
            'description' => '토큰·컴포넌트·레이아웃을 종합한 디자인 시스템 문서를 자동 생성합니다.',
            'taskId'      => 'T33',
            'specSection' => '3.3.6',
        ]);
    }

    public function figmaDev(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'Figma Dev Mode URL',
            'stageLabel'  => '단계 2: 디자인',
            'description' => '각 SCR-XXX 화면의 Figma Dev Mode URL을 자동 수집·관리하여 개발자 핸드오프를 지원합니다.',
            'taskId'      => 'T34',
            'specSection' => '3.3.7',
        ]);
    }

    public function designApproval(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '디자인 단계 승인',
            'stageLabel'  => '단계 2: 디자인',
            'description' => '디자인 산출물 전체를 검토하고 승인하면 개발 준비 단계가 활성화됩니다.',
            'taskId'      => 'T35',
            'specSection' => '3.3.8',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 개발 준비 단계
    // ─────────────────────────────────────────────────────────────────────────

    public function preDevIndex(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '개발 준비 단계',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => 'ERD, API 명세서, 권한 모델을 AI로 자동 생성하고 코드 생성을 준비합니다.',
            'taskId'      => 'T36',
            'specSection' => '3.4',
        ]);
    }

    public function erd(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '데이터 모델 (ERD)',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => '기획서와 화면 정의서를 기반으로 ERD 초안을 자동 생성하고 Mermaid로 시각화합니다.',
            'taskId'      => 'T36',
            'specSection' => '3.4.1',
        ]);
    }

    public function apiSpec(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'API 명세 (OpenAPI)',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => 'ERD와 화면 정의서에서 OpenAPI 3.0 YAML을 자동 생성하고 Swagger UI로 미리보기합니다.',
            'taskId'      => 'T37',
            'specSection' => '3.4.2',
        ]);
    }

    public function rbac(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '권한 모델 (RBAC)',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => '역할-기능 권한 매트릭스를 AI로 자동 생성하고 편집합니다.',
            'taskId'      => 'T38',
            'specSection' => '3.4.3',
        ]);
    }

    public function codePrompts(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'Figma → AI 코드 프롬프트',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => '프로젝트 스택(HTML/React/Vue)에 맞는 코드 생성 프롬프트를 화면별로 자동 생성합니다.',
            'taskId'      => 'T39',
            'specSection' => '3.4.4',
        ]);
    }

    public function aiOutput(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI Output 생성 (Frontend)',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => '화면별 프롬프트로 프론트엔드 코드를 자동 생성하고 표준 폴더 구조에 배치합니다.',
            'taskId'      => 'T40',
            'specSection' => '3.4.5',
        ]);
    }

    public function preDevValidation(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI Output 검증',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => '디자인 일치도, 접근성, 반응형, 스택별 컨벤션 준수를 AI로 검증하고 리포트를 생성합니다.',
            'taskId'      => 'T41',
            'specSection' => '3.4.6',
        ]);
    }

    public function preDevApproval(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '개발 준비 단계 승인',
            'stageLabel'  => '단계 3: 개발 준비',
            'description' => '개발 준비 산출물 전체를 검토하고 승인하면 개발 단계가 활성화됩니다.',
            'taskId'      => 'T42',
            'specSection' => '3.4.7',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 개발 단계
    // ─────────────────────────────────────────────────────────────────────────

    public function devIndex(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '개발 단계',
            'stageLabel'  => '단계 4: 개발',
            'description' => 'Backend 코드 생성, API 연계, AI 코드 리뷰, AI 추가 수정을 순서대로 진행합니다.',
            'taskId'      => 'T43',
            'specSection' => '3.5',
        ]);
    }

    public function backend(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'Backend 개발',
            'stageLabel'  => '단계 4: 개발',
            'description' => 'ERD와 API 명세서를 기반으로 Backend 코드를 AI로 자동 생성합니다.',
            'taskId'      => 'T43',
            'specSection' => '3.5.1',
        ]);
    }

    public function apiConnect(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'API 연계',
            'stageLabel'  => '단계 4: 개발',
            'description' => 'Frontend와 Backend API를 연결하는 호출 코드를 자동 생성하고 통합합니다.',
            'taskId'      => 'T44',
            'specSection' => '3.5.2',
        ]);
    }

    public function codeReview(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI 코드 리뷰',
            'stageLabel'  => '단계 4: 개발',
            'description' => '보안·성능·컨벤션 관점에서 AI 코드 리뷰를 수행하고 리포트를 생성합니다.',
            'taskId'      => 'T45',
            'specSection' => '3.5.3',
        ]);
    }

    public function aiTasks(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI 추가 수정',
            'stageLabel'  => '단계 4: 개발',
            'description' => '추가 작업 요청을 AI에게 전달하고 코드 변경 이력을 자동으로 기록합니다.',
            'taskId'      => 'T46',
            'specSection' => '3.5.4',
        ]);
    }

    public function devApproval(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '개발 단계 승인',
            'stageLabel'  => '단계 4: 개발',
            'description' => '개발 완료 산출물을 검토하고 승인하면 릴리즈 패키지 생성이 가능합니다.',
            'taskId'      => 'T47',
            'specSection' => '3.5.5',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 릴리즈 패키지
    // ─────────────────────────────────────────────────────────────────────────

    public function release(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '릴리즈 패키지',
            'stageLabel'  => '릴리즈',
            'description' => '전체 산출물을 표준 폴더 구조로 패키징하고 ZIP으로 다운로드합니다. manifest.json과 README.md가 자동 생성됩니다.',
            'taskId'      => 'T48',
            'specSection' => '3.6',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 공통 기능
    // ─────────────────────────────────────────────────────────────────────────

    public function traceability(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '추적성 매트릭스',
            'stageLabel'  => '공통 기능',
            'description' => 'REQ → 화면 → 컴포넌트 → API → 코드까지 전체 추적성 매트릭스를 Excel로 출력합니다.',
            'taskId'      => 'T49',
            'specSection' => '3.7.1',
        ]);
    }

    public function versions(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '버전 이력',
            'stageLabel'  => '공통 기능',
            'description' => '각 산출물의 버전 이력을 조회하고 이전 버전으로 복구할 수 있습니다.',
            'taskId'      => 'T14',
            'specSection' => '3.7.2',
        ]);
    }

    public function commonPrompts(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '프롬프트 라이브러리',
            'stageLabel'  => '공통 기능',
            'description' => '단계별 AI 프롬프트 템플릿을 등록·조회·편집하고 버전 관리합니다.',
            'taskId'      => 'T08',
            'specSection' => '3.7.3',
        ]);
    }

    public function usage(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => 'AI 사용량 / 비용',
            'stageLabel'  => '공통 기능',
            'description' => '프로젝트별 AI 호출 횟수, 토큰 사용량, 누적 비용을 조회합니다.',
            'taskId'      => 'T07',
            'specSection' => '3.7.4',
        ]);
    }

    public function permissions(Project $project): View
    {
        $this->authorizeProject($project);

        return $this->placeholder($project, [
            'pageTitle'   => '권한 관리',
            'stageLabel'  => '공통 기능',
            'description' => '프로젝트 AI Agent 메뉴의 접근 권한과 승인 권한자를 관리합니다.',
            'taskId'      => 'T51',
            'specSection' => '5.2',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 내부 헬퍼
    // ─────────────────────────────────────────────────────────────────────────

    private function authorizeProject(Project $project): void
    {
        if (auth()->user()->isAdmin()) {
            return;
        }

        abort_unless(
            ProjectMember::where('project_id', $project->id)
                ->where('user_id', auth()->id())
                ->exists(),
            403,
            '해당 프로젝트에 접근 권한이 없습니다.'
        );
    }

    private function placeholder(Project $project, array $meta): View
    {
        return view('ai-agent.placeholder', array_merge(['project' => $project], $meta));
    }
}
