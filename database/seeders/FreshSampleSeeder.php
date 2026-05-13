<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\CompanyGroup;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\Schedule;
use App\Models\Question;
use App\Models\Answer;
use App\Models\CommunityPost;
use App\Models\CommunityComment;
use App\Models\ActionItem;
use App\Models\Task;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Memo;

class FreshSampleSeeder extends Seeder
{
    public function run(): void
    {
        // ── 0. 테이블 초기화 (users, admin 계열 제외) ─────────────────────
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        $truncate = [
            'community_reactions', 'community_votes', 'community_comments', 'community_posts',
            'answers', 'questions', 'comments', 'project_files', 'schedules',
            'project_members', 'projects', 'action_items', 'tasks',
            'messages', 'conversation_user', 'conversations',
            'memos', 'invitations', 'ai_messages', 'ai_sessions', 'figma_files',
            'company_groups',
        ];
        foreach ($truncate as $t) {
            DB::table($t)->truncate();
            $this->command->line("  truncated: {$t}");
        }
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        // ── 1. CompanyGroup 재생성 ─────────────────────────────────────────
        $cgSW = CompanyGroup::create([
            'name' => 'SupportWorks', 'code' => 'SUPPORTWORKS',
            'description' => 'SupportWorks 내부 개발팀', 'is_active' => true,
        ]);
        $cgTC = CompanyGroup::create([
            'name' => 'TechCore', 'code' => 'TECHCORE',
            'description' => 'TechCore 클라이언트 그룹', 'is_active' => true,
        ]);
        $cgFO = CompanyGroup::create([
            'name' => 'FinanceOne', 'code' => 'FINANCEONE',
            'description' => 'FinanceOne 클라이언트 그룹', 'is_active' => true,
        ]);

        // ── 2. 기존 유저 조회 및 company_group_id 배정 ───────────────────
        $admin   = User::where('role', 'admin')->first();
        $members = User::where('role', 'member')->get();
        $clients = User::where('role', 'client')->get();

        if (!$admin) {
            $this->command->error('admin 유저가 없습니다. DatabaseSeeder 먼저 실행하세요.');
            return;
        }

        $m1 = $members->get(0) ?? $admin;
        $m2 = $members->get(1) ?? $admin;
        $c1 = $clients->get(0);
        $c2 = $clients->get(1);

        // SupportWorks 그룹 배정 (admin + members)
        $swIds = collect([$admin->id])->merge($members->pluck('id'))->unique()->values();
        User::whereIn('id', $swIds)->update(['company_group_id' => $cgSW->id, 'company' => 'SupportWorks']);

        // 클라이언트 배정
        if ($c1) {
            $c1->update(['company_group_id' => $cgTC->id, 'company' => 'TechCore']);
        }
        if ($c2) {
            $c2->update(['company_group_id' => $cgFO->id, 'company' => 'FinanceOne']);
        }

        $this->command->info('CompanyGroups & users assigned.');

        // ── 3. 프로젝트 (6개) ────────────────────────────────────────────
        $now = Carbon::now();

        $p1 = Project::create([
            'name' => 'TechCore 이커머스 플랫폼 구축',
            'description' => '기존 온라인 쇼핑몰을 차세대 이커머스 플랫폼으로 전환. 결제, 재고, 고객 관리 모듈 일체 개발.',
            'status' => 'active', 'start_date' => '2026-03-01', 'end_date' => '2026-07-31',
            'created_by' => $admin->id, 'company_group_id' => $cgSW->id,
            'client_name' => 'TechCore', 'client_email' => 'pm@techcore.co.kr',
        ]);
        $p2 = Project::create([
            'name' => 'FinanceOne 투자 대시보드 개발',
            'description' => '실시간 주식·펀드 포트폴리오 시각화 대시보드. 알림, 수익률 분석, PDF 리포트 기능 포함.',
            'status' => 'active', 'start_date' => '2026-04-01', 'end_date' => '2026-08-31',
            'created_by' => $m1->id, 'company_group_id' => $cgSW->id,
            'client_name' => 'FinanceOne', 'client_email' => 'dev@financeone.com',
        ]);
        $p3 = Project::create([
            'name' => '사내 HR 셀프서비스 포털',
            'description' => '전자결재, 근태관리, 급여명세 조회, 교육이수 관리를 통합한 HR 포털 개발.',
            'status' => 'active', 'start_date' => '2026-02-15', 'end_date' => '2026-06-30',
            'created_by' => $admin->id, 'company_group_id' => $cgSW->id,
        ]);
        $p4 = Project::create([
            'name' => '레거시 PHP 시스템 마이그레이션',
            'description' => '10년 된 PHP4 레거시 코드를 Laravel + Vue3 스택으로 완전 이전. 데이터 무결성 유지 필수.',
            'status' => 'on_hold', 'start_date' => '2026-01-10', 'end_date' => '2026-09-30',
            'created_by' => $m1->id, 'company_group_id' => $cgSW->id,
        ]);
        $p5 = Project::create([
            'name' => '보안 취약점 진단 및 패치',
            'description' => 'OWASP Top 10 기준 취약점 전수 점검 및 패치. SQL Injection, XSS, CSRF 주요 대응.',
            'status' => 'completed', 'start_date' => '2026-01-02', 'end_date' => '2026-03-31',
            'created_by' => $admin->id, 'company_group_id' => $cgSW->id,
        ]);
        $p6 = Project::create([
            'name' => 'SupportWorks 모바일 앱 MVP',
            'description' => 'React Native 기반 모바일 앱. 프로젝트 현황, Q&A, 알림 기능. iOS/Android 동시 배포.',
            'status' => 'active', 'start_date' => '2026-04-15', 'end_date' => '2026-06-30',
            'created_by' => $m2->id, 'company_group_id' => $cgSW->id,
        ]);

        // ── 4. 프로젝트 멤버 ─────────────────────────────────────────────
        $pmData = [
            [$p1->id, $admin->id, 'manager'], [$p1->id, $m1->id, 'member'], [$p1->id, $m2->id, 'member'],
            [$p2->id, $m1->id, 'manager'], [$p2->id, $admin->id, 'member'], [$p2->id, $m2->id, 'member'],
            [$p3->id, $admin->id, 'manager'], [$p3->id, $m2->id, 'member'],
            [$p4->id, $m1->id, 'manager'], [$p4->id, $admin->id, 'viewer'],
            [$p5->id, $admin->id, 'manager'], [$p5->id, $m1->id, 'member'], [$p5->id, $m2->id, 'member'],
            [$p6->id, $m2->id, 'manager'], [$p6->id, $m1->id, 'member'],
        ];
        if ($c1) {
            $pmData[] = [$p1->id, $c1->id, 'viewer'];
        }
        if ($c2) {
            $pmData[] = [$p2->id, $c2->id, 'viewer'];
        }
        foreach ($pmData as [$pid, $uid, $role]) {
            ProjectMember::create(['project_id' => $pid, 'user_id' => $uid, 'role' => $role]);
        }

        // ── 5. 스케줄 (프로젝트당 6~7개 = 38개) ─────────────────────────
        $schedules = [
            // p1 - 이커머스
            [$p1->id, '요구사항 정의 및 WBS 작성', '비즈니스 요구사항 수집, 스코프 확정, 일정계획 수립', '2026-03-02', '2026-03-08', 'completed', 'high', $admin->id],
            [$p1->id, 'UI/UX 와이어프레임 설계', '고객 여정 맵핑, 주요 화면 와이어프레임 40여 장 제작', '2026-03-09', '2026-03-22', 'completed', 'high', $m2->id],
            [$p1->id, '결제 모듈 백엔드 개발', 'PG사 연동(토스, KG이니시스), 결제 이력 관리 API 구현', '2026-03-23', '2026-04-20', 'completed', 'high', $m1->id],
            [$p1->id, '상품·재고 관리 API 개발', '상품 등록/수정/삭제, 옵션 관리, 실시간 재고 동기화', '2026-04-01', '2026-04-30', 'in_progress', 'high', $m1->id],
            [$p1->id, '프론트엔드 구현 (React)', '상품 목록, 장바구니, 주문서, 마이페이지 화면 개발', '2026-04-15', '2026-05-31', 'in_progress', 'medium', $m2->id],
            [$p1->id, '통합 테스트 및 성능 최적화', 'E2E 테스트, 부하 테스트(목표: 동시 500명), Lighthouse 점수 90+ 달성', '2026-06-01', '2026-07-15', 'pending', 'medium', $admin->id],
            [$p1->id, '운영 서버 배포 및 인수인계', 'AWS 프로덕션 배포, CI/CD 파이프라인 구성, 운영 매뉴얼 작성', '2026-07-16', '2026-07-31', 'pending', 'high', $m1->id],

            // p2 - 투자 대시보드
            [$p2->id, '요구사항 분석 및 데이터 설계', '포트폴리오 데이터 모델 설계, 증권사 API 연동 방안 수립', '2026-04-02', '2026-04-10', 'completed', 'high', $m1->id],
            [$p2->id, '실시간 시세 연동 모듈 개발', '한국투자증권 API 연동, WebSocket 실시간 데이터 스트림 구현', '2026-04-11', '2026-04-28', 'in_progress', 'high', $m1->id],
            [$p2->id, '차트 컴포넌트 개발', 'TradingView Lightweight Charts 기반 캔들차트, 라인차트 컴포넌트', '2026-04-20', '2026-05-10', 'in_progress', 'medium', $m2->id],
            [$p2->id, '포트폴리오 분석 엔진 구현', '수익률 계산, 섹터 비중 분석, MDD 산출 로직 개발', '2026-05-01', '2026-05-31', 'pending', 'high', $m1->id],
            [$p2->id, 'PDF 리포트 자동 생성', 'Puppeteer 기반 월간 투자 리포트 PDF 자동 생성 기능', '2026-06-01', '2026-06-20', 'pending', 'medium', $m2->id],
            [$p2->id, '알림 시스템 구축', '목표가 도달, 급등/급락 알림 (이메일, 앱 푸시)', '2026-06-15', '2026-07-05', 'pending', 'medium', $admin->id],
            [$p2->id, 'UAT 및 안정화', '실사용자 10명 대상 UAT, 버그 수정, 최종 배포', '2026-07-20', '2026-08-31', 'pending', 'high', $m1->id],

            // p3 - HR 포털
            [$p3->id, '인사 데이터 이관 설계', '기존 엑셀 기반 인사 데이터 DB 이관 스크립트 작성', '2026-02-15', '2026-02-28', 'completed', 'high', $m1->id],
            [$p3->id, '전자결재 모듈 개발', '기안/검토/결재 워크플로우, PDF 변환, 결재선 관리', '2026-03-01', '2026-03-31', 'completed', 'high', $admin->id],
            [$p3->id, '근태관리 기능 구현', '출퇴근 기록, 연차/반차 신청, 초과근무 관리', '2026-04-01', '2026-04-30', 'in_progress', 'medium', $m2->id],
            [$p3->id, '급여명세서 조회 기능', '급여 항목별 내역, 연말정산 자료 PDF 다운로드', '2026-05-01', '2026-05-25', 'pending', 'medium', $m1->id],
            [$p3->id, '교육이수 관리 모듈', '온라인 교육 수강 이력, 이수증 발급, 필수 교육 알림', '2026-05-20', '2026-06-20', 'pending', 'low', $m2->id],

            // p4 - 마이그레이션
            [$p4->id, '레거시 코드 분석', '기존 PHP4 코드베이스 분석, 의존성 정리, 리스크 파악', '2026-01-10', '2026-02-15', 'completed', 'high', $m1->id],
            [$p4->id, 'DB 스키마 재설계', '정규화, 인덱스 전략, 마이그레이션 스크립트 작성', '2026-02-16', '2026-03-20', 'completed', 'high', $admin->id],
            [$p4->id, '핵심 비즈니스 로직 이전', '주문, 결제, 정산 로직 Laravel로 포팅, 단위 테스트 80%+ 커버리지', '2026-03-21', '2026-06-30', 'in_progress', 'high', $m1->id],
            [$p4->id, '데이터 이관 리허설', '스테이징 환경 데이터 이관, 무결성 검증 쿼리 실행', '2026-07-01', '2026-07-31', 'pending', 'high', $admin->id],

            // p5 - 보안 (completed)
            [$p5->id, 'OWASP ZAP 자동 스캔', '전체 엔드포인트 자동 취약점 스캔, High/Critical 항목 추출', '2026-01-02', '2026-01-15', 'completed', 'high', $admin->id],
            [$p5->id, 'SQL Injection 패치', '동적 쿼리 전수 검토, PreparedStatement 전환, WAF 룰 설정', '2026-01-16', '2026-02-10', 'completed', 'high', $m1->id],
            [$p5->id, 'XSS/CSRF 대응', '입출력 필터링, CSP 헤더 설정, CSRF 토큰 전면 적용', '2026-02-11', '2026-03-05', 'completed', 'high', $m2->id],
            [$p5->id, '보안 리포트 작성', '취약점 조치 결과 보고서, 재현 시나리오, 권고사항 문서화', '2026-03-06', '2026-03-31', 'completed', 'medium', $admin->id],

            // p6 - 모바일 앱
            [$p6->id, '기술 스택 선정 및 환경 구성', 'React Native + Expo, CI(GitHub Actions), Fastlane 자동 배포 설정', '2026-04-15', '2026-04-22', 'completed', 'medium', $m2->id],
            [$p6->id, '인증 및 푸시 알림 구현', 'JWT 로그인, 소셜 로그인(구글), FCM 푸시 알림 연동', '2026-04-23', '2026-05-10', 'in_progress', 'high', $m2->id],
            [$p6->id, '프로젝트 현황 화면 개발', '프로젝트 목록, 일정 캘린더, 멤버 현황 모바일 UI', '2026-05-01', '2026-05-20', 'pending', 'medium', $m1->id],
            [$p6->id, 'Q&A 및 알림 센터', '모바일 Q&A 작성/답변, 읽지 않은 알림 배지, 설정 화면', '2026-05-15', '2026-06-05', 'pending', 'medium', $m2->id],
            [$p6->id, 'TestFlight / 내부 테스트 배포', 'TestFlight iOS 배포, Google Play 내부 테스트 트랙 배포', '2026-06-10', '2026-06-25', 'pending', 'high', $admin->id],
        ];

        foreach ($schedules as [$pid, $title, $desc, $sd, $ed, $status, $priority, $assignedTo]) {
            Schedule::create([
                'project_id' => $pid, 'title' => $title, 'description' => $desc,
                'start_date' => $sd.' 09:00:00', 'end_date' => $ed.' 18:00:00',
                'status' => $status, 'priority' => $priority,
                'assigned_to' => $assignedTo, 'created_by' => $admin->id,
            ]);
        }

        $this->command->info('Schedules seeded: '.count($schedules));

        // ── 6. Q&A (35개) ────────────────────────────────────────────────
        $qaData = [
            // p1
            [$p1->id, $c1 ?? $admin, $m1, 'PG사 연동 시 테스트 환경은 어떻게 구성하나요?', '결제 모듈 개발 전 PG사(토스페이먼츠) 테스트 키를 발급받았습니다. 로컬 개발 환경에서 웹훅을 수신하려면 어떻게 해야 하나요? ngrok 사용이 필요한지 궁금합니다.', 'answered', '로컬에서 웹훅 수신에는 ngrok 또는 Cloudflare Tunnel을 권장합니다. 저희는 Cloudflare Tunnel을 사용하고 있으며, 명령어 하나로 공개 URL을 발급받을 수 있습니다. .env에 PAYMENT_WEBHOOK_URL로 등록해두시면 됩니다.'],
            [$p1->id, $c1 ?? $admin, $admin, '상품 이미지 업로드 용량 제한은?', '상품 대표 이미지와 상세 이미지를 각각 몇 개까지 올릴 수 있나요? 파일 크기 제한도 알려주세요. 고해상도 사진이 많아서요.', 'answered', '대표 이미지 1장(최대 5MB), 상세 이미지 최대 20장(장당 5MB)으로 제한하고 있습니다. 업로드 시 자동으로 WebP 변환 및 리사이징이 적용되어 실제 서빙 용량은 훨씬 줄어듭니다.'],
            [$p1->id, $c1 ?? $m1, $m1, '재고 부족 시 자동 알림 기능 가능한가요?', '재고가 특정 수량(예: 10개) 이하로 떨어질 때 담당자에게 이메일 또는 카카오 알림을 보내는 기능이 필요합니다. 구현 가능한지 확인 부탁드립니다.', 'answered', '가능합니다. 재고 임계값을 상품별로 설정할 수 있으며, 이메일 알림은 기본 제공됩니다. 카카오 알림은 카카오 비즈메시지 API 연동이 필요하며 별도 비용이 발생합니다. 우선 이메일로 진행하고 추후 카카오 연동을 검토하겠습니다.'],
            [$p1->id, $m2->id !== $admin->id ? $m2 : $m1, null, '모바일 결제 UI UX 개선 의견', '현재 와이어프레임의 결제 단계가 4단계인데, 모바일에서는 UX가 복잡하게 느껴집니다. 3단계(장바구니→주문확인→결제완료)로 줄이는 방향을 검토해주실 수 있을까요?', 'open', null],
            [$p1->id, $admin, $m1, 'CDN 설정 방향 논의', '이미지와 정적 에셋을 CloudFront CDN에 올릴 예정인데, S3 버킷 리전을 ap-northeast-2로 해야 할까요, ap-southeast-1이 나을까요?', 'answered', '국내 사용자 대상이면 ap-northeast-2(서울) 리전이 레이턴시 측면에서 유리합니다. CloudFront 배포는 Edge Location이 자동으로 가장 가까운 노드를 선택하므로 오리진 리전은 서울로 설정하시면 됩니다.'],

            // p2
            [$p2->id, $c2 ?? $m1, $m1, '한국투자증권 API 실계좌 연동은 언제부터 가능한가요?', '현재 모의투자 계좌로 테스트 중인데, 실계좌 연동 시점이 궁금합니다. 증권사 심사 기간이 얼마나 걸리나요?', 'answered', '한국투자증권 OpenAPI 실계좌 연동 심사는 보통 2~3주 소요됩니다. 현재 신청서를 제출한 상태이며, 5월 중순경 승인 예상입니다. 그 전까지는 모의투자 환경으로 기능 개발을 완료해두겠습니다.'],
            [$p2->id, $c2 ?? $admin, $m2, '차트 데이터 갱신 주기는 몇 초인가요?', '실시간 시세 차트의 데이터 갱신 주기를 설정할 수 있나요? 5초, 15초, 1분 옵션을 사용자가 선택하도록 구현 가능한지 궁금합니다.', 'answered', '네, 갱신 주기를 사용자가 선택할 수 있도록 설정 패널에 옵션을 추가하겠습니다. 기본값은 15초로 하고, 실시간(3초), 15초, 1분, 5분 옵션을 제공하겠습니다. API 요청 횟수 제한을 고려한 적응형 폴링 방식으로 구현합니다.'],
            [$p2->id, $admin, $m1, '암호화폐 자산도 포트폴리오에 포함할 수 있나요?', '주식 외에 비트코인, 이더리움 같은 암호화폐도 포트폴리오에 추가해서 전체 자산을 볼 수 있으면 좋겠습니다.', 'answered', '업비트 API를 통해 암호화폐 잔고를 조회할 수 있습니다. 다만 이번 MVP 스코프에는 포함되어 있지 않으며, Phase 2 기능으로 로드맵에 추가하겠습니다. 현재는 직접 수동 입력 방식으로 임시 지원하겠습니다.'],
            [$p2->id, $m1, null, '수익률 계산 기준 통화 설정 필요', '해외 주식(미국 ETF) 수익률을 원화 기준으로 표시할 때 환율 적용 시점이 문제가 됩니다. 매입일 환율인지, 현재 환율인지 명확히 해야 할 것 같습니다.', 'open', null],
            [$p2->id, $c2 ?? $m2, $admin, 'PDF 리포트 발송 주기 설정 가능한가요?', '월간 리포트 외에 주간 리포트도 받고 싶습니다. 발송 주기를 사용자가 직접 설정할 수 있으면 좋겠어요.', 'answered', '월간/주간/일간 세 가지 주기를 지원할 예정입니다. 마이페이지의 리포트 설정에서 원하는 주기와 수신 이메일을 지정할 수 있도록 구현하겠습니다.'],

            // p3
            [$p3->id, $admin, $m2, '연차 신청 승인자 다단계 설정이 가능한가요?', '팀장 승인 후 인사팀장도 최종 승인해야 하는 2단계 승인 프로세스가 필요합니다.', 'answered', '전자결재 모듈에서 결재선을 자유롭게 설정할 수 있습니다. 직위/부서별 기본 결재선 템플릿을 미리 설정해두면 신청 시 자동으로 적용됩니다. 예외 결재선도 신청 건별로 변경 가능합니다.'],
            [$p3->id, $m1, $admin, '퇴직자 계정 처리는 어떻게 하나요?', '퇴직 처리 시 계정을 삭제해야 하나요, 아니면 비활성화로 두고 이력을 보존해야 하나요?', 'answered', '계정은 삭제하지 않고 비활성화(soft delete) 처리하여 6년간 이력을 보존합니다. 비활성 계정은 로그인이 불가하며, 관리자 화면에서만 조회 가능합니다. 이는 근로기준법 서류 보존 의무(3년) 충족을 위함입니다.'],
            [$p3->id, $m2, $m1, '모바일 앱에서도 근태 기록이 가능한가요?', '현장 직원들이 스마트폰으로 출퇴근을 기록해야 합니다. GPS 기반 위치 인증도 필요합니다.', 'answered', 'HR 포털 모바일 웹(PWA)에서 근태 기록이 가능합니다. GPS 위치 인증은 특정 반경(예: 100m) 이내에서만 출퇴근 기록이 가능하도록 구현하겠습니다. 오차 범위 설정은 관리자가 조정할 수 있습니다.'],
            [$p3->id, $admin, null, '교육 이수 시간 자동 집계 방식', '사내 온라인 교육 외에 외부 교육, 세미나 참석도 이수 시간에 포함해야 하는데, 수동 입력 후 관리자 승인 방식으로 처리하면 될까요?', 'open', null],
            [$p3->id, $m1, $admin, '초과근무 수당 자동 계산 로직 문의', '포괄임금제 직원과 시급제 직원의 초과근무 수당 계산 방식이 다릅니다. 두 가지 유형 모두 자동 계산되도록 구현 가능한가요?', 'answered', '가능합니다. 직원별 임금 유형(포괄임금/시급제)을 설정하면, 근태 데이터를 기반으로 각각의 수당이 자동 계산됩니다. 근로기준법 기준(1.5배, 2배)을 기본 적용하며 사별 내규로 조정할 수 있습니다.'],

            // p4
            [$p4->id, $m1, $admin, '레거시 세션 방식을 JWT로 전환 시 주의사항', 'PHP 세션 기반 인증을 JWT로 교체할 때 기존 로그인된 사용자 세션이 강제 로그아웃되는 문제를 어떻게 처리해야 할까요?', 'answered', '점진적 전환을 권장합니다. 레거시 세션과 JWT를 일정 기간 병행 운영하고, 사용자가 재로그인 시 JWT로 자동 전환하는 방식입니다. 전환 완료 후 구 세션 방식을 제거합니다. 공지를 통해 특정 날짜 이후 재로그인 안내를 미리 드리면 됩니다.'],
            [$p4->id, $admin, $m1, '다국어 지원 여부', '현재 레거시 시스템은 한국어만 지원하는데, 신규 시스템에서는 영어, 일본어도 지원해야 합니다. Laravel i18n으로 처리하면 될까요?', 'answered', '네, Laravel의 Lang 파사드와 lang 디렉터리 구조를 활용하면 됩니다. DB에 저장된 콘텐츠(상품명, 설명 등)는 spatie/laravel-translatable 패키지를 활용하시면 컬럼별 다국어 지원이 편리합니다.'],
            [$p4->id, $m1, null, '마이그레이션 중 다운타임 최소화 전략', '데이터 이관 시 서비스 중단 없이(Zero-downtime) 운영이 가능할까요? 예상 데이터량이 200GB 이상입니다.', 'open', null],

            // p5 (completed)
            [$p5->id, $admin, $m1, 'CSRF 토큰 만료 시간 설정', 'Laravel CSRF 토큰 기본 만료 시간이 2시간인데, SPA 구조에서는 어떻게 갱신해야 하나요?', 'answered', 'SPA에서는 /sanctum/csrf-cookie 엔드포인트를 주기적으로 호출하거나, axios 인터셉터에서 419 응답 시 자동 갱신하도록 구현합니다. 세션 수명과 CSRF 토큰 수명을 config/session.php에서 동기화하는 것도 중요합니다.'],
            [$p5->id, $m1, $admin, 'SQLi 자동 탐지 패턴 추가 요청', '현재 WAF 룰에 UNION, DROP, EXEC 등 기본 패턴만 있는데, Time-based Blind SQLi 탐지 패턴도 추가해주세요.', 'answered', 'ModSecurity OWASP CRS 규칙셋을 전체 활성화하면 Time-based, Boolean-based, Error-based 등 주요 SQLi 변종이 모두 커버됩니다. 현재 Paranoia Level 2로 설정하여 false positive를 최소화하면서 탐지율을 높였습니다.'],
            [$p5->id, $m2, $admin, '파일 업로드 취약점 점검 결과', '악성 파일 업로드(webshell) 테스트 시 .php 확장자 차단이 Bypass되는 경우가 있었습니다. 어떻게 대응하셨나요?', 'answered', 'MIME 타입 검증만으로는 부족하며, 파일 매직 바이트(file signature) 검증 + 확장자 화이트리스트 방식을 동시에 적용했습니다. 또한 업로드된 파일은 웹 루트 외부 경로에 저장하고, 다운로드 시 스트리밍 방식으로 서빙합니다.'],

            // p6
            [$p6->id, $m2, $m1, 'React Native Expo Go에서 WebSocket 연결이 끊기는 문제', '개발 중 Expo Go 앱에서 Pusher WebSocket이 백그라운드 전환 시 자동으로 끊깁니다. 재연결 로직을 어떻게 구현해야 할까요?', 'answered', 'AppState API를 사용해 앱이 foreground로 전환될 때 재연결을 시도하도록 구현합니다. Pusher SDK의 connection.bind 이벤트로 상태를 모니터링하고, 연결이 끊기면 pusher.connect()를 호출합니다. 재연결 시도 간격은 exponential backoff 방식으로 설정합니다.'],
            [$p6->id, $admin, $m2, 'iOS 앱 아이콘과 스플래시 화면 디자인 기준', 'App Store 제출 시 필요한 아이콘 사이즈 규격과 스플래시 화면 해상도를 알려주세요.', 'answered', 'Expo는 app.json에 단일 1024×1024 아이콘 이미지를 지정하면 자동으로 모든 사이즈를 생성합니다. 스플래시 화면은 1284×2778px(iPhone Pro Max 기준)로 제작 후 splash.resizeMode를 cover로 설정하면 됩니다.'],
            [$p6->id, $m1, null, '안드로이드 알림 권한 요청 타이밍', 'Android 13부터 푸시 알림 권한을 런타임에 요청해야 합니다. 앱 첫 실행 시 바로 요청하면 거부율이 높다고 하는데, 적절한 타이밍이 언제인가요?', 'open', null],
        ];

        foreach ($qaData as $row) {
            [$pid, $asker, $answerer, $qtitle, $qcontent, $status, $answerContent] = $row;
            $q = Question::create([
                'project_id' => $pid,
                'user_id'    => is_object($asker) ? $asker->id : $asker,
                'title'      => $qtitle,
                'content'    => $qcontent,
                'status'     => $status,
            ]);
            if ($answerer && $answerContent) {
                Answer::create([
                    'question_id' => $q->id,
                    'user_id'     => is_object($answerer) ? $answerer->id : $answerer,
                    'content'     => $answerContent,
                    'is_accepted' => true,
                ]);
            }
        }
        $this->command->info('Q&A seeded: '.count($qaData));

        // ── 7. 커뮤니티 게시글 (35개) ────────────────────────────────────
        $postData = [
            // 공지
            ['announcement', '2026년 5월 개발팀 스프린트 일정 공지', '안녕하세요. 5월 스프린트 일정을 안내드립니다.\n\n**Sprint 10 (5/1~5/14)**\n- 이커머스 결제 모듈 마무리\n- HR 포털 근태관리 개발\n- 모바일 앱 인증 완료\n\n**Sprint 11 (5/15~5/31)**\n- 투자 대시보드 차트 고도화\n- HR 급여명세 기능 개발\n\n일정 관련 문의는 채널에 남겨주세요.', true, 15, $admin->id],
            ['announcement', '코드 리뷰 가이드라인 업데이트 안내', "코드 리뷰 문화 개선을 위해 가이드라인을 업데이트했습니다.\n\n**주요 변경사항**\n1. PR 규모: 400줄 이하로 유지\n2. 리뷰어: 최소 1명 승인 필수\n3. 리뷰 응답 시간: 영업일 기준 24시간 이내\n4. Conventional Commits 형식 준수\n\n전체 가이드라인은 Notion 페이지를 참고해주세요.", true, 22, $admin->id],
            ['announcement', '외부 접속 VPN 설정 필수 안내', '보안 정책 강화로 사무실 외부에서 개발 서버 접속 시 VPN 사용이 의무화됩니다. 5월 1일부터 VPN 없이는 스테이징/개발 서버에 접근이 불가합니다. IT팀에 VPN 계정 신청해주세요.', false, 8, $admin->id],

            // 기술
            ['technical', 'Laravel 11 Queue + Redis 설정 최적화 경험 공유', "이커머스 주문 처리에 Queue를 적용하면서 겪은 경험을 공유합니다.\n\n**문제**: 주문 폭주 시 큐 워커가 과부하로 죽는 현상\n\n**해결**: `--max-jobs=1000 --max-time=300` 옵션으로 워커 자동 재시작 설정. Horizon으로 워커 수를 부하에 따라 자동 확장.\n\n**결과**: 피크 시간대 주문 처리 실패율 2.3% → 0.01% 감소.", false, 31, $m1->id],
            ['technical', 'React Query v5 마이그레이션 후기', "투자 대시보드에서 React Query v4 → v5로 마이그레이션하면서 겪은 변경사항입니다.\n\n1. `cacheTime` → `gcTime` 이름 변경\n2. `onSuccess/onError` 콜백 제거 → `useEffect`로 대체\n3. `suspense` 옵션 deprecated → `useSuspenseQuery` 사용\n\n전체 마이그레이션 체크리스트는 댓글에 공유했습니다.", false, 44, $m2->id],
            ['technical', 'MySQL 8.0 윈도우 함수로 쿼리 최적화', "HR 포털 월별 통계 쿼리를 GROUP BY에서 윈도우 함수로 변경했더니 실행 시간이 1.8s → 0.03s로 60배 빨라졌습니다.\n\n```sql\nSELECT\n  dept_id,\n  SUM(work_hours) OVER (PARTITION BY dept_id) as dept_total,\n  AVG(work_hours) OVER (PARTITION BY dept_id) as dept_avg\nFROM attendance\n```\n\nINDEX 추가와 병행하면 더욱 효과적입니다.", false, 38, $m1->id],
            ['technical', 'Docker Compose 개발 환경 표준화 제안', "팀원마다 로컬 환경이 달라 \"내 PC에서는 됩니다\" 문제가 자주 발생했습니다.\n\n`docker-compose.yml` + `Makefile`로 개발 환경을 표준화했습니다.\n\n`make dev` 한 줄로 MySQL, Redis, Mailpit, app이 모두 올라옵니다. 새 팀원 온보딩 시간이 반나절 → 1시간으로 줄었습니다.", false, 27, $admin->id],
            ['technical', 'Tailwind CSS 커스텀 디자인 시스템 구축기', "이커머스 UI에 사용하는 컴포넌트 라이브러리를 tailwind.config.js 기반으로 표준화했습니다.\n\n`brand-*`, `surface-*`, `feedback-*` 세 가지 컬러 팔레트 체계를 정립하고, shadcn/ui를 베이스로 커스터마이징했습니다.\n\nStorybook 연동으로 디자이너와 실시간 피드백이 편해졌습니다.", false, 19, $m2->id],
            ['technical', 'Redis Pub/Sub을 이용한 실시간 재고 동기화', "여러 서버 인스턴스에서 재고 변경 이벤트를 실시간으로 동기화해야 했습니다.\n\nDatabase Lock 방식의 한계를 극복하기 위해 Redis SUBSCRIBE로 재고 변경 채널을 구독하고, Lua 스크립트로 원자적 재고 감소를 처리했습니다.\n\n초당 500건 처리 시 재고 불일치 0건.", false, 35, $m1->id],
            ['technical', 'JWT 리프레시 토큰 보안 강화 방안', "투자 앱 인증에서 리프레시 토큰 탈취 대비 RTR(Refresh Token Rotation) 전략을 도입했습니다.\n\n리프레시 토큰을 1회용으로 만들고, 재사용 감지 시 해당 사용자의 모든 토큰을 무효화합니다. HttpOnly 쿠키 + SameSite=Strict 조합으로 XSS/CSRF 공격도 차단합니다.", false, 42, $m1->id],
            ['technical', 'Figma to Code 워크플로우 개선', "디자인 → 개발 핸드오프 과정에서 오차가 많이 생겼습니다.\n\nFigma Dev Mode + Copilot Kit을 활용해 컴포넌트 스펙을 자동 추출하고, Storybook에 바로 연동하는 워크플로우를 구축했습니다.\n\n픽셀 오차가 줄고 디자이너-개발자 소통 비용이 크게 감소했습니다.", false, 16, $m2->id],

            // 질문
            ['question', 'PHP Artisan schedule 운영 서버 크론 설정 방법', '운영 서버(Ubuntu 22.04, Apache)에서 Laravel 스케줄러를 실행하려면 크론탭을 어떻게 설정해야 하나요? 여러 문서를 봤는데 경로 설정이 헷갈립니다.', false, 5, $m2->id],
            ['question', 'Eloquent N+1 문제 디버깅 도구 추천', 'N+1 쿼리 문제를 자동으로 감지해주는 Laravel 패키지나 도구가 있나요? clockwork는 사용해봤는데 다른 것도 있으면 추천해주세요.', false, 12, $m1->id],
            ['question', 'AWS S3 서명된 URL 만료 시간 설정', '프로젝트 파일 다운로드에 S3 Pre-signed URL을 사용 중인데, 만료 시간을 동적으로 조절하는 방법이 있나요? 관리자는 24시간, 일반 사용자는 1시간으로 다르게 주고 싶습니다.', false, 8, $admin->id],
            ['question', 'Laravel Vite에서 환경변수 사용 방법', 'Vite 번들에서 Laravel .env 변수를 읽으려면 어떻게 해야 하나요? VITE_ 프리픽스를 붙여야 하는 건 알겠는데, 서버사이드 변수를 블레이드를 통해 JS에 넘기는 베스트 프랙티스가 궁금합니다.', false, 9, $m2->id],
            ['question', 'React Native에서 Android 12+ 블루투스 권한 처리', 'Android 12부터 블루투스 권한이 세분화됐는데, react-native-ble-plx를 쓸 때 어떤 권한을 요청해야 하나요?', false, 3, $m1->id],
            ['question', 'MySQL EXPLAIN에서 type이 ALL인 경우 처리', '레거시 DB 분석 중 EXPLAIN 결과에서 type: ALL(전체 스캔)인 쿼리가 다수 발견됐습니다. 인덱스를 추가해도 개선이 안 되는 경우 원인이 뭘까요?', false, 17, $m1->id],

            // 아이디어
            ['idea', '웍스 코드 리뷰 봇 도입 제안', "GitHub PR에 Claude 웍스를 연동해서 코드 리뷰를 자동화하면 어떨까요?\n\n**기대 효과**:\n- 보일러플레이트 리뷰 시간 절감\n- 보안 취약점 1차 자동 탐지\n- 코딩 컨벤션 자동 체크\n\n이미 `anthropic/claude-code-action` 이라는 GitHub Action이 있어서 적용이 어렵지 않을 것 같습니다.", false, 28, $m2->id],
            ['idea', '개발팀 주간 데모데이 제안', "매주 금요일 오후 30분씩 각자 개발한 기능을 팀원들에게 데모하는 자리를 만들면 어떨까요?\n\n**장점**:\n- 서로 어떤 기능을 개발하는지 파악 가능\n- 피드백을 일찍 받아 방향 수정 가능\n- 개발 동기부여 향상\n\n부담 없이 WIP 상태도 괜찮습니다.", false, 35, $m1->id],
            ['idea', '운영 알림 채널 Slack 연동 제안', "서버 에러, 결제 실패, 보안 이상 징후 등의 운영 알림을 Slack에 실시간으로 받으면 어떨까요?\n\nLaravel `spatie/laravel-slack-alerts` 패키지로 간단하게 구현할 수 있습니다. 채널별로 알림 종류를 분류하면 노이즈도 줄일 수 있습니다.", false, 19, $admin->id],
            ['idea', '기술 블로그 운영 제안', "팀에서 쌓은 기술 노하우를 블로그로 정리하면 어떨까요?\n\n채용 시 회사 기술력 어필, 팀원 성장, SEO 효과까지 일석삼조입니다. Velog 팀 계정이나 GitHub Pages로 무료로 시작할 수 있습니다.\n\n월 1~2편, 로테이션으로 작성하면 부담도 없을 것 같습니다.", false, 22, $m2->id],
            ['idea', '테스트 커버리지 목표 설정 제안', "현재 테스트 커버리지가 15% 내외인데, 팀 전체 목표를 설정하면 어떨까요?\n\n**단계별 목표**:\n- 1분기: 핵심 비즈니스 로직 60%\n- 2분기: 전체 70%\n- 3분기: 80% 유지\n\nCI에서 커버리지 임계값 미달 시 머지 블락도 고려해볼 만합니다.", false, 14, $m1->id],
            ['idea', 'E2E 테스트 자동화 Playwright 도입', "Cypress 대신 Playwright로 E2E 테스트를 구성하면 어떨까요?\n\nPlaywright는 멀티 브라우저(Chrome, Firefox, Safari) 동시 테스트가 가능하고, 비동기 처리가 더 안정적입니다. 이커머스 구매 플로우처럼 복잡한 시나리오에 특히 효과적입니다.", false, 18, $m2->id],

            // 일반
            ['general', '5월 팀 워크샵 장소 투표', "5월 셋째 주 금요일 팀 워크샵 장소를 투표로 결정합니다.\n\n1. 강원도 평창 리조트\n2. 가평 글램핑\n3. 제주 서귀포 (1박 2일)\n\n의견 댓글로 남겨주세요!", false, 9, $admin->id],
            ['general', '개발 환경 새 노트북 사양 추천', '다음 달 신입 입사 예정인데 맥북 프로 M4 Pro vs 델 XPS 15 중 어떤 걸 추천하시나요? 주로 Docker, Android 에뮬레이터 많이 씁니다.', false, 11, $m2->id],
            ['general', '좋은 기술 유튜브 채널 공유해요', "팔로우 중인 개발 유튜브 채널 공유합니다!\n\n- Fireship (빠르게 트렌드 파악)\n- Theo (t3gg) (React/TS 심화)\n- ThePrimeagen (알고리즘/Vim)\n- 코드스쿼드 (한국 개발 문화)\n\n추천 채널 댓글로 공유해주세요 :)", false, 16, $m1->id],
            ['general', '페어 프로그래밍 경험 있으신 분?', "이번 레거시 마이그레이션에서 페어 프로그래밍을 시도해보고 싶은데, 경험 있으신 분 계신가요? 효과가 있었는지, 어떤 방식으로 진행했는지 듣고 싶습니다.", false, 7, $m1->id],
            ['general', '이번 달 읽은 개발 책 공유', "이번 달 읽은 책: **가상 면접 사례로 배우는 대규모 시스템 설계 기초 2**\n\n선착순 결제 시스템, 이메일 서비스 아키텍처 등 실무 관련 챕터가 특히 도움됐습니다. 현재 진행 중인 이커머스 프로젝트에 직접 적용 가능한 내용이 많네요.", false, 13, $m2->id],
            ['general', '스탠드업 미팅 시간 변경 논의', "현재 오전 10시 스탠드업이 원격 팀원과 시간대가 안 맞는 경우가 있습니다. 오전 9시 30분으로 당기거나, 비동기 텍스트 스탠드업으로 전환하는 것도 고려해볼 만합니다. 의견 부탁드립니다.", false, 6, $admin->id],
        ];

        $createdPosts = [];
        foreach ($postData as [$category, $title, $content, $pinned, $votes, $userId]) {
            $post = CommunityPost::create([
                'user_id'          => $userId,
                'company_group_id' => $cgSW->id,
                'category'         => $category,
                'title'            => $title,
                'content'          => $content,
                'votes'            => $votes,
                'pinned'           => $pinned,
            ]);
            $createdPosts[] = $post;
        }
        $this->command->info('Community posts seeded: '.count($createdPosts));

        // 커뮤니티 댓글
        $commentData = [
            [0, $m1->id, '공지 감사합니다. Sprint 10 목표 달성을 위해 최선을 다하겠습니다!'],
            [0, $m2->id, 'TechCore 이커머스 결제 모듈 관련해서 내일 별도 싱크 미팅 잡아도 될까요?'],
            [3, $m2->id, '저도 같은 문제를 겪었는데 Horizon 도입 후 정말 안정적으로 바뀌었어요. Supervisor 설정도 공유해주시면 감사하겠습니다.'],
            [3, $admin->id, '데모 환경에도 적용해볼게요. 현재 큐 워커 타임아웃이 60초인데 늘려도 될까요?'],
            [4, $m1->id, 'v4에서 v5 마이그레이션 체크리스트 공유 감사합니다. onError 제거 부분에서 저도 한참 헤맸어요.'],
            [4, $admin->id, 'devtools도 새 버전에서 많이 바뀌었죠. Zustand랑 같이 쓸 때 주의사항도 있으면 알려주세요.'],
            [7, $m2->id, 'Docker 환경 표준화 정말 필요했어요. Makefile 공유해주실 수 있나요?'],
            [7, $admin->id, '다음 주 팀 미팅 때 직접 데모해드릴게요. volume mount 설정도 같이 공유하겠습니다.'],
            [15, $m1->id, '코드 커버리지 목표 완전 동의합니다. Feature 테스트를 API 레이어부터 쌓아가면 현실적인 목표 같아요.'],
            [15, $m2->id, 'PHPUnit + Pest 조합으로 테스트 가독성이 많이 올라갔어요. 함께 도입 검토해봐요.'],
            [21, $m2->id, '평창 리조트 투표합니다! 작년에 갔는데 좋았어요.'],
            [21, $m1->id, '저는 가평 글램핑 추천드려요. 이동 시간이 짧아서 실제 활동 시간이 더 많습니다.'],
            [23, $admin->id, 'Fireship 저도 구독 중입니다. 100초 시리즈가 특히 트렌드 파악에 좋더라고요.'],
            [25, $m2->id, '저도 읽었어요! 챕터 5 알림 시스템 설계가 현재 진행 중인 프로젝트랑 딱 맞아서 인상적이었습니다.'],
            [9, $m1->id, '웍스 리뷰 봇 도입 적극 찬성입니다. POC 한번 만들어볼까요?'],
            [9, $admin->id, '보안 스캔 쪽은 CodeQL이나 Snyk도 같이 붙이면 좋을 것 같아요.'],
            [10, $m2->id, '데모데이 정말 좋은 아이디어예요. 부담 없는 분위기가 중요할 것 같은데, Show & Tell 형식으로 하면 어떨까요?'],
            [10, $admin->id, '금요일 오후 4시~4시30분 어떤가요? 주간 회고랑 연계해서 진행하면 효율적일 것 같습니다.'],
        ];

        foreach ($commentData as [$postIdx, $userId, $content]) {
            if (isset($createdPosts[$postIdx])) {
                CommunityComment::create([
                    'post_id'   => $createdPosts[$postIdx]->id,
                    'user_id'   => $userId,
                    'content'   => $content,
                    'votes'     => rand(0, 8),
                ]);
            }
        }

        // ── 8. Action Items (35개) ────────────────────────────────────────
        $actionData = [
            // p1 이커머스
            [$p1->id, $admin->id, $m1->id, '토스페이먼츠 웹훅 서명 검증 로직 구현', null, '2026-04-28', false, null],
            [$p1->id, $m1->id, $m1->id, '재고 부족 이메일 알림 트리거 구현', '재고 임계값 미달 시 Laravel Mail로 담당자에게 자동 발송', '2026-04-30', false, null],
            [$p1->id, $admin->id, $m2->id, '상품 목록 페이지 스켈레톤 UI 적용', null, '2026-05-05', false, null],
            [$p1->id, $m1->id, $admin->id, 'S3 이미지 업로드 WebP 자동 변환 구현', 'Intervention Image 패키지 활용', '2026-05-07', false, null],
            [$p1->id, $admin->id, $m1->id, '장바구니 세션 만료 정책 결정 및 적용', '비로그인 상태 7일, 로그인 30일', '2026-05-10', true, '2026-04-20 11:30:00'],
            [$p1->id, $m2->id, $m2->id, '모바일 결제 UI 3단계 축소 와이어프레임 제작', null, '2026-05-12', false, null],
            // p2 투자 대시보드
            [$p2->id, $m1->id, $m1->id, '한국투자증권 OpenAPI 실계좌 신청서 제출', '담당자: 이과장(02-2000-XXXX)', '2026-04-25', true, '2026-04-22 09:15:00'],
            [$p2->id, $admin->id, $m2->id, '캔들차트 컴포넌트 단위 테스트 작성', '이동평균선, 볼린저밴드 렌더링 케이스', '2026-05-08', false, null],
            [$p2->id, $m1->id, $admin->id, '포트폴리오 수익률 계산 알고리즘 문서화', 'TWR, MWR 두 방식 모두 기술', '2026-05-15', false, null],
            [$p2->id, $m2->id, $m2->id, 'PDF 리포트 디자인 시안 제작 (3가지)', null, '2026-05-20', false, null],
            [$p2->id, $admin->id, $m1->id, '목표가 알림 WebSocket 이벤트 설계', null, '2026-05-25', false, null],
            // p3 HR
            [$p3->id, $admin->id, $admin->id, '인사 발령 결재 템플릿 5종 등록', '승진/전보/파견/휴직/복직', '2026-04-27', true, '2026-04-21 14:00:00'],
            [$p3->id, $m2->id, $m2->id, '출퇴근 GPS 위치 인증 반경 설정 UI', '관리자 설정 화면: 반경 50~500m 조정', '2026-04-30', false, null],
            [$p3->id, $m1->id, $m1->id, '급여 데이터 암호화 컬럼 설계', 'AES-256-CBC 적용 항목 목록 확정', '2026-05-03', false, null],
            [$p3->id, $admin->id, $m2->id, '연차 잔여일수 계산 엣지케이스 테스트', '입사일 기준, 회계연도 기준 두 방식', '2026-05-10', false, null],
            // p4 레거시
            [$p4->id, $m1->id, $m1->id, '레거시 의존 PHP 확장 모듈 목록 정리', 'mcrypt, mysql(구버전) 등 대체재 확인', '2026-04-29', true, '2026-04-18 10:00:00'],
            [$p4->id, $admin->id, $admin->id, '데이터 이관 검증 쿼리 100개 작성', '이관 전후 레코드 수 및 체크섬 비교', '2026-05-15', false, null],
            [$p4->id, $m1->id, $m2->id, '신구 시스템 URL 리다이렉트 맵 작성', 'SEO 손실 최소화를 위한 301 리다이렉트', '2026-05-20', false, null],
            // p5 보안 (완료)
            [$p5->id, $admin->id, $admin->id, '최종 보안 점검 보고서 경영진 보고', null, '2026-03-31', true, '2026-03-29 16:00:00'],
            [$p5->id, $m1->id, $m1->id, 'ModSecurity CRS 규칙 스테이징 검증', null, '2026-02-28', true, '2026-02-26 11:00:00'],
            [$p5->id, $m2->id, $m2->id, 'XSS 테스트 시나리오 30개 케이스 검증', null, '2026-03-10', true, '2026-03-08 15:30:00'],
            // p6 모바일
            [$p6->id, $m2->id, $m2->id, 'FCM 푸시 알림 iOS/Android 동시 테스트', '실기기 테스트 필수', '2026-05-08', false, null],
            [$p6->id, $m1->id, $m1->id, '프로젝트 현황 API 모바일 최적화', '페이지네이션 cursor 방식 전환', '2026-05-12', false, null],
            [$p6->id, $admin->id, $m2->id, 'App Store 심사 체크리스트 작성', 'Privacy Policy URL, 스크린샷 6종 준비', '2026-06-01', false, null],
            [$p6->id, $m2->id, $admin->id, 'TestFlight 내부 테스터 20명 모집', null, '2026-06-08', false, null],
            // 개인 Action Items
            [null, $admin->id, $admin->id, '서버 월간 비용 리포트 작성', 'AWS, Naver Cloud 청구서 취합', '2026-04-30', false, null],
            [null, $m1->id, $m1->id, 'Laravel 11 공식 릴리즈 노트 정리', '팀 공유용 요약 작성', '2026-04-28', true, '2026-04-22 18:00:00'],
            [null, $m2->id, $m2->id, '디자인 컴포넌트 Storybook 최신화', '15개 컴포넌트 스토리 업데이트', '2026-05-05', false, null],
            [null, $admin->id, $m1->id, '신규 외주 개발자 온보딩 자료 작성', '개발 환경 설정, 코드 컨벤션, Git 전략', '2026-05-01', false, null],
            [null, $m1->id, $admin->id, '개발팀 기술 스택 현황 문서 갱신', 'Notion 페이지 업데이트', '2026-04-26', true, '2026-04-24 10:00:00'],
        ];

        foreach ($actionData as [$pid, $userId, $assignedTo, $title, $desc, $dueDate, $isCompleted, $completedAt]) {
            ActionItem::create([
                'user_id'      => $userId,
                'project_id'   => $pid,
                'assigned_to'  => $assignedTo,
                'title'        => $title,
                'description'  => $desc,
                'due_date'     => $dueDate,
                'is_completed' => $isCompleted,
                'completed_at' => $completedAt,
            ]);
        }
        $this->command->info('Action items seeded: '.count($actionData));

        // ── 9. Tasks (32개) ──────────────────────────────────────────────
        $taskData = [
            // p1
            [$p1->id, $m1->id, '결제 API 엔드포인트 단위 테스트 작성', 'PHPUnit으로 성공/실패/환불 케이스 커버', 'in_progress', 'high', '2026-05-02'],
            [$p1->id, $m2->id, '상품 상세 페이지 반응형 구현', '모바일/태블릿 레이아웃 최적화', 'in_progress', 'high', '2026-05-05'],
            [$p1->id, $admin->id, 'CI/CD 파이프라인 스테이징 배포 자동화', 'GitHub Actions + Docker Hub', 'todo', 'medium', '2026-05-15'],
            [$p1->id, $m1->id, '주문 상태 Webhook 이벤트 설계', '발송/배달/완료/반품 상태 전환', 'todo', 'high', '2026-05-10'],
            [$p1->id, $m2->id, '이메일 템플릿 5종 제작', '회원가입/주문확인/배송시작/배송완료/환불', 'todo', 'medium', '2026-05-20'],
            [$p1->id, $m1->id, 'Elasticsearch 상품 검색 연동', '자동완성, 오타 교정, 유사어 처리', 'todo', 'medium', '2026-06-01'],

            // p2
            [$p2->id, $m1->id, '실시간 시세 캐싱 전략 구현', 'Redis TTL 1초, 구독자 모델', 'in_progress', 'high', '2026-04-28'],
            [$p2->id, $m2->id, '포트폴리오 섹터 비중 파이차트 구현', 'Recharts 라이브러리 활용', 'in_progress', 'medium', '2026-05-05'],
            [$p2->id, $admin->id, '사용자 보안 이상 탐지 로직', '동일 ID 다중 기기 로그인 알림', 'todo', 'high', '2026-05-20'],
            [$p2->id, $m1->id, '배당금 수령 이력 조회 기능', '연간/월간 배당 달력 뷰', 'todo', 'low', '2026-06-01'],
            [$p2->id, $m2->id, '다크모드 전체 적용', 'CSS 변수 기반 테마 전환', 'todo', 'low', '2026-06-10'],

            // p3
            [$p3->id, $m2->id, '출퇴근 기록 달력 뷰 구현', '월간 달력에 출결 현황 시각화', 'in_progress', 'medium', '2026-04-30'],
            [$p3->id, $m1->id, '급여 계산식 설정 관리자 화면', '기본급/수당/공제 항목 커스터마이징', 'todo', 'high', '2026-05-10'],
            [$p3->id, $admin->id, '전자결재 모바일 승인 기능', 'PWA에서 결재 승인/반려 처리', 'todo', 'medium', '2026-05-15'],
            [$p3->id, $m2->id, '인사 발령 히스토리 타임라인 UI', '직원별 이력 시각화', 'todo', 'low', '2026-05-25'],

            // p4
            [$p4->id, $m1->id, 'PHP4 전역변수 리팩터링', 'register_globals 의존 코드 제거', 'in_progress', 'high', '2026-05-15'],
            [$p4->id, $admin->id, '데이터 이관 드라이런 스크립트 실행', '스테이징 DB 이관 후 무결성 검증', 'todo', 'high', '2026-07-05'],
            [$p4->id, $m1->id, 'URL 라우팅 레거시 → Laravel 전환', '300개+ 엔드포인트 매핑 작업', 'todo', 'high', '2026-06-30'],

            // p5 (완료)
            [$p5->id, $admin->id, 'WAF 룰 프로덕션 적용 확인', null, 'done', 'high', '2026-03-15'],
            [$p5->id, $m1->id, 'SQL Injection 패치 후 회귀 테스트', null, 'done', 'high', '2026-02-20'],
            [$p5->id, $m2->id, 'CSP 헤더 모든 페이지 적용 검증', null, 'done', 'medium', '2026-03-05'],

            // p6
            [$p6->id, $m2->id, 'Expo EAS Build 설정', 'iOS/Android 배포 프로파일 설정', 'in_progress', 'high', '2026-05-01'],
            [$p6->id, $m1->id, '프로젝트 API 모바일 DTO 최적화', '응답 데이터 경량화', 'todo', 'medium', '2026-05-15'],
            [$p6->id, $m2->id, '앱 아이콘 및 스플래시 디자인 최종 확정', null, 'todo', 'medium', '2026-05-20'],
            [$p6->id, $admin->id, '앱스토어 등록 메타데이터 작성', '설명문, 키워드, 스크린샷', 'todo', 'low', '2026-06-05'],

            // 개인 태스크
            [null, $admin->id, 'AWS 비용 최적화 분석', 'Cost Explorer 월간 리포트 검토', 'todo', 'medium', '2026-04-30'],
            [null, $m1->id, '코드 컨벤션 문서 최신화', 'PHP, JS, CSS 각각 작성', 'todo', 'low', '2026-05-10'],
            [null, $m2->id, 'Figma 컴포넌트 라이브러리 정리', '중복 컴포넌트 제거 및 네이밍 통일', 'in_progress', 'medium', '2026-05-05'],
            [null, $admin->id, '팀 온보딩 체크리스트 문서화', '신규 입사자용 Day 1~30 가이드', 'todo', 'low', '2026-05-15'],
            [null, $m1->id, 'Dependabot 알림 백로그 처리', '밀린 보안 패치 30건 우선순위 정리', 'in_progress', 'high', '2026-04-28'],
            [null, $m2->id, '다음 분기 UI 디자인 트렌드 조사', '2026 H2 디자인 방향 레포트', 'todo', 'low', '2026-05-20'],
        ];

        foreach ($taskData as [$pid, $userId, $title, $desc, $status, $priority, $dueDate]) {
            Task::create([
                'user_id'     => $userId,
                'project_id'  => $pid,
                'title'       => $title,
                'description' => $desc,
                'status'      => $status,
                'priority'    => $priority,
                'due_date'    => $dueDate,
            ]);
        }
        $this->command->info('Tasks seeded: '.count($taskData));

        // ── 10. 메모 (32개) ──────────────────────────────────────────────
        $memoData = [
            // admin
            [$admin->id, '이커머스 PG 연동 체크리스트', "□ 토스페이먼츠 웹훅 서명 검증\n□ 결제 실패 재시도 로직\n□ 영수증 이메일 발송\n□ 환불 API 테스트\n□ 빌키 발급(정기결제)", 'yellow', true],
            [$admin->id, '5월 스프린트 목표', "Sprint 10:\n- 결제 모듈 완료\n- 차트 컴포넌트 1차\n- HR 근태 완료\n\nVelocity 목표: 45 SP", 'blue', true],
            [$admin->id, '채용 인터뷰 질문 리스트', "기술 질문:\n1. N+1 문제 해결 경험\n2. 트랜잭션 격리 수준\n3. RESTful API 설계 원칙\n4. 인덱스 최적화 경험\n5. CI/CD 구축 경험", 'green', false],
            [$admin->id, '서버 접속 정보 (임시)', "스테이징: 10.0.1.100\nProd: 10.0.1.200\nDB: 10.0.2.50:3306\n\n→ Vault에 이전 예정 (5/1)", 'red', false],
            [$admin->id, '회의 안건 - 4/28 기술 회의', "1. 레거시 마이그레이션 진행 현황\n2. 보안 정책 VPN 의무화 안내\n3. 웍스 코드 리뷰 봇 POC 결과\n4. 기타", 'yellow', false],
            [$admin->id, '클라이언트 연락처', "TechCore PM: 박지현 (010-1234-5678)\nFinanceOne 개발팀장: 이준호 (010-9876-5432)\n미팅 요청은 최소 3일 전 공지", 'blue', false],
            [$admin->id, '인프라 비용 메모', "4월 AWS 예상 청구:\n- EC2 (t3.medium x2): ~$140\n- RDS (db.t3.small): ~$50\n- S3 + CloudFront: ~$30\n- 합계: ~$220/월", 'green', false],

            // m1
            [$m1->id, '토스페이먼츠 API 메모', "클라이언트 키: tk_live_...\n웹훅 URL: /api/payment/webhook\n서명 검증: HMAC-SHA256\n\n⚠️ secret은 절대 코드에 하드코딩 금지", 'red', true],
            [$m1->id, 'Redis 운영 설정 최적화', "maxmemory: 2gb\nmaxmemory-policy: allkeys-lru\nsave: 900 1, 300 10\nrequirepass: ✅\nbind: 127.0.0.1 ✅", 'blue', true],
            [$m1->id, 'Laravel Queue 워커 명령어', "php artisan queue:work redis \\\n  --queue=payments,default \\\n  --tries=3 \\\n  --max-jobs=1000 \\\n  --max-time=300\n\nSupervisor 재시작: supervisorctl restart all", 'green', false],
            [$m1->id, '레거시 코드 분석 현황', "총 파일 수: 3,482개\n의존성 제거 완료: 1,200개\n나머지: ~2,280개\n\n예상 완료: 6월 말\n블로커: mcrypt 대체 필요", 'yellow', false],
            [$m1->id, 'Elasticsearch 셋업 메모', "버전: 8.x\nIndex 설계:\n- products (shards:2, replicas:1)\n- nori 한국어 형태소 분석기 설치\n- synonym 사전: /config/synonyms.txt", 'blue', false],
            [$m1->id, '알고리즘 공부 링크', "- 코딩 테스트: BOJ, Programmers\n- 시스템 설계: Hello Interview\n- DB 최적화: Use The Index, Luke\n- 매주 화/목 1시간 스터디 목표", 'green', false],
            [$m1->id, '4월 회고 메모', "잘한 점:\n- Queue 최적화 완료\n- 테스트 커버리지 +12%\n\n개선점:\n- 코드 리뷰 속도 개선 필요\n- 문서화 더 꼼꼼히", 'yellow', false],
            [$m1->id, 'JWT 구현 참고 링크', "RFC 7519 공식 스펙\nLaravel Sanctum vs Passport 비교\nRTR 패턴 구현 예제 (GitHub)\n\n선택: Sanctum + RTR 직접 구현", 'red', false],

            // m2
            [$m2->id, '이커머스 UI 컴포넌트 목록', "완료: 버튼, 인풋, 모달, 카드\n진행: 상품 갤러리, 장바구니\n대기: 결제 폼, 마이페이지\n\n디자인 시스템 Figma 링크 → (URL)", 'blue', true],
            [$m2->id, 'Figma 컴포넌트 네이밍 규칙', "형식: [섹션]/[컴포넌트]/[변형]\n예시:\n- Forms/Input/Default\n- Forms/Input/Error\n- Navigation/Sidebar/Collapsed\n\nAuto Layout 필수 적용", 'green', true],
            [$m2->id, '모바일 앱 디자인 체크리스트', "□ 터치 영역 최소 44x44pt\n□ 고대비 모드 지원\n□ 다크모드 대응\n□ 동적 폰트 사이즈 지원\n□ 키보드 오버랩 처리", 'yellow', false],
            [$m2->id, 'React Native 성능 최적화 노트', "1. FlatList keyExtractor 필수\n2. useCallback/useMemo 남용 금지\n3. Image → FastImage 교체\n4. Hermes 엔진 활성화\n5. RAM Bundles 고려", 'blue', false],
            [$m2->id, '디자인 참고 레퍼런스', "이커머스: Coupang, Musinsa\n대시보드: Linear, Vercel Dashboard\n모바일: Toss, Kakao Bank\n\n공통: 여백의 미, 마이크로인터랙션 중요", 'green', false],
            [$m2->id, '색상 팔레트 SupportWorks 브랜드', "Primary: #3B82F6 (Blue-500)\nSecondary: #8B5CF6 (Violet-500)\nSuccess: #10B981\nWarning: #F59E0B\nDanger: #EF4444\nSurface: #F9FAFB", 'red', false],
            [$m2->id, '폰트 스택 정의', "Heading: Pretendard Bold/SemiBold\nBody: Pretendard Regular\nCode: JetBrains Mono\n\nnext/font 사용, WOFF2 포맷\n한글 서브셋 최적화 적용", 'yellow', false],
            [$m2->id, '5월 디자인 작업 목록', "1. 이커머스 장바구니 UI (D-5)\n2. 투자 대시보드 차트 (D-10)\n3. HR 근태 달력 뷰 (D-7)\n4. 모바일 앱 온보딩 (D-15)", 'blue', false],
            [$m2->id, 'Storybook 버전 업 메모', "v7 → v8 마이그레이션:\n- CSF3 형식 전환\n- play() 함수 활용 인터랙션 테스트\n- Vite 빌더로 교체 (속도 5배↑)\n\n참고: 공식 마이그레이션 가이드", 'green', false],
            [$m2->id, '접근성 개선 메모', "WCAG 2.1 AA 기준 준수 목표\n\n체크 항목:\n- 색상 대비 4.5:1 이상\n- alt 텍스트 모든 이미지\n- 키보드 탐색 가능\n- aria-label 적절히 사용", 'yellow', false],
        ];

        foreach ($memoData as [$userId, $title, $content, $color, $isPinned]) {
            Memo::create([
                'user_id'   => $userId,
                'title'     => $title,
                'content'   => $content,
                'color'     => $color,
                'is_pinned' => $isPinned,
            ]);
        }
        $this->command->info('Memos seeded: '.count($memoData));

        // ── 11. 메시지 & 대화 ─────────────────────────────────────────────
        $conversations = [
            ['이커머스 결제 모듈 논의', [$admin->id, $m1->id], false, [
                [$admin->id, '토스페이먼츠 웹훅 연동 진행 상황 어떻게 돼?'],
                [$m1->id, '서명 검증 로직까지는 완료했고요, 지금 결제 실패 재시도 로직 구현 중입니다.'],
                [$admin->id, '실패 재시도는 3회, 30초 간격으로 하는 게 좋을 것 같아. 토스 가이드에도 그렇게 나와있더라고.'],
                [$m1->id, '네, 그렇게 반영하겠습니다. 환불 API는 주말 전에 완료할게요.'],
                [$admin->id, '고생해. 테스트 환경 웹훅 URL 변경된 거 배포 후 확인해줘.'],
            ]],
            ['차트 컴포넌트 피드백', [$m1->id, $m2->id], false, [
                [$m2->id, '캔들차트 1차 완성했어요! 확인해보실 수 있나요?'],
                [$m1->id, '방금 봤어요. 이동평균선이 캔들 위에 렌더링되는데, z-index 조정이 필요할 것 같아요.'],
                [$m2->id, '아 맞다! 바로 수정할게요. 볼린저밴드 색상도 반투명하게 바꾸는 게 나을까요?'],
                [$m1->id, '네, opacity 0.2 정도로 배경 처리하면 훨씬 깔끔해 보일 것 같아요.'],
                [$m2->id, '수정 완료했습니다. 다시 확인해보실래요?'],
                [$m1->id, '훨씬 좋네요. 이대로 PR 올려주세요!'],
            ]],
            ['스프린트 회고 논의', [$admin->id, $m1->id, $m2->id], true, [
                [$admin->id, '이번 스프린트 회고 내용 정리해서 공유합니다. 전반적으로 속도가 붙고 있는 것 같아서 좋네요.'],
                [$m1->id, '동감해요. 큐 최적화 이후로 배포 안정성이 많이 좋아진 것 같습니다.'],
                [$m2->id, '디자인-개발 핸드오프 속도도 빨라졌어요. Figma Dev Mode 도입 효과가 있는 것 같습니다.'],
                [$admin->id, '다음 스프린트에는 테스트 커버리지 목표를 세워보면 어떨까요? 지금 15% 정도인데 40% 목표로.'],
                [$m1->id, '현실적으로 핵심 비즈니스 로직 위주로 먼저 쌓는 게 좋을 것 같아요. 60% 목표는 좀 힘들 수 있어서요.'],
                [$m2->id, '저는 E2E 테스트를 Playwright로 구성해보고 싶어요. 구매 플로우 자동화가 제일 필요할 것 같고요.'],
                [$admin->id, '좋아요. 다음 주 기술 미팅 때 구체적인 계획을 잡아봐요.'],
            ]],
            ['HR 포털 결재 플로우 확인', [$admin->id, $m2->id], false, [
                [$m2->id, '전자결재 2단계 승인 플로우 구현 완료했어요. 테스트 부탁드립니다.'],
                [$admin->id, '방금 테스트해봤는데, 2차 결재자에게 알림 이메일이 안 가고 있어요.'],
                [$m2->id, '아, 큐 이벤트 리스너 등록이 빠진 것 같네요. 바로 확인할게요.'],
                [$admin->id, '그리고 반려 시에 반려 사유 입력창이 없더라고요. 추가해주세요.'],
                [$m2->id, '두 가지 모두 수정 완료했습니다! 재확인 부탁드려요.'],
            ]],
            ['레거시 DB 스키마 리뷰', [$m1->id, $admin->id], false, [
                [$m1->id, '레거시 DB 스키마 분석 결과 공유드릴게요. 정규화가 전혀 안 되어 있는 테이블이 23개나 됩니다.'],
                [$admin->id, '예상은 했지만... 중복 컬럼도 많겠네요?'],
                [$m1->id, '네, customer 테이블에 주소 관련 컬럼이 5개 형태로 중복 저장되어 있어요. 정규화 작업만 2주는 잡아야 할 것 같습니다.'],
                [$admin->id, '우선순위를 주문/결제 관련 테이블부터 작업하고, 나머지는 점진적으로 진행해요.'],
                [$m1->id, '알겠습니다. ERD 먼저 그려서 공유드릴게요.'],
                [$admin->id, '수고해줘서 고마워요. Notion에 올려주시면 같이 리뷰할게요.'],
            ]],
        ];

        foreach ($conversations as [$name, $participantIds, $isGroup, $messages]) {
            $conv = Conversation::create([
                'name'     => $isGroup ? $name : null,
                'is_group' => $isGroup,
                'type'     => null,
            ]);
            $conv->participants()->attach(
                collect($participantIds)->mapWithKeys(fn($id) => [$id => ['last_read_at' => null]])->toArray()
            );
            foreach ($messages as [$senderId, $body]) {
                Message::create([
                    'conversation_id' => $conv->id,
                    'sender_id'       => $senderId,
                    'body'            => $body,
                ]);
            }
        }
        $this->command->info('Conversations & messages seeded.');

        $this->command->info('');
        $this->command->info('=== FreshSampleSeeder 완료 ===');
        $this->command->info('Projects: 6 | Schedules: '.count($schedules).' | Q&A: '.count($qaData));
        $this->command->info('Posts: '.count($postData).' | Actions: '.count($actionData).' | Tasks: '.count($taskData));
        $this->command->info('Memos: '.count($memoData).' | Conversations: '.count($conversations));
    }
}
