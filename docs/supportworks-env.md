# Supportworks 환경 정보

> Claude Code가 AI Agent 메뉴 기능을 정확하게 구현할 수 있도록 Supportworks 환경 정보를 정리합니다.
> 마지막 업데이트: 2026-05-03

---

## 1. 기술 스택

### 1.1 Frontend
- **Framework**: 없음 (서버사이드 렌더링 — Laravel Blade 템플릿)
- **언어**: PHP (Blade) + Vanilla JavaScript (Alpine.js)
- **State Management**: Alpine.js (`x-data`) — 컴포넌트 단위 로컬 상태
- **Build Tool**: Vite 7 (`laravel-vite-plugin`)
- **Routing**: Laravel 라우터 (서버사이드)
- **HTTP Client**: Axios 1.x (프론트엔드 Ajax 요청)

### 1.2 UI / 디자인 시스템
- **UI 라이브러리**: 자체 Blade 컴포넌트 (별도 UI 라이브러리 없음)
- **CSS 방식**: Tailwind CSS 3.x (`@tailwindcss/forms` 포함)
- **아이콘 라이브러리**: Heroicons (SVG 인라인 직접 사용)
- **디자인 시스템 위치**: `resources/views/components/` (Blade 컴포넌트)

### 1.3 Backend
- **언어**: PHP 8.2+
- **Framework**: Laravel 12 (laravel/framework ^12.0)
- **API 스타일**: REST (JSON 응답) + 일부 서버사이드 redirect
- **API 명세 도구**: 없음 (라우트 정의: `routes/web.php`, `routes/api.php`)

### 1.4 Database
- **종류**: MySQL 8 (원격 서버 43.203.246.90:3306)
- **ORM**: Eloquent ORM (Laravel 내장)
- **마이그레이션 도구**: Laravel Migrations (`database/migrations/`)
- **DB명**: `supportworks` / 커넥션명: `supportworks`

### 1.5 인증 / 인가
- **인증 방식**: Session 기반 (Laravel Auth)
- **세션 저장소**: database (`SESSION_DRIVER=database`)
- **세션 유효시간**: 360분
- **SSO 사용 여부**: 없음
- **권한 모델**: RBAC
  - **사용자(users) role**: `admin` / `member` / `client`
  - **관리자(admin_users) role**: `super_admin` / `admin` / `operator` / `support_agent`
- **인증 Guard**:
  - 일반 사용자: `web` guard (`users` 테이블)
  - 관리자: `admin` guard (`admin_users` 테이블)

### 1.6 파일 / 스토리지
- **파일 저장소**: 로컬 디스크 (`FILESYSTEM_DISK=local`, `storage/app/`)
- **CDN**: 없음
- **파일 업로드 제한**: 50MB (MaintenanceFile), 20MB (Message 첨부)
- **문서 변환**: LibreOffice (`/usr/bin/libreoffice`) → PDF 변환, Office Online Viewer 폴백

### 1.7 기타 인프라
- **Cache**: database (`CACHE_STORE=database`)
- **실시간(WebSocket)**: Pusher (AP3 클러스터) + Laravel Echo + pusher-js
- **메시지 큐**: sync (비동기 큐 없음, `QUEUE_CONNECTION=sync`)
- **백그라운드 작업**: `app()->terminating()` 콜백 (프로젝트 알림 이메일)
- **로깅**: Laravel 일별 파일 로그 (`LOG_CHANNEL=daily`) + `system_error_logs` DB 테이블

---

## 2. 프로젝트 구조

### 2.1 Repository 구조
- **저장소 형태**: 모노레포 (단일 Laravel 앱, frontend/backend 통합)
- **Frontend 경로**: `resources/views/` (Blade), `resources/js/`, `resources/css/`
- **Backend 경로**: `app/Http/Controllers/`, `app/Models/`, `app/Services/`
- **공통 패키지 경로**: 없음 (단일 앱)

