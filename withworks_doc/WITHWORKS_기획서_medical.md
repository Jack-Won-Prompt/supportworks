# WITHWORKS 도메인 기획서 — medical/standard

> medical/standard 도메인의 **전용** 프로세스 흐름 명세서입니다. 3개 도메인이 공유하는 공통 풀필먼트 흐름(입고·적치·재고·할당·피킹·포장·출고·반품·청구)은 [WITHWORKS_기획서_공통흐름.md](WITHWORKS_기획서_공통흐름.md)를 참조하세요. 본 문서는 medical에만 존재하는 특화 흐름(샘플·위탁/대리점·UDI·분석·고객 접점)만 다룹니다.

## 참조 문서

- [WITHWORKS_기획서.md](WITHWORKS_기획서.md) — 통합 명세
- [WITHWORKS_기획서_공통흐름.md](WITHWORKS_기획서_공통흐름.md) — 3 도메인 공통 풀필먼트 흐름
- [WITHWORKS_기획서_factory.md](WITHWORKS_기획서_factory.md) — factory 도메인 전용
- [WITHWORKS_기획서_pet.md](WITHWORKS_기획서_pet.md) — pet 도메인 전용

---

## 1. 도메인 개요

| 항목 | 값 |
|---|---|
| 도메인 키 | `medical/standard` |
| control_call | `medical\standard` |
| 컨트롤러 위치 | [app/Http/Controllers/main/medical/standard/](../app/Http/Controllers/main/medical/standard/) |
| 컨트롤러 수 | 139개 (공통 60 + medical 전용 79) |
| 사이드내브 메뉴 항목 | 130개 (공통 71 + medical 전용 약 57 + 다도메인 공유 2) |
| layout | [resources/views/main/medical/standard/layouts/v2/app.blade.php](../resources/views/main/medical/standard/layouts/v2/app.blade.php) |
| 사이드내브 | [resources/views/main/medical/standard/layouts/v2/sidenav.blade.php](../resources/views/main/medical/standard/layouts/v2/sidenav.blade.php) |
| 도메인 성격 | 의료 유통 — 가장 복잡한 풀필먼트. 규제 대응(UDI)·샘플·위탁·분석이 핵심 |

---

## 2. medical 전용 메뉴 그룹

