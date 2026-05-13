# {{ $project['name'] }} 마이그레이션 가이드

> 생성일: {{ $metadata['generated_at']->format('Y년 m월 d일') }}
> 버전: {{ $metadata['version'] }}
> 모드: 신규 프로젝트 설치

---

## 1. 가이드 안내

이 문서는 **{{ $project['name'] }}** 시스템의 초기 설치 및 데이터 셋업을 안내합니다.

### 적용 시나리오

✅ **신규 프로젝트 (현재)**: 처음부터 새로 시작
- 빈 데이터베이스에서 시작
- 시스템 관리자 계정 생성
- 초기 데이터 시딩

⚠️ **기존 시스템 전환 (지원 예정)**:
- 기존 DB의 데이터를 새 스키마로 이전
- 사용자 계정 마이그레이션
- 자세한 안내는 시스템 관리자에게 문의

---

## 2. 사전 준비

### 2.1 백업 (기존 데이터가 있는 경우)

⚠️ 진행 전 반드시 백업하세요:

```bash
# 데이터베이스 전체 백업
mysqldump -u root -p --all-databases > backup_$(date +%Y%m%d_%H%M).sql

# 파일 시스템 백업
tar -czf files_backup_$(date +%Y%m%d).tar.gz /var/www/html/
```

### 2.2 환경 요구사항 확인

| 항목 | 최소 요구사항 |
|------|---------------|
| PHP | >= 8.2 |
| MySQL | >= 8.0 |
| 디스크 여유 공간 | >= 2GB |
| 메모리 | >= 512MB |

### 2.3 다운타임 공지

서비스 중단이 필요한 경우 사용자에게 사전 공지:
- 점검 예정 일시
- 예상 소요 시간 (보통 30분 ~ 2시간)
- 영향 범위

---

## 3. 데이터베이스 초기화

### 3.1 새 데이터베이스 생성

```sql
CREATE DATABASE {{ $project['db_name'] }}
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

-- 애플리케이션 전용 DB 사용자 생성 (권장)
CREATE USER '{{ $project['db_name'] }}_user'@'localhost' IDENTIFIED BY 'strong_password_here';
GRANT ALL PRIVILEGES ON {{ $project['db_name'] }}.* TO '{{ $project['db_name'] }}_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3.2 스키마 적용

```bash
# 패키지의 03-dev-prep/erd.sql 실행
mysql -u root -p {{ $project['db_name'] }} < 03-dev-prep/erd.sql

# 또는 Laravel 마이그레이션 실행
php artisan migrate --force
```

@if($database['has_erd'] && !empty($database['tables']))
### 3.3 생성될 테이블 ({{ count($database['tables']) }}개)

| 테이블명 | 컬럼 수 | 설명 |
|---------|---------|------|
@foreach($database['tables'] as $table)
| `{{ $table['name'] }}` | {{ $table['columns_count'] }} | {{ $table['description'] ?: '—' }} |
@endforeach

@if($database['has_relationships'])
> 테이블 간 외래키 관계가 있습니다. `erd.sql`의 ALTER TABLE 문을 순서대로 실행하세요.
@endif
@else
> ERD가 없습니다. `php artisan migrate` 명령으로 마이그레이션을 실행하세요.
@endif

---

## 4. 시스템 관리자 계정 생성

### 4.1 Laravel Artisan (권장)

```bash
cd /path/to/project
php artisan tinker
```

Tinker 프롬프트에서:

```php
$user = new App\Models\User();
$user->name     = '시스템 관리자';
$user->email    = '{{ $admin_setup['recommended_admin_email'] }}';
$user->password = bcrypt('your_secure_password_here');
$user->save();
echo "생성 완료: ID " . $user->id;
exit;
```

### 4.2 SQL 직접 삽입 (대안)

```sql
USE {{ $project['db_name'] }};

INSERT INTO users (name, email, password, created_at, updated_at)
VALUES (
    '시스템 관리자',
    '{{ $admin_setup['recommended_admin_email'] }}',
    '$2y$10$여기에bcrypt해시를넣으세요',
    NOW(),
    NOW()
);
```

> 비밀번호 해시 생성: `php -r "echo password_hash('your_password', PASSWORD_BCRYPT);"`

@if($rbac['has_rbac'] && $rbac['admin_role_key'])
### 4.3 관리자 권한 부여

@if(!empty($rbac['roles']))
**시스템 역할 목록**:

| 역할 키 | 역할 이름 | 설명 |
|--------|---------|------|
@foreach($rbac['roles'] as $role)
| `{{ $role['key'] ?? $role['name'] ?? '—' }}` | {{ $role['name'] ?? '—' }} | {{ $role['description'] ?? '—' }} |
@endforeach

@endif
자동 감지된 관리자 역할: **`{{ $rbac['admin_role_key'] }}`**

```bash
php artisan tinker

