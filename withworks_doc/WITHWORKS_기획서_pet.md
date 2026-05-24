# WITHWORKS 도메인 기획서 — pet/standard

> pet/standard 도메인의 **전용** 프로세스 흐름 명세서입니다. 3개 도메인이 공유하는 공통 풀필먼트 흐름은 [WITHWORKS_기획서_공통흐름.md](WITHWORKS_기획서_공통흐름.md)를 참조하세요. 본 문서는 pet이 medical과 공유하는 항목(대리점 재고·유효기간), factory와 공유하는 항목(전략 v2·입금 관리·적치 이동) 안내, 그리고 사이드내브 외 pet 전용 컨트롤러(TaskChain·모바일)만 다룹니다.

## 참조 문서

- [WITHWORKS_기획서.md](WITHWORKS_기획서.md) — 통합 명세
- [WITHWORKS_기획서_공통흐름.md](WITHWORKS_기획서_공통흐름.md) — 3 도메인 공통 풀필먼트 흐름
- [WITHWORKS_기획서_medical.md](WITHWORKS_기획서_medical.md) — medical 도메인 전용
- [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) — factory 도메인 전용

---

## 1. 도메인 개요

| 항목 | 값 |
|---|---|
| 도메인 키 | `pet/standard` |
| control_call | `pet\standard` |
| 컨트롤러 위치 | [app/Http/Controllers/main/pet/standard/](../app/Http/Controllers/main/pet/standard/) |
| 컨트롤러 수 | 78개 (공통 60 + pet 전용·다도메인 공유 18) |
| 사이드내브 메뉴 항목 | 72개 (공통 71 + pet·medical 공유 2 + pet·factory 공유 4 — 일부 그룹 차이) |
| layout | [resources/views/main/pet/standard/layouts/v2/app.blade.php](../resources/views/main/pet/standard/layouts/v2/app.blade.php) |
| 사이드내브 | [resources/views/main/pet/standard/layouts/v2/sidenav.blade.php](../resources/views/main/pet/standard/layouts/v2/sidenav.blade.php) |
| 도메인 성격 | 반려동물 소비재 풀필먼트. pet만의 사이드내브 메뉴는 없음. 자동화(TaskChain)와 모바일 PDA가 특화 |