medical에만 존재하는 3개 메뉴 그룹입니다. [통합 문서 섹션 7.1](WITHWORKS_기획서.md#71-medical-전용-메뉴-그룹)에 메뉴/URL 표가 있으며, 본 섹션은 각 그룹의 흐름을 설명합니다.

---

## 3. 샘플 관리 흐름

의료 유통 특성상 거래처에 제공되는 견본품(샘플) 관리가 분리되어 있습니다. CE(Conformité Européenne) 인증 대응이 포함됩니다.

### 3.1 주요 컨트롤러

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| 샘플 주문 | [SampleOrderController.php](../app/Http/Controllers/main/medical/standard/SampleOrderController.php) | `/sample_order` |
| 샘플 반품 | [SampleReturnController.php](../app/Http/Controllers/main/medical/standard/SampleReturnController.php) | `/sample_return` |
| 샘플 영업 담당 | [SampleSalesAgentController.php](../app/Http/Controllers/main/medical/standard/SampleSalesAgentController.php) | `/samplesalesagent` |
| 샘플 주문 (대리점) | [SampleOrderAgentController.php](../app/Http/Controllers/main/medical/standard/SampleOrderAgentController.php) | `/sampleorderagent` |
| 샘플 승인 요청 (이메일) | `SampleApprovalRequestEmail` | `/sample_approval_request_email` |
| CE 샘플 주문 현황 | [CESampleOrderStatusController.php](../app/Http/Controllers/main/medical/standard/CESampleOrderStatusController.php) | `/ce_sample_order_status` |

### 3.2 흐름

```
샘플 주문 생성 (SampleOrderController)
  ↓ udf2='SAMPLE', POSTATUS 적용
샘플 승인 요청 (SampleApprovalRequestEmail) — 이메일 발송
  ↓
승인 → 출고 진행 (공통 출고 흐름 진입 — 공통흐름.md §5)
  ↓
샘플 반품 (SampleReturnController) — 필요 시
  ↓
CE 샘플 주문 마감 상태 조회 (CESampleOrderStatusController)
  - getClosedStatus()
  - get_ship_confirm()
```

### 3.3 관련 모델

- [PurchaseOrder](../app/Models/PurchaseOrder.php), [PurchaseOrderDetails](../app/Models/PurchaseOrderDetails.php) — 샘플 주문 저장 (`udf2='SAMPLE'` 필터)
- [Code](../app/Models/Code.php) — POSTATUS, INOUTTYPE 코드 관리

---

## 4. 위탁·대리점 흐름

### 4.1 주요 컨트롤러

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| 대리점 재고 | [AgencyInventoryController.php](../app/Http/Controllers/main/medical/standard/AgencyInventoryController.php) | `/agency_inventory` (pet도 채택) |
| 대리점 일재고 이력 | [AgencyInventoryDailyHistoryController.php](../app/Http/Controllers/main/medical/standard/AgencyInventoryDailyHistoryController.php) | `/agency_inventory_daily_history` |
| 대리점 재고 레벨 관리 | [AgencyInventoryLevelManagementController.php](../app/Http/Controllers/main/medical/standard/AgencyInventoryLevelManagementController.php) | `/agency_inventory_level_management` |
| 대리점 목표 관리 | [AgencyTargetAmountManagementController.php](../app/Http/Controllers/main/medical/standard/AgencyTargetAmountManagementController.php) | `/agency_target_amount_management` |
| 위탁 이동 현황 | [ConsignMoveStatusController.php](../app/Http/Controllers/main/medical/standard/ConsignMoveStatusController.php) | `/consign_move_status` |
| 위탁 실적 | [ConsignmentActualController.php](../app/Http/Controllers/main/medical/standard/ConsignmentActualController.php) | `/consignment_actual` |
| 대리점 고객 SO | [AgencyCustomerSalesOrderController.php](../app/Http/Controllers/main/medical/standard/AgencyCustomerSalesOrderController.php) | `/agency_customer_salesorder` |

### 4.2 핵심 메서드

- `AgencyInventoryController::getAllItems()`, `getAllDetails()`, `invExcelDownload()`, `searchInv()` — 대리점 재고 페이징 조회·엑셀 다운로드
- `AgencyInventoryDailyHistoryController::tab1Store()`, `tab2Store()` — 일일 재고 이력 탭별 저장
- `AgencyInventoryLevelManagementController::getAllDetails()` — 대리점별 재고 최소/최대 임계
- `AgencyTargetAmountManagementController::getAllDetails()` — 대리점 목표 금액
- `ConsignMoveStatusController::getAllDetails()`, `excelDownload()` — 위탁 이동 추적

### 4.3 흐름

```
대리점 거래처 등록 (Account.biz_type=Agency)
  ↓
위탁 이동 (ScheduleByShip → ConsignMoveStatus)
  ↓ ConsignmentItem.accept_yn 추적
대리점 재고 반영 (AgencyInventory)
  ↓
일일 재고 이력 기록 (AgencyInventoryDailyHistory)
  ↓
대리점 목표 대비 실적 분석 (AgencyTargetAmount, ConsignmentActual)
```

### 4.4 관련 모델

- [Inventory](../app/Models/Inventory.php), [InventoryHistory](../app/Models/InventoryHistory.php)
- [Item](../app/Models/Item.php), [Lot](../app/Models/Lot.php), [Location](../app/Models/Location.php)
- [ScheduleByReceiving](../app/Models/ScheduleByReceiving.php), [ScheduleByShip](../app/Models/ScheduleByShip.php), [ShipHistory](../app/Models/ShipHistory.php), [ReceivingHistory](../app/Models/ReceivingHistory.php)
- [ConsignmentItem](../app/Models/ConsignmentItem.php) — 위탁 이동 수락 상태 (`accept_yn`)

---

## 5. UDI (Unique Device Identification) 흐름

의료기기 규제 요건(KFDA UDI) 대응을 위한 medical 전용 흐름.

### 5.1 주요 컨트롤러

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| UDI 품목 | [UdiItemsController.php](../app/Http/Controllers/main/medical/standard/UdiItemsController.php) | `/udi_items` |
| UDI 모델 | [UdiModelsController.php](../app/Http/Controllers/main/medical/standard/UdiModelsController.php) | `/udi_models` |
| UDI 보고 | [UdiReportController.php](../app/Http/Controllers/main/medical/standard/UdiReportController.php) | `/udi_report` |
| UDI 보고 현황 | [ReportController.php](../app/Http/Controllers/main/medical/standard/ReportController.php) | `/report` (factory도 채택) |

### 5.2 핵심 메서드

- `UdiItemsController::getAllDetails()` — UDI 등록 품목 조회
- `UdiReportController::supply_status_init()`, `oAuthInit()`, `supplyAdd()`, `saveLotList()`, `reportSupplyUdiList()` — UDI 보고 API 호출 흐름

### 5.3 흐름

```
UDI 품목 등록 (UdiItems) — UdiApiService 외부 연동
  ↓
UDI 모델 정의 (UdiModels)
  ↓
LOT 입고/출고 시 UDI 정보 결합 (공통흐름.md §2.3 확정 단계)
  ↓
UDI 보고 (UdiReport)
  - OAuth 인증 (oAuthInit)
  - 공급 정보 등록 (supplyAdd)
  - LOT 목록 저장 (saveLotList)
  - UDI 공급 보고 (reportSupplyUdiList)
  ↓
보고 현황 조회 (ReportController)
```

---

## 6. 분석·모니터링 흐름

### 6.1 주요 컨트롤러

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| 판매 현황 | [SalesStatusController.php](../app/Http/Controllers/main/medical/standard/SalesStatusController.php) | `/sales_status` |
| 품목별 판매 현황 | [ItemSalesStatusController.php](../app/Http/Controllers/main/medical/standard/ItemSalesStatusController.php) | `/item_sales_status` |
| 연계 고객 판매 현황 | [LinkCustomerSalesStatusController.php](../app/Http/Controllers/main/medical/standard/LinkCustomerSalesStatusController.php) | `/link_customer_sales_status` |
| 월마감 모니터링 | [MonthlyClosingMonitoringController.php](../app/Http/Controllers/main/medical/standard/MonthlyClosingMonitoringController.php) | `/monthly_closing_monitoring` |
| IMS/EX-CP 모니터링 | [ImsExcpMonitoringController.php](../app/Http/Controllers/main/medical/standard/ImsExcpMonitoringController.php) | `/ims_excp_monitoring` |
| IMS 실적 | [ImsActualController.php](../app/Http/Controllers/main/medical/standard/ImsActualController.php) | `/ims_actual` |
| EX-CP 실적 | [ExcpActualController.php](../app/Http/Controllers/main/medical/standard/ExcpActualController.php) | `/excp_actual` |

### 6.2 핵심 메서드

- `SalesStatusController` — `getInmarketCloseStatus()`, `getSalesConfirmStatus()`, `getShipmentStatus()`, `monthlyCloseConfirm()`
- `MonthlyClosingMonitoringController` — `getAllImsLists()`, `getAllExcpLists()`, `bulkUpdate()`
- `ExcpActualController` — `getAchievementRate()`, `getAgencyAchievementRatePopup()`, `sendEmail()`

### 6.3 흐름

```
일일 판매 데이터 누적 (SalesOrder → SalesOrderDetails — 공통흐름.md §6)
  ↓
판매 현황 집계 (SalesStatus)
  ↓
IMS·EX-CP 모니터링 (ImsExcpMonitoring)
  ↓
실적 달성률 계산 (ExcpActual::getAchievementRate)
  ↓
월마감 (MonthlyClosingMonitoring::bulkUpdate)
  ↓
거래처별 실적 이메일 발송 (ExcpActual::sendEmail)
```

---

## 7. 고객 접점 흐름 (medical 전용 컨트롤러)

### 7.1 주요 컨트롤러

| 화면 | 컨트롤러 | 라우트 |
|---|---|---|
| 고객 상담 | [AccountCounsellingController.php](../app/Http/Controllers/main/medical/standard/AccountCounsellingController.php) | `/account_counselling` |
| 클레임 | [ComplaintController.php](../app/Http/Controllers/main/medical/standard/ComplaintController.php) | `/complaint` |
| 클레임 현황 | [ComplaintStatusController.php](../app/Http/Controllers/main/medical/standard/ComplaintStatusController.php) | `/complaint_status` |

> 고객 서비스(`/customerservice`)는 3 도메인 공통 — [공통흐름.md](WITHWORKS_기획서_공통흐름.md) 참조.

### 7.2 핵심 메서드

- `AccountCounsellingController::getCounsellingHeaders()`, `reOpenCounselling()`, `cancelAll()`
- `ComplaintController::getNewComplaint()`, `store()`, `imageUploadPopup()` (이미지 첨부)

### 7.3 흐름

```
거래처 상담 접수 (AccountCounselling)
  ↓ 상담 헤더 + 상세 기록
  ↓
클레임 발생 시 (Complaint)
  ↓ 이미지 첨부, 거래처/품목/LOT 연결
  ↓ 상태 추적 (ComplaintStatus)
  ↓
해결 → 종결 (cancelAll) 또는 재오픈 (reOpenCounselling)
```

### 7.4 관련 모델

- [Counselling](../app/Models/Counselling.php), [CounsellingDetail](../app/Models/CounsellingDetail.php)
- [Complaint](../app/Models/Complaint.php), [ComplaintDetail](../app/Models/ComplaintDetail.php), [ComplaintImage](../app/Models/ComplaintImage.php)
- [Inquiry](../app/Models/Inquiry.php), [InquiryDetail](../app/Models/InquiryDetail.php), [InquiryFile](../app/Models/InquiryFile.php)

---

## 8. medical 전용 추가 흐름 (공통 그룹에 medical만 추가)

medical 메뉴 중 공통 그룹에 medical만 추가한 항목들의 흐름은 [통합 문서 §7.2](WITHWORKS_기획서.md#72-공통-그룹의-medical-추가-항목)의 URL·컨트롤러 매핑을 참조하세요. 주요 항목:

- 거래처·고객 9개 (담당자/모니터링/판매현황/상담/매핑/개인거래처/추가정보/클레임/클레임현황)
- 품목 6개 (단가 관리·조회·LOT 마스터·LOT 관리·공지 관리·검사 보고서 마스터)
- 입고 3개 (입고·통합 입고·글로벌 입고 현황)
- 출고 3개 (직접 피킹·출고 통합·출고 실적)
- 재고 조회 3개 (재고 조회·월별 재고 레벨·월별 대리점 재고)
- 재고 관리 3개 (거래처 재고 조정·조회·재고 실사)
- 청구·정산 1개 (매출 인보이스)
- ERP·UDI 5개 (WMS·E1 연계 + UDI 4)
- 시스템 3개 (공통 코드·개인정보 관리·시스템 셋팅)

---

## 9. medical 도메인 핵심 모델 연결도

```
주문·청구 (공통흐름.md §6,§8 참조):
  PurchaseOrder ──┐
                   ├─ SampleOrderAgent (udf2='SAMPLE')
  SalesOrder ─────┘
  ↓
  ScheduleByReceiving ─→ ReceivingHistory ─→ Inventory
  ↓
  ScheduleByShip ─→ ShipHistory ─→ Inventory (차감)
                  └─ ConsignMoveStatus → ConsignmentItem (accept_yn)
  ↓
  Bills → BillDetails (SalesInvoice)

위탁·대리점 (medical 전용):
  Account (biz_type=Agency)
  ├── AgencyInventory ─→ AgencyInventoryDailyHistory
  ├── AgencyInventoryLevelManagement (임계치)
  ├── AgencyTargetAmountManagement (목표)
  └── ConsignmentActual

UDI (medical 전용):
  UdiItems ←→ UdiApiService (외부)
  ├── UdiModels
  └── UdiReport ─→ ReportController (보고 현황)

분석·모니터링 (medical 전용):
  SalesStatus
  ├── ItemSalesStatus
  ├── LinkCustomerSalesStatus
  ├── ImsExcpMonitoring ─→ ImsActual, ExcpActual
  └── MonthlyClosingMonitoring (bulkUpdate)

고객 접점 (medical 전용):
  Counselling ─→ CounsellingDetail
  Complaint ─→ ComplaintDetail, ComplaintImage
```

---

> 본 문서는 코드베이스 정적 분석으로 작성되었습니다.
