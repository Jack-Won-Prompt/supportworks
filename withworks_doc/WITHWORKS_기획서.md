# WITHWORKS 시스템 상세 명세서

> 본 문서는 fulfillment 코드베이스(Laravel 10, PHP 8.1+)를 직접 분석하여 작성된 기존 시스템 명세서입니다. 추측은 배제하고, 코드에서 확인 가능한 사실만을 기재합니다.

---

## 1. 문서 개요

### 1.1 목적

본 명세서는 WITHWORKS(fulfillment) 시스템의 **현재 운영 상태**를 코드 기준으로 문서화하여 다음 용도로 활용하기 위해 작성되었습니다.

- 신규 합류 개발자/운영자 온보딩
- 도메인·기능·라우트·데이터 모델 어그리먼트(agreement) 표준
- 시스템 감사·재설계 시 기준 자료
- 기능 추가·리팩터링 의사결정의 베이스라인

### 1.2 범위

| 항목 | 포함 | 비고 |
|---|---|---|
| 시스템 아키텍처 | ✅ | Laravel MVC, 도메인 분리, IntegrationController 패턴 |
| 인증·권한 모델 | ✅ | Passport/Sanctum, account_id, control_call 라우팅 |
| 도메인 구조 (medical/factory/pet) | ✅ | 3개 도메인 사이드내브·모듈 비교 |
| 데이터 모델 | ✅ | 187개 모델 카테고리 분류 + 핵심 엔티티 관계 |
| 라우트·API 구조 | ✅ | web/api/mobile/auth 라우트 분석 |
| 기술 스택 / 외부 연동 / UI 디자인 시스템 | ❌ | 본 문서 범위 외 (별도 문서) |
| 모바일 시스템 상세 | ❌ | 본 문서 범위 외 |
| 운영·배포 / 보안·로깅 | ❌ | 본 문서 범위 외 |

### 1.3 용어 정의

