# WITHWORKS 도메인 기획서 — factory/lseA

> factory/lseA 도메인의 **전용** 프로세스 흐름 명세서입니다. 3개 도메인이 공유하는 공통 풀필먼트 흐름은 [WITHWORKS_기획서_공통흐름.md](WITHWORKS_기획서_공통흐름.md)를 참조하세요. 본 문서는 factory에만 있거나 factory가 일부 도메인과 공유하는 특화 흐름(견적·배송사 관리·입금 관리·전략 v2)만 다룹니다.

## 참조 문서

- [WITHWORKS_기획서.md](WITHWORKS_기획서.md) — 통합 명세
- [WITHWORKS_기획서_공통흐름.md](WITHWORKS_기획서_공통흐름.md) — 3 도메인 공통 풀필먼트 흐름
- [WITHWORKS_기획서_medical.md](WITHWORKS_기획서_medical.md) — medical 도메인 전용
- [WITHWORKS_기획서_pet.md](WITHWORKS_기획서_pet.md) — pet 도메인 전용

---

## 1. 도메인 개요

| 항목 | 값 |
|---|---|
| 도메인 키 | `factory/lseA` |
| control_call | `factory\lseA` |
| 컨트롤러 위치 | [app/Http/Controllers/main/factory/lseA/](../app/Http/Controllers/main/factory/lseA/) |
| 컨트롤러 수 | 77개 (공통 60 + factory 전용·다도메인 공유 17) |
| 사이드내브 메뉴 항목 | 79개 (공통 71 + factory 전용 2 + factory·pet 공유 4 + factory·medical 공유 1 + 1 차이) |
| layout | [resources/views/main/factory/lseA/layouts/v2/app.blade.php](../resources/views/main/factory/lseA/layouts/v2/app.blade.php) |
| 사이드내브 | [resources/views/main/factory/lseA/layouts/v2/sidenav.blade.php](../resources/views/main/factory/lseA/layouts/v2/sidenav.blade.php) |
| 도메인 성격 | 제조·공급 중심. 견적·배송사·입금 관리가 핵심 |