### 2.2 AI Agent 메뉴를 추가할 위치
- **Frontend 뷰 경로**: `resources/views/ai/` (기존 AI 채팅), 신규는 `resources/views/ai-agent/`
- **Backend 컨트롤러 경로**: `app/Http/Controllers/` (기존: `AiController.php`)
- **서비스 레이어 경로**: `app/Services/` (기존: `AiOrchestrator.php`, `ClaudeService.php` 등)
- **DB 마이그레이션 경로**: `database/migrations/`
- **라우트 파일**: `routes/web.php`

### 2.3 기존 모듈과의 연동 지점
- **프로젝트 모듈**: `app/Models/Project.php`, `app/Http/Controllers/ProjectController.php`
- **사용자/권한 모듈**: `app/Models/User.php`, `app/Models/AdminUser.php`
- **파일 업로드 모듈**: `app/Http/Controllers/FileCommentController.php`, `app/Models/ProjectFile.php`
- **AI 기존 모듈**:
  - `app/Services/AiOrchestrator.php` — Claude → OpenAI 폴백 오케스트레이터
  - `app/Services/ClaudeService.php` — Anthropic API
  - `app/Services/OpenAiService.php` — OpenAI API
  - `app/Services/ManusService.php` — Manus API (채팅 완성 미지원, task 전용)
  - `app/Models/AiSetting.php` — AI API 키 관리 (DB 암호화 저장 + .env 폴백)
  - `app/Models/AiSession.php`, `app/Models/AiMessage.php` — AI 대화 세션 DB

### 2.4 코드 컨벤션
- **Linter**: Laravel Pint (`laravel/pint`) — PSR-12 기준
- **컨벤션 문서**: 없음 (Laravel 표준 관행)
- **네이밍 규칙**:
  - PHP 클래스/메서드: PascalCase / camelCase
  - DB 컬럼: snake_case
  - Blade 파일: kebab-case (`my-component.blade.php`)
  - JS 변수: camelCase

---

## 3. AI 통합

### 3.1 사용할 AI 모델
- **주 모델**: Claude (Anthropic) — `claude-sonnet-4-5` 또는 최신 claude-sonnet
- **폴백 순서**: Claude → OpenAI (GPT-4o) — Manus는 채팅 완성 미지원으로 제외
- **API 키 관리 방식**: DB 암호화 저장(`ai_settings` 테이블) + `.env` 폴백
  - `ANTHROPIC_API_KEY` (`.env`)
  - `OPENAI_API_KEY` (`.env`)
  - `MANUS_API_KEY` (`.env`) — task 생성 전용
- **API 호출 방식**: `AiOrchestrator::chatRawDirect()` 를 통해 서비스 레이어 호출

### 3.2 AI 사용 정책
- **고객 데이터 외부 전송 가능 여부**: 가능 (현재 외부 Anthropic/OpenAI API 사용 중)
- **온프레미스 LLM 사용 필요성**: 없음 (현재 클라우드 API만 사용)
- **데이터 보존 기간**: AI 대화 로그는 DB 영구 보존 (삭제 정책 미설정)

### 3.3 외부 도구 통합
- **Figma API 사용 여부**: 사용 중 (`figma_token` in `ai_settings`, `figma_files` 테이블)
- **Microsoft Teams 통합**: 사용 중 (`teams_settings` 테이블, `TeamsService.php`)
- **목업 생성 도구**: 없음 (자체 구현)

---

## 4. 배포 / 운영 환경

### 4.1 환경 구분
- **환경**: local (개발) / production (운영)
- **현재 APP_URL**: `http://localhost/supportworks` (로컬), `https://www.supportworks.co.kr` (운영)
- **배포 방식**: XAMPP (로컬 개발), 운영 환경 별도 (Apache/Nginx + PHP-FPM 추정)
- **CI/CD**: 없음 (수동 배포)