> pet 사이드내브 메뉴는 [통합 문서 §9](WITHWORKS_기획서.md#9-pet-전용-모듈)에 URL/컨트롤러 매핑이 있습니다.

---

## 2. pet·medical 공유 흐름

### 2.1 유효기간 관리

#### 2.1.1 컨트롤러·메서드

- 컨트롤러: [InventoryExpirationDateManagementController.php](../app/Http/Controllers/main/pet/standard/InventoryExpirationDateManagementController.php) (pet 전용 컨트롤러)
- 라우트: `/inventory_expiration_date_management`
- 주요 메서드: `search_inventory()`, `get_list()`

#### 2.1.2 핵심 코드

- `REDATE` — 남은 유효기간 일수 (Code 테이블에서 임계 기준 정의)

#### 2.1.3 흐름

```
LOT 생성 시 유효기간 등록 (Lot.expire_date / expiration_date — 공통흐름.md §2.3 입고확정 단계)
  ↓
입고 시 Lot에 유효기간 결합
  ↓
유효기간 관리 화면 (InventoryExpirationDateManagement)
  - 잔여 일수 = expire_date - 오늘
  - REDATE 임계 이하 → 만기 임박 상품 식별
  ↓
조치: 우선 출고 / 폐기 / 반품 (반품 흐름은 공통흐름.md §7)
```

> medical은 동일 라우트(`/inventory_expiration_date_management`)를 가지지만 컨트롤러는 medical/standard에 별도 존재. factory는 미보유.

#### 2.1.4 관련 모델

- [Lot](../app/Models/Lot.php) — 유효기간 정보 보유
- [Inventory](../app/Models/Inventory.php) — Lot 참조
- [Code](../app/Models/Code.php) — REDATE 임계 정의

### 2.2 대리점 재고 (pet·medical 공유, factory 미보유)

#### 2.2.1 컨트롤러

- 컨트롤러: [AgencyInventoryController.php](../app/Http/Controllers/main/pet/standard/AgencyInventoryController.php) (pet 전용 컨트롤러 — medical의 같은 이름 컨트롤러와는 별개)
- 라우트: `/agency_inventory`
- 모델: [Inventory](../app/Models/Inventory.php), [InventoryHistory](../app/Models/InventoryHistory.php), [Item](../app/Models/Item.php), [Lot](../app/Models/Lot.php), [Warehouse](../app/Models/Warehouse.php)

#### 2.2.2 medical과 pet의 채택 범위 차이

| 항목 | medical | pet |
|---|:-:|:-:|
| 대리점 재고 (`/agency_inventory`) | ✅ | ✅ |
| 대리점 일재고 이력 | ✅ | ❌ |
| 대리점 재고 레벨 관리 | ✅ | ❌ |
| 대리점 목표 관리 | ✅ | ❌ |
| 위탁 이동 현황 | ✅ | ❌ |
| 위탁 실적 | ✅ | ❌ |

pet은 medical의 대리점·위탁 시스템 중 **"대리점 재고 조회"만** 채택한 축소 버전입니다. medical의 전체 위탁/대리점 흐름은 [WITHWORKS_기획서_medical.md §4](WITHWORKS_기획서_medical.md#4-위탁대리점-흐름) 참조.

---

## 3. pet·factory 공유 흐름

다음 항목은 factory와 동일한 컨트롤러·라우트·흐름을 사용합니다. 상세 흐름은 [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) 해당 섹션을 참조하세요.

| 항목 | 라우트 | 참조 |
|---|---|---|
| 입금 관리 | `/deposit_management` | [WITHWORKS_기획서_factory.md §4](WITHWORKS_기획서_factory.md#4-입금-관리-흐름-factorypet-공유-medical-미보유) |
| 할당 전략 v2 | `/strategy/alloc_strategy` | [WITHWORKS_기획서_factory.md §5.1](WITHWORKS_기획서_factory.md#51-할당-전략-v2) |
| 적치 전략 v2 | `/strategy/putaway_strategy` | [WITHWORKS_기획서_factory.md §5.2](WITHWORKS_기획서_factory.md#52-적치-전략-v2) |
| 적치 이동 | `/putaway_movement` | [WITHWORKS_기획서_factory.md §6](WITHWORKS_기획서_factory.md#6-적치-이동-흐름-factorypet-공유-medical-미보유) |

---

## 4. 사이드내브 외 pet 전용 기능

### 4.1 TaskChain 흐름 (자동화 워크플로우)

사이드내브 메뉴는 없지만 pet 도메인 전용 자동화 엔진. 주문·입고·출고·고객서비스 이벤트 발생 시 사전 정의된 작업 체인을 순차 실행합니다.

#### 4.1.1 컨트롤러

- 컨트롤러: [TaskChainController.php](../app/Http/Controllers/main/pet/standard/TaskChainController.php) (pet **전용**)
- 주요 메서드: `callTaskChain()` (타입별 디스패치)
  - `callTaskChainByPo()` — PO 트리거
  - `callTaskChainBySo()` — SO 트리거
  - `callTaskChainByRcpt()` — RCPT(입고) 트리거
  - `callTaskChainByShip()` — SHIP(출고) 트리거
  - `callTaskChainByCS()` — CS(고객서비스) 트리거

#### 4.1.2 모델 핵심 필드

**[TaskChainHeader](../app/Models/TaskChainHeader.php)** — 작업 체인 헤더

| 필드 | 의미 |
|---|---|
| `account_id` | 거래처 |
| `task_chain` | 체인 코드 |
| `type` | 트리거 타입 (PO/SO/RCPT/SHIP/CS) |
| `ref_type` | 참조 타입 |
| `status` | 체인 상태 |
| `task_chain_group` | 그룹 키 |
| `use_yn` | 사용 여부 |

관계: `details()` → TaskChainDetail (1:N)

**[TaskChainDetail](../app/Models/TaskChainDetail.php)** — 체인의 개별 단계

#### 4.1.3 흐름

```
이벤트 발생 (PO 생성 / SO 생성 / RCPT 확정 / SHIP 확정 / CS 접수)
  ↓
TaskChainController::callTaskChain(type)
  ↓
TaskChainHeader 조회 (type + use_yn=Y)
  ↓
TaskChainDetail 순차 실행 (task_chain_group 단위)
  ↓
각 단계 결과를 상태에 반영
  ↓
완료 시 다음 단계 또는 종료
```

### 4.2 pet 모바일 / PDA 흐름

pet 도메인은 별도 모바일 컨트롤러를 가집니다.

#### 4.2.1 컨트롤러 위치

- [app/Http/Controllers/mobile/pet/standard/](../app/Http/Controllers/mobile/pet/standard/) — PDA 작업
  - `ShipmentController` — 모바일 출고
  - `ReceivingController` — 모바일 입고
  - `InventoryController` — 모바일 재고

- [app/Http/Controllers/main/pet/standard/api/v1/](../app/Http/Controllers/main/pet/standard/api/v1/) — 모바일/외부 API
  - `InvMoveController` — 재고 이동 API
  - `ItemController` — 품목 API
  - 기타 풀필먼트 API

#### 4.2.2 흐름

```
PDA 앱에서 로그인 (mobile guard — 인증은 통합 문서 §4 참조)
  ↓ industry = 'pet_standard'
PDA 화면 선택 (입고검사 / 재고이동 / 바코드 / 출고 등)
  ↓
모바일 컨트롤러 호출 (mobile/pet/standard/*)
  ↓
API v1 컨트롤러 위임 (main/pet/standard/api/v1/*)
  ↓
공통 모델(Inventory, ScheduleByReceiving, Lot 등) 갱신 — 공통흐름.md 참조
```

---

## 5. pet 도메인 핵심 모델 연결도

```
풀필먼트 (공통흐름.md §2~5 참조):
  ScheduleByReceiving ─→ ReceivingHistory ─→ Inventory
                                              └─ Lot (expire_date)
                                                  └─ InventoryExpirationDateManagement (pet·medical)
  ScheduleByShip ─→ ShipHistory ─→ Inventory (차감)

대리점 재고 (pet·medical 공유):
  Account (biz_type=Agency)
    └── AgencyInventory ─→ InventoryHistory (대리점 단위 조회)

자동화 (pet 전용):
  Event (PO/SO/RCPT/SHIP/CS)
    └── TaskChainController::callTaskChain
           └── TaskChainHeader ─→ TaskChainDetail (1:N) 순차 실행

청구·입금 (pet·factory 공유):
  SalesOrder (공통흐름.md §6, status=95)
    └── Bills ─→ BillDetails
           └── billing → deposit_schedule → deposit_fix

모바일 (pet 전용 디렉토리):
  mobile/pet/standard/* ─→ main/pet/standard/api/v1/* ─→ Inventory / ScheduleByReceiving / Lot
```

---

> 본 문서는 코드베이스 정적 분석으로 작성되었습니다.