> factory가 가진 메뉴는 [통합 문서 §8](WITHWORKS_기획서.md#8-factory-전용-모듈)에 URL/컨트롤러 매핑이 있습니다. 본 문서는 흐름 중심으로 정리합니다.

---

## 2. 견적 흐름 (factory만)

### 2.1 컨트롤러·모델

- 컨트롤러: [QuotationController.php](../app/Http/Controllers/main/factory/lseA/QuotationController.php)
- 라우트: `/quotation`
- 모델:
  - [Quotation](../app/Models/Quotation.php) — 견적 헤더
  - [QuotationDetail](../app/Models/QuotationDetail.php) — 견적 품목
  - [QuotationRequest](../app/Models/QuotationRequest.php) — 견적 요청 헤더
  - [QuotationRequestDetail](../app/Models/QuotationRequestDetail.php) — 견적 요청 품목

### 2.2 견적 유형

| Type | 의미 | 흐름 |
|---|---|---|
| `01` | 제안 견적 | 공급자가 거래처에 견적서 직접 발행 |
| `02` | 요청 견적 | 구매자가 견적 요청 → 공급자가 금액 작성 |

### 2.3 상태값

| 값 | 의미 |
|---|---|
| `02` | 신규 요청 |
| `95` | 견적 완료 |

### 2.4 흐름 도식

```
견적 요청 (QuotationRequest) — type=02
  ↓
공급자가 금액·조건 입력 (QuotationDetail)
  ↓
견적 확정 (Quotation) — status=95
  ↓
거래처 수락 → 주문 전환 (공통흐름.md §6 SalesOrder 생성)
```

---

## 3. 배송사 관리 흐름 (factory만)

### 3.1 컨트롤러·모델

- 컨트롤러: [ShippingCarrierManagementController.php](../app/Http/Controllers/main/factory/lseA/ShippingCarrierManagementController.php) (factory **전용**)
- 라우트: `/shipping_carrier_management`
- 주요 메서드: `search_shipping_carrier_management()`
- 모델: [ShippingCarrierManagement](../app/Models/ShippingCarrierManagement.php)

### 3.2 모델 핵심 필드

| 필드 | 의미 |
|---|---|
| `account_id` | 거래처 |
| `carrier` | 배송사 코드 |
| `carrier_name` | 배송사명 |
| `carrier_url` | 배송 추적 URL |
| `active_yn` | 활성화 여부 |
| `link_yn` | API 연동 여부 |
| `priority` | 우선순위 |

### 3.3 흐름

```
배송사 등록 (ShippingCarrierManagement)
  ↓ priority·active_yn·link_yn 설정
출고 확정 시 배송사 선택 (공통흐름.md §5.7 Shipment)
  ↓
배송 추적 (Tracking 모델 연계)
  ↓ link_yn=Y인 경우 외부 배송 API 호출 (CarrierApiMng / CarrierApiHist)
```

### 3.4 관련 모델

- [ShippingCarrierManagement](../app/Models/ShippingCarrierManagement.php) — 배송사 마스터
- [Tracking](../app/Models/Tracking.php) — 송장 추적
- [CarrierApiMng](../app/Models/CarrierApiMng.php) — 배송사 API 설정
- [CarrierApiHist](../app/Models/CarrierApiHist.php) — API 호출 이력
- [CarrierHistory](../app/Models/CarrierHistory.php) — 배송 이력

---

## 4. 입금 관리 흐름 (factory·pet 공유, medical 미보유)

### 4.1 컨트롤러·모델

- 라우트: `/deposit_management`
- 컨트롤러: BillController 계열 (factory: [BillController.php](../app/Http/Controllers/main/factory/lseA/BillController.php))
- 주요 메서드: `search()`, `getAllDetails()`
- 모델: [Bills](../app/Models/Bills.php)

### 4.2 Bills 모델 핵심 필드

| 필드 | 의미 |
|---|---|
| `billing_no` | 청구 번호 |
| `account_id` | 청구 발행 거래처 |
| `to_account_id` | 청구 대상 거래처 |
| `so_id` | 연결 판매주문 |
| `po_id` | 연결 구매주문 |
| `billing_date` | 청구 일자 |
| `billing_confirm_date` | 청구 확정 일자 |
| `bill_amt` | 청구 금액 |
| `tax_amt` | 세액 |
| `bill_total_amt` | 청구 총액 |
| `deposit_amt` | 입금 금액 |
| `deposit_schedule_date` | 입금 예정일 |
| `deposit_fix_date` | 입금 확정일 |
| `in_out_type` | 매출/매입 구분 |
| `invoice_type` | 인보이스 종류 |

### 4.3 상태 코드

- `BILLSTATUS` — 청구 상태
- `INOUTTYPE` — 매출/매입 구분
- `INVOICETYPE2` — 세금계산서 등 인보이스 유형

### 4.4 흐름

```
판매주문 출고 완료 (공통흐름.md §5.7 SalesOrder.status=95)
  ↓
청구 발행 (Bills 생성)
  - billing_date 기록
  - 청구 금액·세액 계산
  ↓
청구 확정 (billing_confirm_date)
  ↓
입금 예정 (deposit_schedule_date 등록)
  ↓
입금 확정 (deposit_fix_date)
  - deposit_amt 기록
  ↓
미수금 잔액 계산 = bill_total_amt - deposit_amt
```

> 동일 흐름이 pet 도메인에도 적용됩니다. medical은 매출 인보이스(`/sales_invoice`)로 대체.

---

## 5. 전략 v2 (factory·pet 공유, medical 미보유)

factory/pet 사이드내브에는 v1 전략 외에 v2 분기가 추가됩니다.

### 5.1 할당 전략 v2

- 컨트롤러: [AllocStrategyController.php](../app/Http/Controllers/main/factory/lseA/AllocStrategyController.php) (별도 액션)
- 주요 메서드: `search()`, `store()`, `destroy()`
- 라우트: `/strategy/alloc_strategy`
- 모델: [AllocStrategy](../app/Models/AllocStrategy.php), [AllocStrategyDetail](../app/Models/AllocStrategyDetail.php)
- 기능: 출고 시 재고 할당 우선순위 규칙 (선입선출 / 후입선출 / 거리 기반 / LOT 기반 등)

### 5.2 적치 전략 v2

- 컨트롤러: [PutAwayStrategyController.php](../app/Http/Controllers/main/factory/lseA/PutAwayStrategyController.php) (별도 액션)
- 주요 메서드: `store()`, `getAllDetails()`, `destroy()`
- 라우트: `/strategy/putaway_strategy`
- 모델: [PutAwayStrategy](../app/Models/PutAwayStrategy.php), [PutAwayStrategyDetail](../app/Models/PutAwayStrategyDetail.php)
- 기능: 입고 적치 위치 결정 규칙 (구역 → 존 → 로케이션)

### 5.3 v1 / v2 차이

| 측면 | v1 | v2 |
|---|---|---|
| 라우트 | `/alloc_strategy`, `/putaway_strategy` | `/strategy/alloc_strategy`, `/strategy/putaway_strategy` |
| 적용 | 3 도메인 모두 (공통) | factory/pet만 |
| 컨트롤러 | 동일 컨트롤러의 v1 액션 | 동일 컨트롤러의 v2 액션 |

---

## 6. 적치 이동 흐름 (factory·pet 공유, medical 미보유)

### 6.1 라우트·컨트롤러

- 라우트: `/putaway_movement`
- 컨트롤러: factory/pet의 InventoryController 또는 PutawayController 액션 (구체 메서드는 [factory/lseA/](../app/Http/Controllers/main/factory/lseA/) 디렉토리에서 확인)

### 6.2 기능

이미 적치된 재고를 다른 로케이션으로 이동. 공통흐름의 적치(§3.1)와 별도로 적치 후 위치 재배치 시 사용.

---

## 7. UDI 보고 현황 (factory·medical 공유, pet 미보유)

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| UDI 보고 현황 | ReportController | `/report` |

medical에는 UDI 품목·모델·보고 등 전체 UDI 흐름이 있지만 factory는 보고 현황 조회만 채택. 자세한 UDI 흐름은 [WITHWORKS_기획서_medical.md §5](WITHWORKS_기획서_medical.md#5-udi-unique-device-identification-흐름) 참조.

---

## 8. factory 도메인 핵심 모델 연결도

```
견적·주문 (factory 전용 + 공통):
  QuotationRequest ─→ QuotationRequestDetail
        ↓ (수락 시)
  Quotation ─→ QuotationDetail
        ↓ (확정 시)
  PurchaseOrder / SalesOrder (공통흐름.md §6)

배송 (factory 전용):
  공통흐름.md §5 ScheduleByShip → ShipHistory
        ↓
        ShippingCarrierManagement ─→ Tracking
        ↓ (link_yn=Y)
        CarrierApiMng → CarrierApiHist → CarrierHistory

전략 v2 (factory·pet 공유):
  AllocStrategy ─→ AllocStrategyDetail (할당 우선순위)
  PutAwayStrategy ─→ PutAwayStrategyDetail (적치 위치)

청구·입금 (factory·pet 공유):
  SalesOrder (공통흐름.md §6, status=95)
        ↓
  Bills ─→ BillDetails
        ↓
  - billing_date → billing_confirm_date
  - deposit_schedule_date → deposit_fix_date
```

---

> 본 문서는 코드베이스 정적 분석으로 작성되었습니다.