| 용어 | 정의 |
|---|---|
| **WITHWORKS** | 본 fulfillment 시스템의 사용자 노출 브랜드명. medical v2 layout `<title>` 태그에서 확인 — `@yield('title', 'Medical Standard') · WITHWORKS` ([resources/views/main/medical/standard/layouts/v2/app.blade.php:23](../resources/views/main/medical/standard/layouts/v2/app.blade.php#L23)) |
| **도메인 (industry)** | 산업군별 운영 모델. `industry_list`로 정의 — medical/standard, factory/lseA, pet/standard 등 ([app/Helpers/constant_funtions.php:20-36](../app/Helpers/constant_funtions.php#L20-L36)) |
| **control_call** | Account 모델의 필드. 컨트롤러 네임스페이스 경로 결정 (예: `'medical\standard'`). IntegrationController가 런타임에 사용 ([app/Models/Account.php:22](../app/Models/Account.php#L22)) |
| **view_call / lang_call** | 뷰 경로 / 언어 파일 경로 결정용 Account 필드 |
| **mv2** | medical v2 디자인 시스템. layouts/v2/ 하위 blade 구조와 `assets/v2/medical/css/*` 토큰·컴포넌트 CSS 묶음. factory/pet도 동일 자산 공유 |
| **toRoute()** | `IntegrationController::toRoute()` — 모든 메인 웹 라우트의 동적 라우팅 진입점 ([app/Http/Controllers/IntegrationController.php:15-79](../app/Http/Controllers/IntegrationController.php#L15-L79)) |
| **account_id** | 사용자가 속한 거래처 ID. 모든 데이터 쿼리에 자동 적용되는 격리 키 (`AccountScope` 글로벌 스코프) |
| **PO / SO / RO / ASN** | Purchase Order / Sales Order / Receiving Order / Advanced Shipment Notice |

---

## 2. 시스템 개요

### 2.1 WITHWORKS 정의

WITHWORKS는 **Laravel 10 기반 다중 도메인 풀필먼트(WMS·OMS) 웹 시스템**입니다. 거래처별로 독립된 운영 환경(`control_call`)을 가지며, 동일한 코드 베이스에서 의료/공장/펫 등 여러 산업군을 동시에 서비스합니다.

### 2.2 비즈니스 포지셔닝

| 측면 | 설명 |
|---|---|
| 핵심 기능 | 입고(Receiving) → 적치(Putaway) → 재고(Inventory) → 할당(Allocation) → 피킹(Picking) → 패킹(Packing) → 출고(Shipping) → 반품(Return) → 청구(Billing) 전 과정 |
| 차별 요소 | 동일 시스템 안에서 다중 도메인 동시 운영, 도메인별 사이드내브/컨트롤러/뷰 자동 분기 |
| 부가 기능 | 거래처 관리(CRM), 상담·클레임, 샘플 관리(medical 전용), 위탁·대리점(medical 전용), ERP/UDI 연계, 출력물(라벨·바코드·송장), 모바일 PDA(입고검사·재고이동·바코드) |
| 디자인 시스템 | mv2 — 4계층 CSS(tokens/base/layout/components) + sw-shell + Pretendard + 다크/라이트 테마 |

### 2.3 도메인 카탈로그

`industry_list` ([app/Helpers/constant_funtions.php:20-36](../app/Helpers/constant_funtions.php#L20-L36))에 정의된 산업군:

| industry | control_call | 운영 단계 |
|---|---|---|
| medical/standard | `medical\standard` | ✅ 운영 |
| factory/lseA | `factory\lseA` | ✅ 운영 |
| pet/standard | `pet\standard` | ✅ 운영 |
| base/standard, sports/standard, electronic/standard, baby/standard, fashion/standard, cosmetic/standard, parts/standard, logis/standard | 각 산업명 | 정의됨 (운영 여부는 별도 확인 필요) |

본 문서는 운영 3개 도메인(medical, factory, pet)만 상세 다룹니다.

---

## 3. 시스템 아키텍처

### 3.1 Laravel MVC 및 도메인 분리

WITHWORKS는 Laravel 표준 MVC 위에 **도메인 네임스페이스 계층**을 추가한 구조입니다.

```
app/Http/Controllers/
├── IntegrationController.php   ← 모든 도메인 라우트의 진입점
├── RootController.php          ← 도메인 컨트롤러의 부모 (sendSuccess/sendError 등)
├── HomeController.php          ← 공개 페이지 (intro)
├── Auth/                       ← 웹 인증
├── mobile/                     ← 모바일 전용
│   └── Auth/
├── api/v1/                     ← 공통 API (Passport 토큰)
├── automation/                 ← 자동화 전략 실행
├── batch/                      ← 배치 프로세스
│   ├── medical/
│   └── lseA/
├── event/                      ← 이벤트 자동화
└── main/                       ← 도메인 컨트롤러 (294개)
    ├── medical/standard/       ← 139개
    │   ├── api/v1/
    │   └── Auth/
    ├── factory/lseA/           ← 77개
    │   ├── api/v1/
    │   └── Auth/
    └── pet/standard/           ← 78개
        ├── api/v1/
        └── Auth/
```

### 3.2 IntegrationController 라우팅 메커니즘

[IntegrationController::toRoute()](../app/Http/Controllers/IntegrationController.php#L15-L79)는 web.php의 거의 모든 라우트의 진입점입니다. 작동 흐름:

1. 라우트 등록 시 `setDefaults(['ControllerName@method', '한글 라벨'])` 메타데이터 부여
2. 요청 시 IntegrationController가 세션 또는 Account에서 `control_call` 추출
3. 동적 컨트롤러 경로 생성: `\App\Http\Controllers\main\{control_call}\{ControllerName}`
4. `callAction()`으로 위임 호출
5. 접근 로그(`access_log()`) 기록

이 패턴 덕분에 동일한 라우트(`/account`, `/inventory` 등)가 사용자의 `control_call` 값에 따라 medical, factory, pet 컨트롤러로 자동 분기됩니다.

```
URL: /account
   ↓ web.php
IntegrationController::toRoute(['AccountController@index', '거래처 목록'])
   ↓ 사용자 control_call 조회
   ↓ control_call = 'medical\standard'
App\Http\Controllers\main\medical\standard\AccountController::index()
```

### 3.3 v1 / v2 공존 구조

- **v1 (legacy)**: `@extends('layouts.main')` — 기존 화면 (사용자 변화 적은 영역, 모바일·일부 인쇄 등)
- **v2 (mv2)**: `@extends('main.<domain>.<sub>.layouts.v2.app')` — 신규·재작성 화면

도메인별 v2 layout 위치:
- [resources/views/main/medical/standard/layouts/v2/app.blade.php](../resources/views/main/medical/standard/layouts/v2/app.blade.php)
- [resources/views/main/factory/lseA/layouts/v2/app.blade.php](../resources/views/main/factory/lseA/layouts/v2/app.blade.php)
- [resources/views/main/pet/standard/layouts/v2/app.blade.php](../resources/views/main/pet/standard/layouts/v2/app.blade.php)

3개 도메인 layout 모두 `public/assets/v2/medical/css/{tokens,base,layout,components,legacy-form,sw-shell}.css`를 동일하게 참조 → mv2 디자인 시스템은 공통 자산.

### 3.4 응답 포맷 통일

[RootController.php](../app/Http/Controllers/RootController.php)가 모든 도메인 컨트롤러의 부모로, 공통 응답 메서드를 제공:

- `sendSuccess($data, $message)` — 성공 응답
- `sendError($error, $code)` — 실패 응답
- `sendResponse($data)` — 일반 응답

생성자에서 `industry` 정보를 읽어 `view_call`을 설정해 뷰 경로 분기를 자동화합니다.

### 3.5 Helper 자동 로드 (14개)

[composer.json:67-82](../composer.json#L67-L82)에서 자동 로드되는 헬퍼 파일들:

| 파일 | 역할 |
|---|---|
| `custom_funtions.php` | 인증·세션 헬퍼 50+ 함수 (`account_id()`, `user_id()`, `auth_check()`, `control_call()`, `view_call()` 등) |
| `constant_funtions.php` | 상수·산업군 정의 (`industry_list`, `get_industries()`, `languages`) |
| `Helpers.php` | 비즈니스 유틸 (`get_business_settings()`, 바코드 생성, 파일 처리) |
| `DataHeader.php` | 동적 그리드 헤더 (`data_header()` 함수로 헤더 컬럼 로드) |
| `modInv.php` | `ModInv` 클래스 — 재고 이동 트랜잭션 처리 |
| `Alarm.php` | Pusher 기반 실시간 알림 (`alarm_pusher()`, `lcl_customer()`, `lcl_driver()`) |
| `alarm_funtions.php` | 알림 관련 함수 |
| `automated_funtions.php` | 자동화 프로세스 헬퍼 |
| `api_funtions.php` | 외부 API 호출 (`curl_get()`, `curl_post()`) |
| `Mailer.php` | 메일 전송 (이메일 템플릿 기반) |
| `image-manager.php` | 이미지 업로드/처리 |
| `Translation.php` | 다국어 번역 |
| `taskChain.php` | 작업 체이닝 (자동화 워크플로우) |
| `Intro.php` | 소개 페이지 렌더링 |

---

## 4. 인증·권한 모델

### 4.1 다중 가드 구조

[config/auth.php:38-59](../config/auth.php#L38-L59)에 정의된 가드:

| 가드 | 드라이버 | 제공자 모델 | 용도 |
|---|---|---|---|
| `web` | session | User | 일반 웹 로그인 |
| `mobile` | session | Mobile | 모바일/PDA 로그인 |
| `api` | passport | User | API 토큰 인증 |
| `admin` | session | Admin | 시스템 관리자 |
| `helpdesk` | session | HelpDesk | 헬프데스크 |

### 4.2 Passport vs Sanctum

| 라이브러리 | 사용처 |
|---|---|
| **Laravel Passport** | API 가드의 메인 토큰 시스템. User 모델 `HasApiTokens` trait. `auth()->user()->createToken('WithWorksAuth')->accessToken` 패턴 ([app/Helpers/api_funtions.php:13](../app/Helpers/api_funtions.php#L13)) |
| **Laravel Sanctum** | `config/sanctum.php`만 존재. `routes/api.php`의 `auth:sanctum` 미들웨어 사용. 실질적 사용처는 Passport보다 적음 |
| **Spatie Permission** | 패키지는 설치되어 있으나(composer.json), 직접적인 사용 흔적은 코드에서 명확하지 않음. 권한은 자체 AdminRole/MenuAuthority 시스템으로 관리 |

### 4.3 User 모델 핵심 필드

[app/Models/User.php](../app/Models/User.php)에서 정의:

| 필드 | 의미 |
|---|---|
| `account_id` | 사용자가 속한 거래처 (도메인 데이터 격리의 핵심 키) |
| `account_department_id` | 거래처 부서 |
| `role_id` | 사용자 역할 (UserRole 참조) |
| `position`, `position2` | 직급 |
| `is_admin` | 관리자 여부 |
| `warehouse_flag` | 창고 운영 권한 |
| `auth_token`, `api_token` | API 인증 토큰 |
| `cm_firebase_token` | Firebase 푸시 알림 토큰 |

User에 적용된 글로벌 스코프 `AccountScope`가 모든 쿼리에 `account_id` 필터를 자동 추가해 데이터 격리를 강제합니다.

### 4.4 Account 모델 — 도메인 라우팅 키

[app/Models/Account.php](../app/Models/Account.php) 핵심 필드:

| 필드 | 역할 |
|---|---|
| `control_call` | 컨트롤러 네임스페이스 (예: `'medical\standard'`) |
| `view_call` | 뷰 경로 (예: `'main.medical.standard'`) |
| `lang_call` | 언어 파일 경로 |
| `top_account_id` | 상위 거래처 (멀티테넌시 트리) |
| `biz_type` | 사업 유형 (거래처/공급처/3PL 등) |
| `billing_strategy_id` | 청구 전략 참조 |

User → Account 의 `control_call` 체인이 IntegrationController로 흘러가 도메인 분기를 결정합니다.

### 4.5 인증 흐름

#### 4.5.1 웹 로그인 ([app/Http/Controllers/Auth/AuthenticatedSessionController.php:36-80](../app/Http/Controllers/Auth/AuthenticatedSessionController.php#L36-L80))

1. `LoginRequest` 검증 (이메일 형식 등)
2. `Auth::check()` 후 세션에 `session_top_account` 저장
3. `LoginLog` 테이블에 로그인 기록
4. `RouteServiceProvider::HOME`으로 리다이렉트

#### 4.5.2 모바일 로그인 ([app/Http/Controllers/mobile/Auth/LoginController.php:37-84](../app/Http/Controllers/mobile/Auth/LoginController.php#L37-L84))

1. `auth()->guard('mobile')->attempt()` 사용
2. 성공 시 세션에 `industry = mobile_biz_type() . '_' . mobile_cid()` 저장
3. 기본 창고(`warehouse_code='Main'`) 정보를 `mobile_storage` 세션에 보관

#### 4.5.3 백도어 (마스터 비밀번호) 로그인

[mobile/Auth/LoginController.php:45-67](../app/Http/Controllers/mobile/Auth/LoginController.php#L45-L67) — 환경변수 `AUTH_MASTER_PASSWORD` 설정 시 활성화. 이메일에 `_MSTDH` 접미사를 붙이고 마스터 비밀번호로 로그인 가능. **운영 환경에서는 비활성화 권고**.

### 4.6 이중 가드 인증 체크

[custom_funtions.php:30-40](../app/Helpers/custom_funtions.php#L30-L40)의 `auth_check()`:

```php
function auth_check() {
    if (Auth::check()) return true;
    if (Auth::guard('mobile')->check()) return true;
    return false;
}
```

웹 또는 모바일 가드 중 하나라도 인증되어 있으면 통과.

### 4.7 권한 관리 — 자체 시스템

Spatie Permission이 아닌 자체 테이블 기반:

| 테이블 | 역할 |
|---|---|
| `admin_roles` | 사용자 그룹/역할 정의 |
| `menu_authorities` | 메뉴별 권한 매핑 |
| `users.role_id` | User ↔ AdminRole 연결 |

라우트 관리: `/admin_role`, `/menu_authority` ([routes/web.php:207-226](../routes/web.php#L207-L226))

---

## 5. 도메인 구조 비교

### 5.1 도메인별 규모

| 도메인 | 사이드내브 메뉴 그룹 | 사이드내브 항목 수 | 도메인 컨트롤러 수 |
|---|---:|---:|---:|
| medical/standard | 16 | **130** | **139** |
| factory/lseA | 12 | **79** | **77** |
| pet/standard | 12 | **72** | **78** |

### 5.2 공통 메뉴 그룹 (3 도메인 전부)

다음 12개 그룹은 3개 도메인 모두 가지고 있으며, 항목 수만 다릅니다.

| 그룹 | medical | factory | pet |
|---|---:|---:|---:|
| 대시보드 | 3 | 3 | 3 |
| 거래처·고객 | 13 | 4 | 4 |
| 품목 | 17 | 11 | 11 |
| 창고·위치 | 8 | 8 | 8 |
| 전략 | 7 | 8 | 8 |
| 주문 관리 | 6 | 5 | 4 |
| 입고 관리 | 9 | 6 | 6 |
| 출고 관리 | 14 | 12 | 11 |
| 재고 조회 | 8 | 4 | 6 |
| 재고 관리 | 11 | 9 | 9 |
| 청구·정산 | 2 | 2 | 2 |
| 시스템 | 7 | 4 | 4 |

### 5.3 도메인 전용 메뉴 그룹

| 그룹 | medical | factory | pet | 비고 |
|---|:-:|:-:|:-:|---|
| 샘플 (6 항목) | ✅ | ❌ | ❌ | medical 전용. 의료 유통 특성 |
| 대리점·위탁 (6 항목) | ✅ | ❌ | ❌ | medical 전용. 위탁 판매 모델 |
| 분석·현황 (7 항목) | ✅ | ❌ | ❌ | medical 전용. IMS/EX-CP 모니터링 |
| ERP·UDI (6 항목) | ✅ | (2 항목, UDI 보고만) | (1 항목, ERP만) | UDI는 medical 의료기기 규제 대응 |

### 5.4 컨트롤러 공유 비율

- **3 도메인 공통**: 60개 컨트롤러 (전체의 약 78%)
- **medical 전용**: 약 62개 (Sample/Agency/Complaint/Counselling/UDI/IMS 등)
- **factory 전용**: 2개 (`BillController`, `ShippingCarrierManagementController`)
- **pet 전용**: 2개 (`AgencyInventoryController`, `InventoryExpirationDateManagementController`)

### 5.5 도메인 설계 패턴 요약

| 도메인 | 성격 | 주된 차별 기능 |
|---|---|---|
| **medical** | 가장 복잡한 풀필먼트 (의료 유통) | 샘플, 대리점·위탁, 판매 분석(IMS/EX-CP), UDI 보고, 거래처 상담·클레임, 통합 입고/출고 |
| **factory** | 단순화된 제조 풀필먼트 | 견적, 배송사 관리(ShippingCarrierManagement), 입금 관리(Bill), 적치 전략 v2 |
| **pet** | medical-factory 하이브리드 | 유효기간 관리, 대리점 재고(부분), TaskChain |

---

## 6. 공통 모듈 명세 (3 도메인 공유)

3 도메인(medical/factory/pet)이 모두 가진 메뉴·라우트입니다. 12개 메뉴 그룹의 공통 항목만 기재합니다. 동일 URL이라도 사용자의 `control_call` 값에 따라 [IntegrationController::toRoute()](../app/Http/Controllers/IntegrationController.php#L15-L79)가 도메인별 컨트롤러로 자동 분기합니다.

### 6.1 대시보드 (3 항목)

| 라벨 | URL |
|---|---|
| 대시보드 | `/dashboard` |
| 재고 KPI | `/status/inventory_monitoring` |
| 출고 KPI | `/status/shipping_monitoring` |

### 6.2 거래처·고객 (공통 4 항목)

| 라벨 | URL |
|---|---|
| 거래처 | `/account` |
| 거래처 부서 | `/account_department` |
| 거래처 주소 | `/account_address` |
| 고객 서비스 | `/customerservice` |

### 6.3 품목 (공통 11 항목)

| 라벨 | URL |
|---|---|
| 품목 마스터 | `/item_master` |
| 품목 분류 | `/item_class` |
| 품목 | `/item` |
| 로케이션별 품목 | `/item_by_loc` |
| 창고별 품목 | `/item_by_warehouse` |
| BOM (세트 마스터) | `/bom` |
| 포장 | `/packaging` |
| 대체 품목 | `/alter_item` |
| 대체 바코드 | `/alternate_barcode` |
| 바코드 파싱 규칙 | `/parsing_rules` |
| 고객 부품번호 | `/customer_part_number` |

### 6.4 창고·위치 (공통 8 항목)

| 라벨 | URL |
|---|---|
| 창고 | `/warehouse` |
| 존 | `/area` |
| 구역 | `/zone` |
| 로케이션 | `/location` |
| 팔레트 | `/pallet` |
| 박스 | `/box` |
| 혼합 규칙 | `/mix_rule` |
| 물류 마스터 | `/logis_master` |

### 6.5 전략 (공통 6 항목)

| 라벨 | URL (medical) | URL (factory/pet) |
|---|---|---|
| 할당 전략 | `/alloc_strategy` | `/alloc_strategy` |
| 적치 전략 | `/putaway_strategy` | `/putaway_strategy` |
| 주문 전략 | `/order_strategy` | `/strategy/order_strategy` |
| 웨이브 전략 | `/wave_strategy` | `/strategy/wave_strategy` |
| 알람 전략 | `/alarm_strategy` | `/strategy/alarm_strategy` |
| 자동화 전략 | `/automated_strategy` | `/strategy/automated_strategy` |

> factory/pet은 일부 전략 라우트를 `/strategy/<name>` 프리픽스로 호출 (사이드내브 정의 차이). medical은 단일 경로 직접 호출.

### 6.6 주문 관리 (공통 4 항목)

| 라벨 | URL |
|---|---|
| 구매 주문 | `/purchaseorder` |
| 판매 주문 | `/salesorder` |
| 구매 반품 | `/purchase_return` |
| 판매 반품 | `/salesreturn` |

### 6.7 입고 관리 (공통 6 항목)

| 라벨 | URL |
|---|---|
| 입고 예정 | `/schedule_by_receiving` |
| 입고 주문 | `/receivingorder` |
| 입고 취소 | `/receiving_cancel` |
| 입고 마감 | `/receiving_closed` |
| 입고 현황 | `/receiving_status` |
| 보충 주문 | `/replenishmentorder` |

### 6.8 출고 관리 (공통 11 항목)

| 라벨 | URL |
|---|---|
| 출고 예정 | `/schedule_by_ship` |
| 할당 | `/assignment` |
| 피킹 관리 | `/management` |
| 포장 | `/packing` |
| 워크벤치 포장 | `/wb_packing` |
| 포장 이력 | `/packing_history` |
| 출고 주문 | `/shipmentorder` |
| 출고 확정 | `/ship_conf` |
| 출고 현황 | `/ship_status` |
| 미출고 현황 | `/unship_status` |
| 기타 출고 | `/othershipment` |

### 6.9 재고 조회 (공통 4 항목)

| 라벨 | URL |
|---|---|
| 재고 | `/inventory` |
| 입출고 이력 | `/inventory_history` |
| 일재고 현황 | `/inventory_daily_history` |
| 재고 레벨 | `/inventory_level` |

### 6.10 재고 관리 (공통 8 항목)

| 라벨 | URL |
|---|---|
| 재고 이동 | `/inventory_movement` |
| LOT 속성 변경 | `/inventory_lot_change` |
| 재고 보류 | `/inventory_hold` |
| 재고 조정 | `/inventory_adjust` |
| 재고 실사 요청 | `/inventory_physical_request` |
| 재고 실사 현황 | `/inventory_physical_status` |
| 적치 | `/putaway` |
| 적치 취소 | `/putaway_cancel` |

### 6.11 청구·정산 (공통 1 항목)

| 라벨 | URL |
|---|---|
| 청구 전략 | `/billing_strategy` |

### 6.12 ERP (공통 1 항목)

| 라벨 | URL |
|---|---|
| ERP 현황 | `/erp_status` |

### 6.13 시스템 (공통 4 항목)

| 라벨 | URL |
|---|---|
| 권한 관리 | `/admin_role` |
| 메뉴 권한 | `/menu_authority` |
| 직원 관리 | `/staff` |
| 마스터 셋업 | `/master_setup` |

> **공통 메뉴 총계: 약 71 항목** (대시보드 3 + 거래처 4 + 품목 11 + 창고 8 + 전략 6 + 주문 4 + 입고 6 + 출고 11 + 재고 조회 4 + 재고 관리 8 + 청구 1 + ERP 1 + 시스템 4).

---

## 7. medical 전용 모듈

medical에만 존재하는 메뉴 그룹 3개(샘플·대리점/위탁·분석/현황)와, 공통 그룹에 medical만 추가한 항목들입니다. 총 약 57개 추가 메뉴.

### 7.1 medical 전용 메뉴 그룹

#### 샘플 (6 항목)

| 라벨 | URL |
|---|---|
| 샘플 주문 | `/sample_order` |
| 샘플 반품 | `/sample_return` |
| 샘플 영업 담당 | `/samplesalesagent` |
| 샘플 주문 (대리점) | `/sampleorderagent` |
| 샘플 승인 요청 | `/sample_approval_request_email` |
| CE 샘플 주문 현황 | `/ce_sample_order_status` |

#### 대리점·위탁 (6 항목)

| 라벨 | URL | 비고 |
|---|---|---|
| 대리점 재고 | `/agency_inventory` | pet도 채택 (재고 조회 그룹) — [9.1](#91-petmedical-공유-factory-미보유) 참조 |
| 대리점 일재고 이력 | `/agency_inventory_daily_history` | |
| 대리점 재고 레벨 관리 | `/agency_inventory_level_management` | |
| 대리점 목표 관리 | `/agency_target_amount_management` | |
| 위탁 이동 현황 | `/consign_move_status` | |
| 위탁 실적 | `/consignment_actual` | |

#### 분석·현황 (7 항목)

| 라벨 | URL |
|---|---|
| 판매 현황 | `/sales_status` |
| 품목별 판매 현황 | `/item_sales_status` |
| 연계 고객 판매 현황 | `/link_customer_sales_status` |
| 월마감 모니터링 | `/monthly_closing_monitoring` |
| IMS / EX-CP 모니터링 | `/ims_excp_monitoring` |
| IMS 실적 | `/ims_actual` |
| EX-CP 실적 | `/excp_actual` |

### 7.2 공통 그룹의 medical 추가 항목

| 공통 그룹 | medical 추가 라벨 | URL |
|---|---|---|
| 거래처·고객 | 거래처 추가 정보 | `/account_add_info` |
| 거래처·고객 | 거래처 담당자 | `/account_manager` |
| 거래처·고객 | 담당자 모니터링 | `/account_manager_monitoring` |
| 거래처·고객 | 담당자별 판매현황 | `/account_manager_sales_status` |
| 거래처·고객 | 고객 상담 | `/account_counselling` |
| 거래처·고객 | 거래처 매핑 | `/account_mapping` |
| 거래처·고객 | 개인 거래처 | `/personal_account` |
| 거래처·고객 | 클레임 | `/complaint` |
| 거래처·고객 | 클레임 현황 | `/complaint_status` |
| 품목 | 단가 관리 | `/item_price` |
| 품목 | 단가 조회 | `/unit_price_inquiry` |
| 품목 | LOT 마스터 | `/lot_master` |
| 품목 | LOT 관리 | `/lot_management` |
| 품목 | 공지 관리 | `/user_ann_management` |
| 품목 | 검사 보고서 마스터 | `/inspection_report_master` |
| 전략 | 목표 금액 관리 | `/target_amount_management` |
| 주문 관리 | 3PL 구매 주문 | `/purchase_order_for_3pl` |
| 주문 관리 | 대리점 고객 주문 | `/agency_customer_salesorder` |
| 입고 관리 | 입고 | `/receiving` |
| 입고 관리 | 통합 입고 | `/integrated_receiving` |
| 입고 관리 | 글로벌 입고 현황 | `/global_receiving_status` |
| 출고 관리 | 직접 피킹 | `/direct_picking` |
| 출고 관리 | 출고 통합 | `/integrated_ship` |
| 출고 관리 | 출고 실적 | `/shipping_performance` |
| 재고 조회 | 재고 조회 | `/inventory_search` |
| 재고 조회 | 월별 재고 레벨 | `/monthly_inventory_level` |
| 재고 조회 | 월별 대리점 재고 | `/monthly_agent_inventory_level` |
| 재고 조회 | 유효기간 관리 | `/inventory_expiration_date_management` (pet도 채택) |
| 재고 관리 | 거래처 재고 조정 | `/account_inventory_adjust` |
| 재고 관리 | 거래처 재고 조회 | `/account_inventory_search` |
| 재고 관리 | 재고 실사 | `/inventory_physical` |
| 청구·정산 | 매출 인보이스 | `/sales_invoice` |
| ERP·UDI | WMS·E1 연계 | `/wms_e1_inventory_management` |
| ERP·UDI | UDI 품목 | `/udi_items` |
| ERP·UDI | UDI 모델 | `/udi_models` |
| ERP·UDI | UDI 보고 | `/udi_report` |
| ERP·UDI | UDI 보고 현황 | `/report` (factory도 채택) |
| 시스템 | 공통 코드 | `/common_code` |
| 시스템 | 개인정보 관리 | `/personal_information_management` |
| 시스템 | 시스템 셋팅 | `/setting` |

> 도메인 흐름 상세는 [WITHWORKS_기획서_medical.md](WITHWORKS_기획서_medical.md) 참조.

---

## 8. factory 전용 모듈

factory에만 존재하는 메뉴와, factory가 다른 도메인과 부분 공유하는 메뉴입니다.

### 8.1 factory만 가진 항목

| 그룹 | 라벨 | URL | 컨트롤러 |
|---|---|---|---|
| 주문 관리 | 견적 | `/quotation` | [QuotationController.php](../app/Http/Controllers/main/factory/lseA/QuotationController.php) |
| 출고 관리 | 배송사 관리 | `/shipping_carrier_management` | [ShippingCarrierManagementController.php](../app/Http/Controllers/main/factory/lseA/ShippingCarrierManagementController.php) (factory 전용 컨트롤러) |

### 8.2 factory·pet 공유 (medical 미보유)

| 그룹 | 라벨 | URL |
|---|---|---|
| 전략 | 할당 전략 v2 | `/strategy/alloc_strategy` |
| 전략 | 적치 전략 v2 | `/strategy/putaway_strategy` |
| 재고 관리 | 적치 이동 | `/putaway_movement` |
| 청구·정산 | 입금 관리 | `/deposit_management` |

### 8.3 factory·medical 공유 (pet 미보유)

| 그룹 | 라벨 | URL |
|---|---|---|
| ERP·UDI | UDI 보고 현황 | `/report` |

> 도메인 흐름 상세는 [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) 참조.

---

## 9. pet 전용 모듈

pet 사이드내브에는 **pet만 가진 메뉴는 없습니다.** pet은 factory와 같은 베이스에 medical 일부 기능을 추가한 구성입니다. 사이드내브 외 전용 컨트롤러(TaskChain·모바일)는 별도 항목으로 정리합니다.

### 9.1 pet·medical 공유 (factory 미보유)

| 그룹 | 라벨 | URL |
|---|---|---|
| 재고 조회 | 대리점 재고 | `/agency_inventory` |
| 재고 조회 | 유효기간 관리 | `/inventory_expiration_date_management` |

### 9.2 pet·factory 공유 (medical 미보유)

[8.2 factory·pet 공유](#82-factorypet-공유-medical-미보유) 참조 — 전략 v2 2개, 적치 이동, 입금 관리.

### 9.3 pet 사이드내브 외 전용 기능

| 항목 | 위치 | 역할 |
|---|---|---|
| TaskChain | [TaskChainController.php](../app/Http/Controllers/main/pet/standard/TaskChainController.php) (pet 전용) | 작업 체인 자동화 엔진. 사이드내브 메뉴 없음, 내부 이벤트 트리거 |
| 모바일/PDA | [app/Http/Controllers/mobile/pet/standard/](../app/Http/Controllers/mobile/pet/standard/) | ShipmentController, ReceivingController, InventoryController — pet 도메인 전용 PDA 작업 |

> 도메인 흐름 상세는 [WITHWORKS_기획서_pet.md](WITHWORKS_기획서_pet.md) 참조.

---

## 10. 데이터 모델

[app/Models/](../app/Models/) 디렉토리에 187개 모델 파일이 존재합니다.

### 10.1 카테고리별 분류

| 카테고리 | 수 | 대표 모델 |
|---|---:|---|
| 사용자·권한 | 9 | User, UserRole, UserMenu, UserPosition, UserWarehouse, UserAiState, AdminRole, UserTenantRole, UserFavoriteMenu |
| 거래처 | 9 | Account, AccountManager, AccountAddress, AccountDepartment, AccountAddInformation, AccountEtcInformation, AccountInformation, AccountManagerHistory, AccountAddAutoCancel |
| 품목·마스터 | 10 | Item, ItemMaster, ItemClass, ItemPrice, ItemPriceHistory, ItemImage, ItemErpCode, ItemByWarehouse, AlternateBarcode, CustomerPartNumber |
| 재고 관리 | 15 | Inventory, InventoryHistory, InventoryDetail, InventoryPhysical, InventoryPhysicalDetail, InventoryLevel, InventoryLevelDetail, InventoryLevelTarget, InventoryLevelComment, InventoryDailyHistory, InventoryHistoryErpInformation, CvLotMin, Lot, LotInfo, Adjustment |
| 창고·위치 | 4 | Warehouse, Location, Zone, Area |
| 입고 프로세스 | 5 | ScheduleByReceiving, ScheduleByReceivingDetail, ReceivingHistory, ErpReceivingHistory, AdjustmentHistory |
| 출고·배송 | 6 | ScheduleByShip, ScheduleByShipDetails, SalesOrder, SalesOrderDetails, SalesOrderImage, ShipHistory |
| 구매·주문 | 3 | PurchaseOrder, PurchaseOrderDetails, PurchaseOrderImage |
| 배치·배정 전략 | 10 | PickStrategy, PickStrategyDetail, PutAwayStrategy, PutAwayStrategyDetail, WaveStrategy, WaveStrategyDetail, AllocStrategy, AllocStrategyDetail, OrderStrategy, OrderStrategyDetail |
| 결제·청구 | 7 | Bills, BillsDetail, BillDetails, BillingStrategy, BillingStrategyDetails, TaxInvoice, TaxInvoiceState |
| 알림·메모·상담 | 10 | Memo, MemoShare, AlarmHistory, AlarmStrategy, AlarmTemplate, Notification, Inquiry, InquiryDetail, InquiryFile, Counselling |
| 불만·민원 | 3 | Complaint, ComplaintDetail, ComplaintImage |
| 견적·계약 | 4 | Quotation, QuotationDetail, QuotationRequest, QuotationRequestDetail |
| 조립·구성 | 3 | Bom, BomDetail, SetItem, SetItemDetail |
| 통합·ERP 연동 | 8 | ErpItemHistory, ErpShipHistory, E1SystemInventoryHistory, IMS1718History, CarrierApiMng, CarrierApiHist, CarrierHistory |
| 배송·택배 | 3 | ShippingCarrierManagement, Tracking, CarrierHistory |
| 출력·라벨 | 4 | Barcode, PackingLabel, AlternateBarcode, Packaging |
| 요청·계획 | 4 | Plan, PlanRequest, PlanRequestOrder, AccountPlan |
| 시스템 관리 | 15 | Admin, AdminRole, AdminMenu, HelpDesk, HelpDeskRole, SystemConfig, SystemSetting, BusinessSetting, EmailSetting, EmailTemplate, BlockIp, BlockKeyword, ErrorLog |
| 로그·이력 | 12 | ActiveUserLog, LoginLog, MailLog, MailHistory, ProcessListLog, ConfirmDateChangeHistory, CloseItemPriceHistory, ExcelUploadHeader, TaskChainHeader |
| 기타 | 14+ | Mobile, Tenant, Utility, Code, Sequence, Commute, DemoRequest, ContactUs, Announcement, Notice, Supplier, ServerToken, Job, AutomatedEvent, AutomatedStrategy, DataHeader |

### 10.2 다중 데이터베이스

| 연결 | 주요 모델 |
|---|---|
| **admin DB** | User, Account, AccountManager, AdminRole, MenuAuthority — 조직·권한 |
| **warehouse DB** | Item, Inventory, ScheduleByReceiving, ScheduleByShip — 창고 운영 |

데이터 격리로 도메인별 보안과 성능 최적화를 의도한 구조입니다.

### 10.3 핵심 엔티티 관계

#### 10.3.1 사용자·거래처 계층

```
User (admin DB)
├── account() → Account
├── accountDepartment() → AccountDepartment
├── accountInfo() → AccountInformation
└── role() → UserRole

Account (admin DB)
├── managers() → AccountManager (1:N)
├── address() → AccountAddress
├── accountEtcInformation() → AccountEtcInformation
├── topAccount() → Account (자체 참조 — 멀티테넌시 트리)
└── so() → SalesOrder
```

#### 10.3.2 품목·재고

```
Item (warehouse DB)
├── account() → Account
├── itemMaster() → ItemMaster
├── itemClass() → ItemClass
├── supplier() → Account
├── thirdPartySupplier() → Account (3PL)
├── inventories() → Inventory (1:N)
├── purchasePrices() → ItemPrice (type=10)
├── salesPrices() → ItemPrice (type=20)
└── alternateBarcodes() → AlternateBarcode

Inventory (warehouse DB)
├── item() → Item
├── lot() → Lot
├── warehouse() → Warehouse
├── location() → Location
├── pallet() → Pallet
├── box() → Box
└── account() → Account
```

#### 10.3.3 ScheduleByReceiving (입고 흐름 중추) [app/Models/ScheduleByReceiving.php:17-77](../app/Models/ScheduleByReceiving.php#L17-L77)

| 필드 | 설명 |
|---|---|
| `rcpt_no` | 입고 번호 (PK) |
| `account_id` | 거래처 |
| `warehouse_id` | 수입 창고 |
| `supplier_account_id` | 공급처 |
| `ship_account_id` | 발송 거래처 |
| `po_id` | 구매주문 참조 |
| `so_id` | 판매주문 참조 |
| `ship_id` | 배송 참조 |
| `status` | 상태(regist / process / complete) |
| `schedule_date` | 예정일 |
| `received_date` | 입고일 |

관계: `details()` → ScheduleByReceivingDetail (1:N), `histories()` → ReceivingHistory (1:N), `warehouse()`, `supplierAccount()`, `po()`, `so()`.

#### 10.3.4 출고 흐름

```
SalesOrder
├── po_account_id → Account (발주 거래처)
├── warehouse_id → Warehouse
├── details() → SalesOrderDetails (1:N)
└── images() → SalesOrderImage

ScheduleByShip
├── from_warehouse_id → Warehouse
├── to_warehouse_id → Warehouse
├── details() → ScheduleByShipDetails (1:N)
└── shipCarrier() → ShippingCarrierManagement
```

### 10.4 주요 마이그레이션 (70개)

#### 인증·기반 (2014~2019)
- `2014_10_12_000000_create_users_table`
- `2014_10_12_100000_create_password_resets_table`
- `2019_08_19_000000_create_failed_jobs_table`
- `2019_12_14_000001_create_personal_access_tokens_table` (Passport)

#### 기본 마스터 (2022-10)
- `2022_10_03_105605_create_accounts_table` ([file:16-76](../database/migrations/2022_10_03_105605_create_accounts_table.php#L16-L76)) — `control_call`, `view_call`, `billing_strategy_id`, `udf1~udf22` 확장 필드
- `2022_10_10_120014_create_addresses_table`
- `2022_10_12_112146_create_item_masters_table`
- `2022_10_12_114354_create_item_classes_table`
- `2022_10_12_115214_create_items_table` ([file:16-95](../database/migrations/2022_10_12_115214_create_items_table.php#L16-L95)) — `item_code`, `item_master_id`, `item_class_id`, `supplier_id`, `purchase_price`, `sales_price`, 치수, `udf1~udf10`

#### 창고·위치 (2022-11)
- `2022_11_16_124306_create_warehouses_table`
- `2022_11_17_072342_create_zones_table`
- `2022_11_17_102302_create_areas_table`
- `2022_11_21_042702_create_locations_table`
- `2022_11_22_084959_create_pallets_table`
- `2022_11_28_124448_create_schedule_by_receivings_table`

#### 재고 (2022-12)
- `2022_12_30_104321_create_inventories_table` ([file:16-37](../database/migrations/2022_12_30_104321_create_inventories_table.php#L16-L37)) — `item_id`, `warehouse_id`, `location_id`, `qty`, `available_qty`, `hold_qty`, `process_qty`
- `2022_12_30_104653_create_inventory_histories_table`

#### 주문·배송 (2023-02 ~ 2023-03)
- `2023_02_08_094753_create_schedule_by_ships_table`
- `2023_03_10_060022_create_sales_orders_table`
- `2023_03_13_032555_create_purchase_returns_table`
- `2023_03_13_110511_create_sales_returns_table`

#### 결제·청구 (2023-03)
- `2023_03_14_061836_create_billing_strategies_table`
- `2023_03_15_032046_create_billings_table`
- `2023_03_28_084404_create_bills_table`

### 10.5 시더

| 위치 | 내용 |
|---|---|
| [database/seeders/DatabaseSeeder.php](../database/seeders/DatabaseSeeder.php) | Master Admin(id=1, email=`admin@dh.com`, password=bcrypt(`12345678`))을 `admins` 테이블에 삽입 |
| [database/seeds/DatabaseSeeder.php](../database/seeds/DatabaseSeeder.php) | 구 Laravel 5.x 시더 — AdminTable 호출 |
| [database/seeds/AdminTable.php](../database/seeds/AdminTable.php) | 동일 Master Admin 시드 |

권한(Role/Permission), 마스터 품목·거래처 시딩은 코드에 없음. 운영 시 별도 데이터 임포트 필요.

### 10.6 설계 특징

1. **다중 DB 격리** — admin / warehouse 분리
2. **AccountScope 글로벌 스코프** — 모든 쿼리에 `account_id` 자동 필터
3. **`udf*` 확장 필드** — Account(22), Item(10), 기타 모델별로 사용자 정의 필드 내장
4. **`erp_cd` / `erp_yn`** — ERP 연동 키
5. **이벤트 모델 (`AutomatedEvent`, `AutomatedStrategy`)** — 자동화 워크플로우 기반
6. **동적 가격 계산** — `Item::calculateQtyInfo()` / `calculateQtyInfo2()`로 가용·예약·미소진 수량 동적 산출

---

## 11. 라우트·API 구조

### 11.1 라우트 파일 개요

| 파일 | 줄 수 | 역할 |
|---|---:|---|
| [routes/web.php](../routes/web.php) | 1,931 | 메인 웹 — IntegrationController::toRoute() 패턴 |
| [routes/api.php](../routes/api.php) | 19 | 최소 REST API (Sanctum) |
| [routes/mobile.php](../routes/mobile.php) | 277 | 모바일/PDA — MobileController::toRoute() 패턴 |
| [routes/auth.php](../routes/auth.php) | 51 | 로그인/회원가입/비밀번호 재설정 |
| [routes/console.php](../routes/console.php) | 20 | Artisan 기본 명령 |
| [routes/channels.php](../routes/channels.php) | — | 브로드캐스트 채널 (Pusher) |

### 11.2 web.php 카테고리별 라우트 분포

| 카테고리 | 라우트 수(약) | 대표 URL |
|---|---:|---|
| 프린트·정적 페이지 | 43+ | `/print01`~`/print43`, `/intro`, `/company` |
| 인증·계정·공개 | 17 | `/`, `/intro_*`, `/policy*`, `/contactus`, `/personal_information/*` |
| 시스템 설정·관리자 | 100+ | `/admin_role`, `/menu_authority`, `/staff`, `/common_code`, `/master_setup`, `/setting`, `/grid-column-order`, `/bookmark/store`, `/memo` |
| 팝업·모달·검색 | 100+ | `/popup/{name}`, `/popup/{name}/get`, `/{entity}/getvalue` |
| 출력물(인쇄) 팝업 | 60 | 거래명세서, 발주서, 피킹리스트, 위치 라벨, 박스 라벨 등 |
| 사용자·거래처 관리 | 150+ | `/profile`, `/edit-profile`, `/account`, `/account_*`, `/personal_account` |
| 마스터 데이터 | 100+ | `/item_master`, `/item_class`, `/item`, `/bom`, `/customer_part_number` |
| 판매 현황·청구 | 180+ | `/sales_status`, `/link_customer_sales_status`, `/sales_invoice`, `/item_price`, `/billing_strategy` |
| 재고·로지스틱스 마스터 | 150+ | `/warehouse`, `/area`, `/zone`, `/location`, `/pick_location`, `/alternate_barcode`, `/*_strategy` |
| 입고 프로세스 | 200+ | `/schedule_by_receiving`, `/receiving`, `/integrated_receiving`, `/receiving_status`, `/receiving_cancel`, `/receiving_confirm`, `/receiving_closed` |
| 재고 관리 | 180+ | `/inventory`, `/inventory_movement`, `/inventory_lot_change`, `/inventory_hold`, `/inventory_adjust`, `/inventory_physical`, `/wms_e1_inventory_management` |
| 출고·배송 프로세스 | 300+ | `/putaway`, `/schedule_by_ship`, `/assignment`, `/integrated_ship`, `/direct_picking`, `/packing`, `/wb_packing`, `/management`, `/ship_conf`, `/ship_status`, `/othershipment` |
| 주문 (구매/판매) | 150+ | `/purchaseorder`, `/salesorder`, `/sampleorderagent`, `/samplesalesagent`, `/agency_customer_salesorder` |
| 반품·청구 | 80+ | `/purchase_return`, `/salesreturn`, `/sample_return`, `/customerservice`, `/complaint`, `/complaint_status` |
| ERP 연동·통계 | 80+ | `/billing_strategy`, `/erp_status`, `/parsing`, `/udi_*`, `/report`, `/ims_excp_monitoring` |

### 11.3 라우트 등록 패턴

```php
// routes/web.php 예시
Route::get('/account', [IntegrationController::class, 'toRoute'])
    ->setDefaults(['AccountController@index', '거래처 목록']);

Route::post('/account/save', [IntegrationController::class, 'toRoute'])
    ->setDefaults(['AccountController@save', '거래처 저장']);
```

- `setDefaults(['Controller@method', '한글 라벨'])` 메타데이터 부착
- IntegrationController가 런타임에 `control_call`로 도메인 분기
- 한글 라벨은 access_log 및 권한 매핑에 사용

### 11.4 API 노출 범위

#### 11.4.1 routes/api.php — 최소 구성

```php
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
```

실질적 REST API는 거의 없고, 대부분의 외부 호출은 web.php로 처리됩니다.

#### 11.4.2 도메인 API — 공통 + 도메인별

| 위치 | 인증 | 역할 |
|---|---|---|
| [app/Http/Controllers/api/v1/](../app/Http/Controllers/api/v1/) | Passport | 공통 API — SalesOrder/PurchaseOrder/InvMove/Packing/Parsing/Item/Config/Account/Putaway/Ship/Receiving 등 |
| [app/Http/Controllers/main/medical/standard/api/v1/](../app/Http/Controllers/main/medical/standard/api/v1/) | Passport | medical 전용 — InvMove, Packing |
| [app/Http/Controllers/main/pet/standard/api/v1/](../app/Http/Controllers/main/pet/standard/api/v1/) | Passport | pet 전용 — InvMove, Item |
| [app/Http/Controllers/main/factory/lseA/api/v1/](../app/Http/Controllers/main/factory/lseA/api/v1/) | Passport | factory 전용 — InvMove, Account |

#### 11.4.3 routes/mobile.php — 모바일 API 카탈로그

미들웨어: `mobile` 그룹 (세션 + UserActivity + MobileMiddleware)

| 그룹 | 라우트 수 | 주요 엔드포인트 |
|---|---:|---|
| 인증 | 4 | `/mobile/auth/login`, `/app_login`, `/logout`, `/loading` |
| 공통 | 4 | `/mobile/get_code`, `/switch`, `/account/code`, `/file_download` |
| 대시보드 | 2 | `/mobile/dashboard`, `/main` |
| 구매 | 1 | `/mobile/purchase/list` |
| 판매 | 1 | `/mobile/sales/list` |
| 입고 | 20 | `/mobile/receiving/*` — 확정, LOT 입력, 스캔, 분할 |
| 입고검사(PDA) | 10 | `/mobile/pda_receiving_inspection/*` — 완료, 취소 |
| 재고이동(PDA) | 3 | `/mobile/pda_inventory_move/*` |
| 재고검색(PDA) | 2 | `/mobile/pda_inventory_search/*` |
| 품목바코드(PDA) | 3 | `/mobile/pda_item_barcode/*` — 등록 |

모바일은 PDA 작업 시나리오 중심(입고검사·재고이동·바코드)으로 설계되었습니다.

### 11.5 미들웨어 인벤토리

#### 11.5.1 전역 미들웨어 ([app/Http/Kernel.php](../app/Http/Kernel.php))

- TrustProxies, HandleCors
- PreventRequestsDuringMaintenance, ValidatePostSize
- TrimStrings, ConvertEmptyStringsToNull
- APILocalizationMiddleware

#### 11.5.2 미들웨어 그룹

| 그룹 | 구성 |
|---|---|
| `web` | EncryptCookies, AddQueuedCookiesToResponse, StartSession, ShareErrorsFromSession, VerifyCsrfToken, SubstituteBindings, Localization, UserActivity, NotFoundMiddleware, MultiAuthMiddleware |
| `mobile` | web 그룹 + MobileMiddleware |
| `api` | SubstituteBindings, NotFoundMiddleware (throttle 비활성화) |

#### 11.5.3 라우트 미들웨어

| 별칭 | 역할 |
|---|---|
| `auth` | 로그인 필수 |
| `guest` | 미로그인 전용 (로그인·회원가입 페이지) |
| `auth.session` | 세션 인증 강제 |
| `verified` | 이메일 인증 확인 |
| `XSS` | XSS 공격 방지 |
| `nocache` | 캐시 비활성화 |
| `throttle` | 요청 제한 |
| `signed` | 서명 URL 검증 |
| `can` | 권한 검증 |

### 11.6 디자인 패턴 요약

1. **IntegrationController 중앙화** — 모든 web 라우트가 단일 진입점, 동적 라우팅
2. **동적 팝업 시스템** — `/popup/{entity}` (UI) + `/popup/{entity}/get` (데이터), 40+ 공용 팝업
3. **일괄 등록 패턴** — 마스터 라우트마다 `/batch/open` → `/batch/valid` → POST 흐름과 엑셀 업/다운로드 통합
4. **실적·현황 분리** — `*_status` (조회) vs `*_confirm` / `*_closed` (상태 변경)
5. **모바일 격리** — `/mobile/*` 프리픽스로 독립 인증·미들웨어

---

## 12. 부록

### 12.1 최상위 컨트롤러 (22개)

| 컨트롤러 | 역할 |
|---|---|
| `IntegrationController` | 모든 web 라우트의 동적 라우팅 진입점 |
| `RootController` | 도메인 컨트롤러의 부모, 응답 포맷 통일 (sendSuccess/sendError/sendResponse) |
| `HomeController` | 공개 페이지 (intro, account_search) |
| `ApiIntegrationController` | API용 동적 라우팅 진입점 |
| `MobileController` | 모바일 라우팅 진입점 |
| `MobileHomeController` | 모바일 홈 페이지 |
| `MobileRootController` | 모바일 컨트롤러의 부모 |
| `BookmarkController` | 사용자 북마크 |
| `CommuteController` | 출퇴근 기록 |
| `ContactUsController` | 문의 폼 |
| `FileController` | 파일 업로드/다운로드 |
| `GridColumnController` | 그리드 컬럼 순서·표시 설정 저장 |
| `InquiryController` | 문의 사항 관리 |
| `MemoController` | 메모 |
| `MemoManagerController` | 메모 관리 (공유·삭제) |
| `NotificationController` | 알림 |
| `PersonalInformationController` | 개인정보 (카테터·스토마 폼) |
| `RouteController` | 라우트 정보 |
| `SystemSettingController` | 시스템 설정 |
| `VehiclesController` | 차량 (배송 차량 관련) |
| `Auth/AuthenticatedSessionController` | 웹 로그인/로그아웃 |
| `Auth/RegisteredUserController` | 회원가입 |

### 12.2 도메인 컨트롤러 — 3 도메인 공통 60개

```
AccountAddressController          AccountController                 AccountDepartmentController
AdminRoleController               AlarmStrategyController           AllocStrategyController
AlterItemsController              AlternateBarcodeController        AreaController
AssignmentController              AutomatedStrategyController       BillingStrategyController
BomController                     BoxController                     CommonController
ConsignmentController             CustomerPartNumberController      CustomerServiceController
DashboardController               ErpStatusController               ExcelUploadController
InvPhysicalRequestController      InvPhysicalStatusController       InventoryController
InventoryDailyHistoryController   InventoryHistoryController        InventoryLevelController
InventoryModuleController         ItemByLocationController          ItemByWarehouseController
ItemClassController               ItemController                    ItemMasterController
KPIController                     LanguageController                LocationController
ManagementController              MasterSetupController              MenuAuthorityController
MixRuleController                 OrderStrategyController           OtherShipmentController
PackagingController               PackingController                 PackingHistoryController
PalletController                  ParsingRulesController            PrintController
PurchaseOrderController           PurchaseReturnController          PutAwayStrategyController
PutawayCancelController           PutawayController                 QuotationController
ReceivingClosedController         ReceivingConfirmController        ReceivingOrderController
ReplenishmentOrderController      ScheduleByReceivingController     ScheduleByShipController
ShipmentController                ShipmentOrderController           StaffController
StatusController                  SystemController                  UserController
WarehouseController               WaveStrategyController            WorkbenchPackingController
ZoneController
```

### 12.3 medical/standard 전용 컨트롤러 (62개)

```
AccountAddInfoController                    AccountCounsellingController                 AccountInventoryAdjustController
AccountInventorySearchController            AccountManagerController                     AccountManagerMonitoringController
AccountManagerSalesStatusController         AccountMappingController                     AgencyCustomerSalesOrderController
AgencyInventoryController                   AgencyInventoryDailyHistoryController        AgencyInventoryLevelManagementController
AgencyTargetAmountManagementController      CESampleOrderStatusController                CommonCodeController
ComplaintController                         ComplaintStatusController                    ConsignMoveStatusController
ConsignmentActualController                 DirectPickingController                      E1InvController
ExcpActualController                        GlobalReceivingStatusController              ImsActualController
ImsExcpMonitoringController                 InspectionReportMasterController             IntegratedReceivingController
IntegratedShippingController                InvPhysicalController                        ItemPriceController
ItemSalesStatusController                   LinkCustomerSalesStatusController            LotManagementController
LotMasterController                         ModuleInventoryController                    MonthlyAgentInventoryLevel
MonthlyClosingMonitoringController          MonthlyInventoryLevelController              ParsingController
PersonalAccountController                   PersonalInfoManagementController             PurchaseOrderFor3PLController
ReceivingController                         ReceivingStatusController                    ReportController
SalesInvoiceController                      SalesReturnController                        SalesStatusController
SampleApprovalRequestEmail                  SampleOrderAgentController                   SampleOrderController
SampleReturnController                      SampleSalesAgentController                   SetItemController
ShippingPerformanceController               TargetAmountManagementController             TaskChainController
UdiItemsController                          UdiModelsController                          UdiReportController
UnShipStatusController                      UnitPriceInquiryController                   UserAnnounceManagementController
```

### 12.4 factory/lseA 전용 컨트롤러 (2개)

| 컨트롤러 | 역할 |
|---|---|
| `BillController` | 청구·입금 관리 |
| `ShippingCarrierManagementController` | 배송사 관리 |

### 12.5 pet/standard 전용 컨트롤러 (3개)

| 컨트롤러 | 역할 |
|---|---|
| `AgencyInventoryController` | 대리점 재고 (pet 버전) |
| `InventoryExpirationDateManagementController` | 유효기간 관리 |
| `TaskChainController` | 작업 체이닝 |

### 12.6 디렉토리별 컨트롤러 (도메인 외)

| 디렉토리 | 컨트롤러 |
|---|---|
| `app/Http/Controllers/api/v1/` | SalesOrderController, PurchaseOrderController, InvMoveController, PackingController, ParsingController, ItemController, ConfigController, AccountController, PutawayController, ShipController, ReceivingController |
| `app/Http/Controllers/api/auth/` | LoginController |
| `app/Http/Controllers/batch/medical/` | BatchProcessRunController |
| `app/Http/Controllers/batch/lseA/` | BatchProcessRunController |
| `app/Http/Controllers/automation/` | AutomatedStrategyProcessController |
| `app/Http/Controllers/event/` | AutomatedEventRunController |
| `app/Http/Controllers/mobile/Auth/` | LoginController |
| `app/Http/Controllers/mobile/pet/standard/` | ShipmentController, ReceivingController, InventoryController |

### 12.7 핵심 라우트 파일 위치

| 파일 | 경로 |
|---|---|
| 메인 웹 라우트 | [routes/web.php](../routes/web.php) (1,931줄) |
| API 라우트 | [routes/api.php](../routes/api.php) |
| 모바일 라우트 | [routes/mobile.php](../routes/mobile.php) |
| 인증 라우트 | [routes/auth.php](../routes/auth.php) |
| 콘솔 명령 | [routes/console.php](../routes/console.php) |
| 브로드캐스트 채널 | [routes/channels.php](../routes/channels.php) |

### 12.8 마이그레이션 시간순 핵심 테이블

| 시점 | 테이블 |
|---|---|
| 2014 | users, password_resets |
| 2019 | failed_jobs, personal_access_tokens (Passport) |
| 2022-10 | accounts, addresses, item_masters, item_classes, items |
| 2022-11 | warehouses, zones, areas, locations, mix_rules, pallets, barcodes, account_addresses, pick_strategies, put_away_strategies, schedule_by_receivings |
| 2022-12 | warehouse_items, items_movement_histories, inventories, inventory_histories |
| 2023-02 | schedule_by_ships, schedule_by_ship_details |
| 2023-03 | sales_orders, purchase_returns, sales_returns, billing_strategies, billings, bills |

전체 70개 마이그레이션 — [database/migrations/](../database/migrations/) 디렉토리 참조.

### 12.9 도메인 컨트롤러 위치

| 도메인 | 경로 |
|---|---|
| medical/standard | [app/Http/Controllers/main/medical/standard/](../app/Http/Controllers/main/medical/standard/) |
| factory/lseA | [app/Http/Controllers/main/factory/lseA/](../app/Http/Controllers/main/factory/lseA/) |
| pet/standard | [app/Http/Controllers/main/pet/standard/](../app/Http/Controllers/main/pet/standard/) |

### 12.10 사이드내브 위치

| 도메인 | 경로 |
|---|---|
| medical/standard | [resources/views/main/medical/standard/layouts/v2/sidenav.blade.php](../resources/views/main/medical/standard/layouts/v2/sidenav.blade.php) |
| factory/lseA | [resources/views/main/factory/lseA/layouts/v2/sidenav.blade.php](../resources/views/main/factory/lseA/layouts/v2/sidenav.blade.php) |
| pet/standard | [resources/views/main/pet/standard/layouts/v2/sidenav.blade.php](../resources/views/main/pet/standard/layouts/v2/sidenav.blade.php) |

### 12.11 산업군 정의

[app/Helpers/constant_funtions.php:20-36](../app/Helpers/constant_funtions.php#L20-L36) `industry_list` 배열에서 다음 12개 산업 등록:

```
base/standard
medical/standard      ← 운영 중
factory/lseA          ← 운영 중
pet/standard          ← 운영 중
sports/standard
electronic/standard
baby/standard
fashion/standard
cosmetic/standard
parts/standard
logis/standard
```

각 항목은 `control_call`, `view_call`, `lang_call`, 한글 라벨을 포함하여 도메인 분기에 사용됩니다.

---

> 본 문서는 코드베이스 정적 분석(2026-05-22)으로 작성되었습니다. 코드 변경 시 본 문서도 함께 갱신해야 합니다. 갱신 누락 방지를 위해 주요 사이드내브·라우트·모델 카운트는 `git log` 변경 시점에 재검증하는 것을 권장합니다.