# 방금 생성한 관리자에게 역할 부여
$user = App\Models\User::where('email', '{{ $admin_setup['recommended_admin_email'] }}')->first();
$user->role = '{{ $rbac['admin_role_key'] }}';
$user->save();
exit;
```

@endif

---

## 5. 초기 데이터 시딩 (선택)

### 5.1 기본 데이터 추가

프로젝트에 따라 카테고리, 분류 코드, 시스템 설정 값 등의 초기 데이터가 필요할 수 있습니다:

```bash
# 전체 시드 실행
php artisan db:seed

# 특정 시더만 실행
php artisan db:seed --class=RolesTableSeeder
```

### 5.2 데이터 검증

```sql
-- 테이블별 행 개수 확인
SELECT TABLE_NAME, TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = '{{ $project['db_name'] }}'
ORDER BY TABLE_ROWS DESC;
```

---

## 6. 검증

### 6.1 시스템 연결 확인

```bash
# DB 연결 확인
php artisan db:show

# 라우트 목록 확인
php artisan route:list --compact

# 설정 캐시 재구성
php artisan config:clear && php artisan config:cache
```

### 6.2 Frontend 접속 테스트

@if($project['frontend_stack'] === 'HTML')
브라우저에서 직접 `index.html` 열기 또는 웹서버를 통해 접속.
@else
```bash
cd 04-frontend/
npm install
npm run dev
# http://localhost:5173 (또는 Vite가 지정한 포트)
```
@endif

### 6.3 관리자 로그인 테스트

1. 브라우저에서 시스템 URL 접속
2. 이메일: `{{ $admin_setup['recommended_admin_email'] }}`로 로그인
3. 관리자 메뉴 접근 가능 확인
4. [MANUAL.md](MANUAL.md)의 기본 기능 테스트

---

## 7. 환경 변수 최종 확인

`.env` 파일에서 아래 항목이 올바르게 설정되었는지 확인하세요:

```env
APP_NAME="{{ $project['name'] }}"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_DATABASE={{ $project['db_name'] }}
DB_USERNAME={{ $project['db_name'] }}_user
DB_PASSWORD=your_db_password

CACHE_DRIVER=file
SESSION_DRIVER=database
QUEUE_CONNECTION=sync
```

---

## 8. 운영 전환

### 8.1 최적화

```bash
# 캐시 최적화 (배포 후 한 번만)
php artisan config:cache
php artisan route:cache
php artisan view:cache
composer install --optimize-autoloader --no-dev
```

### 8.2 파일 권한

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### 8.3 스토리지 링크

```bash
php artisan storage:link
```

---

## 9. 롤백 계획

문제 발생 시 즉각 롤백:

### 9.1 빠른 롤백 (DB)

```bash
# 백업에서 복원
mysql -u root -p {{ $project['db_name'] }} < backup_YYYYMMDD_HHMM.sql
```

### 9.2 마이그레이션 롤백

```bash
# 마지막 마이그레이션 롤백
php artisan migrate:rollback

# 특정 단계까지 롤백
php artisan migrate:rollback --step=5
```

### 9.3 완전 초기화

```bash
# 모든 테이블 삭제 후 재생성
php artisan migrate:fresh

# 시드 포함
php artisan migrate:fresh --seed
```

---

## 10. 자주 묻는 질문

### Q. 마이그레이션 실행 중 오류 발생

1. 오류 메시지 전체 확인 (`storage/logs/laravel.log`)
2. MySQL 버전 확인 (`mysql --version`)
3. PHP 버전 확인 (`php -v`)
4. 백업에서 복원 후 재시도

### Q. `SQLSTATE[42S01]: Base table or view already exists`

```bash
php artisan migrate:fresh   # 기존 테이블 모두 삭제 후 재실행
```

또는 특정 테이블만 삭제:

```sql
DROP TABLE IF EXISTS 테이블명;
```

### Q. 관리자 로그인이 안 돼요

1. 비밀번호 재설정: `php artisan tinker` → `$user->password = bcrypt('new_pw'); $user->save();`
2. 이메일 대소문자 확인
3. 역할/권한 재확인

### Q. 기존 시스템 데이터가 있는데 어떻게 이전하나요?

현재 가이드는 신규 프로젝트 기준입니다.
기존 데이터 이전이 필요한 경우 **별도 데이터 마이그레이션 계획**이 필요합니다.
시스템 관리자 또는 개발팀에 문의하세요.

---

## 11. 다음 단계

설치 완료 후:

1. ✅ [DEPLOY.md](DEPLOY.md) 참고하여 배포 환경 구성
2. 📖 [MANUAL.md](MANUAL.md)를 사용자에게 배포
3. 🔍 모니터링 활성화 (`storage/logs/` 감시)
4. 📞 사용자 지원 채널 오픈

---

_본 가이드는 AI Agent (T51)에 의해 자동 생성되었습니다._
_환경에 따라 일부 명령어가 다를 수 있습니다. 프로덕션 적용 전 충분한 테스트를 권장합니다._