### 4.2 모니터링
- **APM**: 없음
- **에러 추적**: 자체 `system_error_logs` DB 테이블 + Laravel daily 로그 파일
  - `SystemErrorLog::record(\Throwable $e, string $level = 'error')` — 모든 catch 블록에서 사용
  - 관리자 패널 `/admin/system-errors` 에서 확인 가능

---

## 5. 비즈니스 / 정책

### 5.1 사용자
- **예상 동시 사용자 수**: 소규모 (수십~수백 명 범위)
- **예상 프로젝트 수**: 소규모 팀 단위
- **다국어 지원**: 한국어 기본, 번역 기능 내장 (TranslateController — Claude/OpenAI/Google 폴백)

### 5.2 권한
- **AI Agent 메뉴 사용 권한**: 프로젝트 멤버 전원 (`member` role 이상)
  - 관리자(`admin`) 및 매니저(`manager` role in project_members) 는 추가 기능(팀원 전체 분석 등) 사용 가능
- **승인 권한자**: 프로젝트 매니저 / 관리자

### 5.3 비용
- **AI 호출 예산**: 미설정
- **비용 알림 임계치**: 미설정

---

## 6. 우선순위 및 제약사항

### 6.1 추가 우선순위
- **MVP 범위**: 현재 기존 AI 채팅(`ai_sessions`) 기반 기능 확장
- **목표 출시일**: 미정

### 6.2 제약사항
- **기술 제약**:
  - Manus API는 `/v2/chat/completions` 미지원 → AI 채팅 완성에 사용 불가 (task 전용)
  - LibreOffice 미설치 환경에서는 PDF 변환 불가 → Office Online Viewer 폴백
  - `QUEUE_CONNECTION=sync` — 무거운 AI 작업이 HTTP 응답을 차단할 수 있음
- **보안 제약**:
  - 관리자 영역(`/admin/*`)은 `admin` guard로 별도 인증 필요
  - API 키는 반드시 `AiSetting::current()->anthropicKey()` 방식으로 조회 (DB 우선, .env 폴백)
- **법적 제약**: 개인정보보호법 준수 (한국)

---

## 7. 추가 컨텍스트

### 7.1 기존 시스템과의 차별점
- Supportworks는 프로젝트 협업 + 고객지원(SR) + AI 어시스턴트를 통합한 올인원 플랫폼
- 일반 SaaS 대비: 관리자(AdminUser)와 일반 사용자(User)가 완전히 분리된 Guard 구조
- AI 기능은 프로젝트 컨텍스트에 연동됨 (프로젝트별 AI 세션, Figma 파일, 기획 문서 등)

### 7.2 알려진 제약 / 주의사항
- `auth()->user()` 는 일반 사용자, `auth('admin')->user()` 는 관리자 — 혼용 금지
- AI API 키 조회 시 `env()` 직접 호출 금지 → `AiSetting::current()->anthropicKey()` 사용
- `.env` 변경 후 반드시 `php artisan config:clear` 필요 (캐시 문제)
- Blade 파일 내 `<script>` 블록의 Intelephense/TypeScript 파서 오류는 false positive — 무시
- Pusher 이벤트 브로드캐스트는 `try/catch` 로 감싸고 실패해도 응답 계속 (비크리티컬)
- 모든 catch 블록에서 `SystemErrorLog::record($e)` 호출 필수 (관리자 에러 패널 연동)

### 7.3 참고 문서
- **기존 시스템 아키텍처**: 코드베이스 직접 확인 (`app/`, `routes/`, `resources/views/`)
- **API 문서**: 없음 (`routes/web.php` 참조)
- **디자인 시스템 문서**: 없음 (기존 Blade 뷰 참조)
- **주요 서비스 파일**:
  - `app/Services/AiOrchestrator.php` — AI 폴백 오케스트레이터
  - `app/Services/ClaudeService.php` — Anthropic API 래퍼
  - `app/Services/OpenAiService.php` — OpenAI API 래퍼
  - `app/Models/AiSetting.php` — AI 설정 모델
  - `app/Models/SystemErrorLog.php` — 에러 로그 모델
