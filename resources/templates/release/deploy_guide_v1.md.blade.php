# {{ $project['name'] }} — 배포 가이드

> 생성일: {{ $metadata['generated_at'] }} | 버전: 1.0.0 | AI Agent T49 자동 생성

---

## 1. 프로젝트 개요

| 항목 | 값 |
|------|----|
| 프로젝트 | {{ $project['name'] }} |
| Frontend 스택 | {{ strtoupper($project['frontend_stack']) }} |
| Backend 스택 | Laravel (PHP) |
| 생성 시각 | {{ $metadata['generated_at'] }} |

---

## 2. 사전 요구사항

### 공통

- PHP >= 8.2
- Composer >= 2.x
- MySQL >= 8.0 또는 MariaDB >= 10.6
- Node.js >= 18.x (Frontend 빌드 시)

### Backend (Laravel)

```bash
# 의존성 설치
composer install --no-dev --optimize-autoloader

# 환경 설정
cp .env.example .env
php artisan key:generate

# 데이터베이스 마이그레이션
php artisan migrate --force

# 스토리지 링크
php artisan storage:link

# 캐시 최적화
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

@switch($project['frontend_stack'])
@case('react')
### Frontend (React)

```bash
cd 04-frontend/
npm install
npm run build
# dist/ 폴더를 웹서버 루트 또는 public/ 하위에 배포
```

> **환경 변수**: `.env` 파일에 `VITE_API_BASE_URL` 등 API 엔드포인트 설정 필요

@break
@case('vue')
### Frontend (Vue 3)

```bash
cd 04-frontend/
npm install
npm run build
# dist/ 폴더를 웹서버 루트 또는 public/ 하위에 배포
```

> **환경 변수**: `.env` 파일에 `VITE_API_BASE_URL` 등 API 엔드포인트 설정 필요

@break
@default
### Frontend (HTML / Vanilla JS)

```bash
# 빌드 불필요 — 정적 파일 직접 배포
# 04-frontend/ 폴더 전체를 웹서버 루트에 복사
cp -r 04-frontend/* /var/www/html/
```

@endswitch

---

## 3. 데이터베이스 설정

### 스키마 적용

```sql
-- 03-dev-prep/erd.sql 파일 실행
mysql -u {DB_USER} -p {DB_NAME} < 03-dev-prep/erd.sql
```

@if($database['tables_count'] > 0)
### 테이블 목록 ({{ $database['tables_count'] }}개)

@foreach($database['tables'] as $table)
- `{{ $table }}`
@endforeach
@endif

### .env 데이터베이스 설정

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE={your_database}
DB_USERNAME={your_username}
DB_PASSWORD={your_password}
```

---

## 4. API 명세

@if($api['has_spec'])
API 명세서는 `{{ $api['spec_file'] }}` 파일을 참조하십시오.

- **총 엔드포인트**: {{ $api['endpoints_count'] }}개
- **Swagger UI**: `api-spec.yaml`을 [Swagger Editor](https://editor.swagger.io)에 임포트하여 확인
- **Postman**: `api-spec.yaml` 또는 `api-spec.json` 파일을 Postman Collection으로 임포트

```bash
# 로컬 Swagger UI 실행 (선택)
npx @stoplight/spectral-cli lint 03-dev-prep/api-spec.yaml
```
@else
API 명세서가 아직 생성되지 않았습니다. `03-dev-prep/api-spec.json`을 확인하십시오.
@endif

---

## 5. Frontend 배포 상세

@switch($project['frontend_stack'])
@case('react')
### React 배포

```bash
cd 04-frontend/

# 의존성 설치
npm ci

# 환경 변수 설정
cp .env.example .env.production
# VITE_API_BASE_URL=https://your-api-domain.com/api

# 프로덕션 빌드
npm run build

# dist/ 폴더 내용을 웹서버에 배포
# Nginx 예시:
#   root /var/www/html/dist;
#   try_files $uri $uri/ /index.html;
```

#### 화면 구조 ({{ $frontend['screens_count'] }}개 화면)

각 화면 코드는 `04-frontend/SCR-XXX/` 형식으로 구성되어 있습니다.
빌드 후 `dist/` 폴더에 번들링된 결과물이 생성됩니다.

@break
@case('vue')
### Vue 3 배포

```bash
cd 04-frontend/

# 의존성 설치
npm ci

# 환경 변수 설정
cp .env.example .env.production
# VITE_API_BASE_URL=https://your-api-domain.com/api

# 프로덕션 빌드
npm run build

# dist/ 폴더 내용을 웹서버에 배포
# Nginx 예시:
#   root /var/www/html/dist;
#   try_files $uri $uri/ /index.html;
```

#### 화면 구조 ({{ $frontend['screens_count'] }}개 화면)

각 화면 컴포넌트는 `04-frontend/SCR-XXX/` 형식으로 구성되어 있습니다.

@break
@default
### HTML / Vanilla JS 배포

빌드 프로세스 없이 정적 파일을 직접 배포합니다.

```bash
# 04-frontend/ 폴더 전체를 웹서버 루트에 복사
rsync -av 04-frontend/ user@server:/var/www/html/

# 또는 Nginx/Apache 설정에서 04-frontend/ 경로를 document root로 지정
```

#### 화면 구조 ({{ $frontend['screens_count'] }}개 화면)

각 화면은 `04-frontend/SCR-XXX/index.html` 형식으로 개별 파일로 구성되어 있습니다.

@endswitch

---

## 6. Backend 배포 상세

### Laravel 파일 배포

```bash
# 05-backend/ 폴더의 각 리소스를 Laravel 프로젝트에 복사
# 예: 05-backend/User/ → app/Http/Controllers/, app/Models/ 등

cp -r 05-backend/*/app/ /path/to/laravel/app/
cp -r 05-backend/*/database/ /path/to/laravel/database/
```

@if($backend['resources_count'] > 0)
### 리소스 목록 ({{ $backend['resources_count'] }}개)

`05-backend/` 폴더 아래 각 리소스별 폴더에 Controller, Model, Policy, Migration 파일이 포함되어 있습니다.
@endif

### 권한 모델 (RBAC)

@if($backend['has_rbac'])
- **역할 수**: {{ $backend['role_count'] }}개
- **Policy 파일**: `{{ $backend['policy_file'] }}`

```bash
# RolePolicy.php를 Laravel 프로젝트에 복사
cp 03-dev-prep/RolePolicy.php /path/to/laravel/app/Policies/RolePolicy.php

# AuthServiceProvider에 Policy 등록 필요
# $policies = [Model::class => RolePolicy::class];
```
@else
RBAC 모델이 아직 생성되지 않았습니다. `03-dev-prep/rbac.json`을 참조하십시오.
@endif

---

## 7. API 연계 확인

@if($integration['has_integration'])
@if($integration['compliance_rate'] !== null)
- **API 연계율**: {{ $integration['compliance_rate'] }}%
@endif
@if($integration['review_score'] !== null)
- **코드 리뷰 점수**: {{ $integration['review_score'] }}/100
@endif

`06-integration/api-integration.json`에서 연계 상태를 확인하십시오.

미연결 API 목록은 `06-integration/unmatched-apis.md`를 참조하십시오.
@else
API 연계 정보가 없습니다.
@endif

---

## 8. 환경 변수 체크리스트

```env
# Application
APP_NAME="{{ $project['name'] }}"
APP_ENV=production
APP_KEY=                    # php artisan key:generate
APP_DEBUG=false
APP_URL=https://your-domain.com

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

# Cache / Session
CACHE_DRIVER=redis          # 또는 file
SESSION_DRIVER=redis         # 또는 file
QUEUE_CONNECTION=redis       # 또는 sync

# Mail (필요 시)
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
```

---

## 9. 배포 후 확인 사항

- [ ] 웹서버 접속 확인 (`https://your-domain.com`)
- [ ] 데이터베이스 연결 확인 (`php artisan tinker` → `DB::connection()->getPdo()`)
- [ ] 로그인/회원가입 동작 확인
- [ ] API 엔드포인트 응답 확인 (`GET /api/health` 또는 첫 번째 엔드포인트)
- [ ] 파일 업로드/다운로드 확인 (storage:link 적용 여부)
- [ ] 권한별 접근 제어 확인 (RBAC)
- [ ] 에러 로그 확인 (`storage/logs/laravel.log`)

---

## 10. 트러블슈팅

### 일반적인 문제

| 증상 | 원인 | 해결 방법 |
|------|------|-----------|
| 500 Internal Server Error | `.env` 미설정 또는 캐시 문제 | `php artisan config:clear && php artisan cache:clear` |
| DB 연결 실패 | 잘못된 DB 자격증명 | `.env`의 DB 설정 확인 |
| 파일 권한 오류 | `storage/`, `bootstrap/cache/` 권한 | `chmod -R 775 storage bootstrap/cache` |
| 404 Not Found (API) | 라우트 캐시 문제 | `php artisan route:clear && php artisan route:cache` |
@switch($project['frontend_stack'])
@case('react')
| React 빌드 오류 | Node.js 버전 불일치 | Node.js >= 18.x 확인, `nvm use 18` |
@break
@case('vue')
| Vue 빌드 오류 | Node.js 버전 불일치 | Node.js >= 18.x 확인, `nvm use 18` |
@break
@default
| 화면 로딩 안됨 | 경로 문제 | 웹서버 document root 확인 |
@endswitch

---

*이 문서는 AI Agent (T49)에 의해 자동 생성되었습니다. 배포 환경에 맞게 수정하여 사용하십시오.*
