# WITHWORKS 공통 풀필먼트 흐름

> 3개 도메인(medical/factory/pet)이 공통으로 사용하는 풀필먼트 프로세스 흐름 명세서입니다. 도메인별 차이가 거의 없는 공통 흐름을 한 곳에 모았습니다. 도메인 전용 흐름은 각 도메인 기획서를 참조하세요. 추측은 배제하고 코드에서 확인 가능한 사실만 기재합니다.

## 참조 문서

- [WITHWORKS_기획서.md](WITHWORKS_기획서.md) — 통합 명세 (개요·아키텍처·인증·메뉴·데이터·라우트·부록)
- [WITHWORKS_기획서_medical.md](WITHWORKS_기획서_medical.md) — medical 도메인 전용 흐름
- [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) — factory 도메인 전용 흐름
- [WITHWORKS_기획서_pet.md](WITHWORKS_기획서_pet.md) — pet 도메인 전용 흐름

---

## 1. 공통 흐름 개요

3개 도메인은 모두 동일한 풀필먼트 베이스를 공유합니다. 핵심 컨트롤러는 도메인 디렉토리(`app/Http/Controllers/main/<domain>/`)에 각각 존재하지만, 메서드 시그니처·상태 코드·라우트 경로는 동일합니다. URL은 동일하나 [IntegrationController::toRoute()](../app/Http/Controllers/IntegrationController.php#L15-L79)가 사용자의 `control_call`에 따라 도메인별 컨트롤러로 자동 분기합니다.

본 문서의 코드 경로는 `medical/standard`를 기준으로 표기하나, 동일 클래스명은 [factory/lseA](../app/Http/Controllers/main/factory/lseA/) 및 [pet/standard](../app/Http/Controllers/main/pet/standard/)에도 존재합니다.

### 1.1 전체 흐름 도식

```
[주문] PurchaseOrder / SalesOrder
   ↓
[입고예정] ScheduleByReceiving (status=01)
   ↓
[입고처리] Receiving (status=02, ReceivingHistory 기록)
   ↓
[입고확정] ReceivingConfirm (status=95, LOT 생성, 재고 반영)
   ↓
[입고마감] ReceivingClosed (status=98)
   ↓
[적치] Putaway → 재고 위치 확정
   ↓
[재고] Inventory (조정·이동·LOT 변경·실사)
   ↓
[출고예정] ScheduleByShip (status=01)
   ↓
[할당] Assignment (status=02)
   ↓
[피킹] Management
   ↓
[포장] Packing / WorkbenchPacking → PackingHistory
   ↓
[출고확정] Shipment (status=95)
   ↓
[출고마감] (status=98)
   ↓
[청구] BillingStrategy → Bills / SalesInvoice
```

### 1.2 상태값 사전

| 코드 테이블 | 값 | 의미 | 적용 모델 |
|---|---|---|---|
| RCPTSTATUS | `01` | 신규 | ScheduleByReceiving |
| RCPTSTATUS | `02` | 처리 중 | ScheduleByReceiving |
| RCPTSTATUS | `95` | 확정 | ScheduleByReceiving |
| RCPTSTATUS | `98` | 마감 | ScheduleByReceiving |
| ORDERSTATUS01 | `01` | 신규 | ScheduleByShip |
| ORDERSTATUS01 | `02` | 할당 | ScheduleByShip |
| ORDERSTATUS01 | `50~94` | 피킹/포장 진행 | ScheduleByShip |
| ORDERSTATUS01 | `95` | 출고 완료 | ScheduleByShip |
| ORDERSTATUS01 | `98` | 마감 | ScheduleByShip |
| POSTATUS | (코드 테이블 참조) | 구매주문 상태 | PurchaseOrder |
| SOSTATUS | (코드 테이블 참조) | 판매주문 상태 | SalesOrder |
| BILLSTATUS | (코드 테이블 참조) | 청구 상태 | Bills (factory·pet 입금 관리에서 사용) |

---

## 2. 입고 흐름 (공통)

### 2.1 입고 예정 (ASN)

- 컨트롤러: [ScheduleByReceivingController.php](../app/Http/Controllers/main/medical/standard/ScheduleByReceivingController.php)
- 주요 메서드: `index()`, `getAllDetails()`, `searchAllReceiving()`, `getNewDetails()`
- 라우트: `/schedule_by_receiving`, `/schedule_by_receiving/get`, `/schedule_by_receiving/save`
- 모델: [ScheduleByReceiving](../app/Models/ScheduleByReceiving.php) — 테이블 `schedule_by_receivings`
- 핵심 필드: `rcpt_no`, `schedule_date`, `warehouse_id`, `ship_account_id`, `type`, `po_id`, `so_id`, `ro_id`, `status`

### 2.2 입고 처리

- 컨트롤러: [ReceivingController.php](../app/Http/Controllers/main/medical/standard/ReceivingController.php)
- 주요 메서드: `index()`, `getAllDetails()`, `searchAllReceiving()`, `saveRcptProcess()`
- 라우트: `/receiving` (medical 사이드내브에만 노출 — factory/pet는 라우트는 있으나 사이드내브에 미노출)
- 모델: [ReceivingHistory](../app/Models/ReceivingHistory.php)
- 핵심 필드: `rcpt_id`, `rcpt_detail_id`, `item_id`, `qty`, `location_id`, `lot_id`, `rcpt_flag`, `rcpt_date`
- 상태 전이: `01` → `02`

### 2.3 입고 확정

- 컨트롤러: [ReceivingConfirmController.php](../app/Http/Controllers/main/medical/standard/ReceivingConfirmController.php)
- 주요 메서드: `confirmReceiving()`, `runConfirmReceivingProcess()`
- 라우트: `/receiving_confirm`
- 상태 전이: `02` → `95`
- 처리 내역:
  - `received_qty` 증가, `not_rcpt_qty` 감소
  - `ModInv::createLot()`으로 LOT 생성
  - 재고 신규 생성 또는 누적 업데이트
  - ReceivingHistory 기록

### 2.4 입고 마감

- 컨트롤러: [ReceivingClosedController.php](../app/Http/Controllers/main/medical/standard/ReceivingClosedController.php)
- 주요 메서드: `closeReceiving()`
- 라우트: `/receiving_closed`
- 상태 전이: `95` → `98`
- 제약: 상태 `01`(신규) 또는 `98`(이미 마감)에서는 마감 불가

### 2.5 입고 취소

- 컨트롤러: [ScheduleByReceivingController.php](../app/Http/Controllers/main/medical/standard/ScheduleByReceivingController.php)의 `searchRcptCancel()` 등
- 라우트: `/receiving_cancel`
- 기능: 입고 예정·진행건 취소 처리

### 2.6 입고 현황

- 컨트롤러: [ReceivingStatusController.php](../app/Http/Controllers/main/medical/standard/ReceivingStatusController.php)
- 주요 메서드: `searchRcptStatus()`
- 라우트: `/receiving_status`

> medical 도메인은 추가로 통합 입고(`/integrated_receiving`)와 글로벌 입고 현황(`/global_receiving_status`)을 제공합니다. [WITHWORKS_기획서_medical.md](WITHWORKS_기획서_medical.md) 참조.

---

## 3. 적치 흐름 (공통)

### 3.1 적치

- 컨트롤러: [PutawayController.php](../app/Http/Controllers/main/medical/standard/PutawayController.php)
- 주요 메서드: `index()`, `getInvPutawayDetails()`, `searchPutaway()`
- 라우트: `/putaway`
- 데이터 소스: ReceivingHistory (입고이력에서 미적치건 조회)
- 핵심 필드: `location_id`, `pallet_id`, `box_id`, `lot_id`, `rcpt_flag`

### 3.2 적치 취소

- 컨트롤러: [PutawayCancelController.php](../app/Http/Controllers/main/medical/standard/PutawayCancelController.php)
- 라우트: `/putaway_cancel`
- 기능: 적치 결과 원상복구

> factory/pet은 추가로 적치 이동(`/putaway_movement`)을 제공합니다. [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) 참조.

---

## 4. 재고 흐름 (공통)

| 화면 | 컨트롤러 | 라우트 | 핵심 기능 |
|---|---|---|---|
| 재고 | [InventoryController.php](../app/Http/Controllers/main/medical/standard/InventoryController.php) | `/inventory` | 재고 조회·조정·보류·이동 |
| 입출고 이력 | InventoryHistoryController | `/inventory_history` | 재고 트랜잭션 로그 |
| 일재고 현황 | InventoryDailyHistoryController | `/inventory_daily_history` | 일별 재고 스냅샷 |
| 재고 레벨 | InventoryLevelController | `/inventory_level` | 재고 최소·최대 |
| 재고 이동 | (InventoryController) | `/inventory_movement` | 창고 간 이동 |
| LOT 속성 변경 | (InventoryController) | `/inventory_lot_change` | LOT 정보 일괄 변경 |
| 재고 보류 | (InventoryController) | `/inventory_hold` | 출고 잠금 |
| 재고 조정 | (InventoryController) | `/inventory_adjust` | 차이 보정 |
| 재고 실사 요청 | [InvPhysicalRequestController.php](../app/Http/Controllers/main/medical/standard/InvPhysicalRequestController.php) | `/inventory_physical_request` | 실사 작업 발행 |
| 재고 실사 현황 | [InvPhysicalStatusController.php](../app/Http/Controllers/main/medical/standard/InvPhysicalStatusController.php) | `/inventory_physical_status` | 실사 결과 집계 |

> medical 추가: 재고 조회(`/inventory_search`), 월별 재고 레벨, 월별 대리점 재고, 거래처 재고 조정·조회, 재고 실사 메인(`InvPhysicalController`), WMS·E1 연계. [WITHWORKS_기획서_medical.md](WITHWORKS_기획서_medical.md) 참조.
> pet 추가: 대리점 재고(`/agency_inventory`), 유효기간 관리(`/inventory_expiration_date_management`). [WITHWORKS_기획서_pet.md](WITHWORKS_기획서_pet.md) 참조.

---

## 5. 출고 흐름 (공통)

### 5.1 출고 예정

- 컨트롤러: [ScheduleByShipController.php](../app/Http/Controllers/main/medical/standard/ScheduleByShipController.php)
- 주요 메서드: `index()`, `getAllDetails()`, `searchShipment()`
- 라우트: `/schedule_by_ship`
- 모델: [ScheduleByShip](../app/Models/ScheduleByShip.php) — 테이블 `schedule_by_ships`
- 핵심 필드: `ship_no`, `so_id`, `from_warehouse_id`, `to_account_id`, `schedule_date`, `type`, `status`

### 5.2 할당

- 컨트롤러: [AssignmentController.php](../app/Http/Controllers/main/medical/standard/AssignmentController.php)
- 주요 메서드: `index()`, `getAllDetails()`, `search()`
- 라우트: `/assignment`
- 상태 전이: ScheduleByShip `01` → `02` (재고 배정)

### 5.3 피킹 관리

- 컨트롤러: [ManagementController.php](../app/Http/Controllers/main/medical/standard/ManagementController.php)
- 주요 메서드: `index()`, `getAllDetails()`, `searchAllManagement()`
- 라우트: `/management`
- 모델: [Picks](../app/Models/Picks.php)

### 5.4 포장

- 컨트롤러: [PackingController.php](../app/Http/Controllers/main/medical/standard/PackingController.php)
- 라우트: `/packing`
- 모델: [Pack](../app/Models/Pack.php)

### 5.5 워크벤치 포장

- 컨트롤러: [WorkbenchPackingController.php](../app/Http/Controllers/main/medical/standard/WorkbenchPackingController.php)
- 라우트: `/wb_packing`
- 기능: 통합 포장 + 라벨 출력

### 5.6 포장 이력

- 컨트롤러: [PackingHistoryController.php](../app/Http/Controllers/main/medical/standard/PackingHistoryController.php)
- 라우트: `/packing_history`

### 5.7 출고 확정

- 컨트롤러: [ShipmentController.php](../app/Http/Controllers/main/medical/standard/ShipmentController.php)
- 주요 메서드: `index()`, `confirm()`
- 라우트: `/ship_conf`
- 상태 전이: ScheduleByShip `02~94` → `95`
- 제약: 상태 `02`, `95`, `98`에서는 확정 불가

### 5.8 출고 현황 / 미출고 현황

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| 출고 현황 | ShipmentController | `/ship_status` |
| 미출고 현황 | UnShipStatusController | `/unship_status` |
| 기타 출고 | OtherShipmentController | `/othershipment` |
| 출고 주문 | ShipmentOrderController | `/shipmentorder` |

> medical 추가: 직접 피킹(`/direct_picking`), 출고 통합(`/integrated_ship`), 출고 실적(`/shipping_performance`).
> factory 추가: 배송사 관리(`/shipping_carrier_management`). [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) 참조.

---

## 6. 주문 흐름 (공통)

| 화면 | 컨트롤러 | 라우트 | 상태 코드 |
|---|---|---|---|
| 구매 주문 | [PurchaseOrderController.php](../app/Http/Controllers/main/medical/standard/PurchaseOrderController.php) | `/purchaseorder` | POSTATUS |
| 판매 주문 | [SalesOrderController.php](../app/Http/Controllers/main/medical/standard/SalesOrderController.php) | `/salesorder` | SOSTATUS |
| 보충 주문 | ReplenishmentOrderController | `/replenishmentorder` | — |

> medical 추가: 3PL 구매 주문(`/purchase_order_for_3pl`), 대리점 고객 주문(`/agency_customer_salesorder`).
> factory 추가: 견적(`/quotation`).

---

## 7. 반품 흐름 (공통)

| 화면 | 컨트롤러 | 라우트 | 비고 |
|---|---|---|---|
| 구매 반품 | [PurchaseReturnController.php](../app/Http/Controllers/main/medical/standard/PurchaseReturnController.php) | `/purchase_return` | POSTATUS, `udf3='POR'` 필터 |
| 판매 반품 | [SalesReturnController.php](../app/Http/Controllers/main/medical/standard/SalesReturnController.php) | `/salesreturn` | SOSTATUS, `udf3='SOR'` 필터 |

---

## 8. 청구·정산 흐름 (공통)

| 화면 | 컨트롤러 | 라우트 | 모델 |
|---|---|---|---|
| 청구 전략 | BillingStrategyController | `/billing_strategy` | [BillingStrategy](../app/Models/BillingStrategy.php), [BillingStrategyDetails](../app/Models/BillingStrategyDetails.php) |

> medical 추가: 매출 인보이스(`/sales_invoice`).
> factory·pet 추가: 입금 관리(`/deposit_management`) — Bills 모델 기반.

---

## 9. 공통 모델 연결도

```
주문·청구 (공통):
  PurchaseOrder ─→ ScheduleByReceiving (입고)
  SalesOrder ───→ ScheduleByShip (출고)

입고 흐름:
  ScheduleByReceiving ─→ ReceivingHistory ─→ Inventory
                                              └─ Lot 생성 (ModInv::createLot)

출고 흐름:
  ScheduleByShip ─→ Picks (할당)
                  └─ Pack (포장)
                  └─ ShipHistory ─→ Inventory (차감)

재고 마스터:
  Inventory ── item() → Item
            ── lot() → Lot
            ── warehouse() → Warehouse
            ── location() → Location
            ── pallet() → Pallet
            ── box() → Box
            ── account() → Account
```

---

> 본 문서는 코드베이스 정적 분석으로 작성되었습니다. 컨트롤러·모델·라우트 변경 시 본 문서도 함께 갱신해야 합니다.
