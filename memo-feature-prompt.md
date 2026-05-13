# 메모 기능 구현 프롬프트

아래 사양을 기반으로 웹 애플리케이션에 **메모 기능**을 구현해주세요.

---

## 1. 기능 개요

글로벌 레이아웃 최상단 헤더에 메모 버튼을 추가하고, 버튼 클릭 시 팝업이 열려 메모를 관리할 수 있습니다. 메모는 "고정(Pin)" 기능으로 화면 위에 floating 창으로 띄울 수 있으며, 다른 페이지로 이동해도 항상 표시됩니다. 메모는 다른 멤버에게 공유할 수 있고, 공유 받은 메모도 고정할 수 있습니다.

---

## 2. 데이터베이스 스키마

### memos 테이블
```sql
CREATE TABLE memos (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,  -- 작성자 (FK → users)
    title       VARCHAR(200) NULL,
    content     TEXT NOT NULL,
    color       VARCHAR(20) DEFAULT 'yellow',  -- yellow|green|blue|pink|purple
    is_pinned   BOOLEAN DEFAULT FALSE,
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### memo_shares 테이블
```sql
CREATE TABLE memo_shares (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    memo_id     BIGINT UNSIGNED NOT NULL,   -- FK → memos
    shared_by   BIGINT UNSIGNED NOT NULL,   -- 공유한 사람 (FK → users)
    shared_to   BIGINT UNSIGNED NOT NULL,   -- 공유 받은 사람 (FK → users)
    is_pinned   BOOLEAN DEFAULT FALSE,      -- 수신자 기준 고정 여부
    created_at  TIMESTAMP,
    updated_at  TIMESTAMP,
    UNIQUE KEY (memo_id, shared_to),
    FOREIGN KEY (memo_id)   REFERENCES memos(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (shared_to) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## 3. API 사양

모든 API는 로그인 필요. JSON 응답/요청 사용.

| Method   | URL                        | 설명                              |
|----------|----------------------------|-----------------------------------|
| GET      | /memos                     | 내 메모 목록 + 공유받은 메모 목록 |
| POST     | /memos                     | 메모 생성                         |
| PATCH    | /memos/{id}                | 메모 수정                         |
| DELETE   | /memos/{id}                | 메모 삭제                         |
| PATCH    | /memos/{id}/pin            | 내 메모 고정/해제 토글            |
| GET      | /memos/members             | 공유 가능한 멤버 목록             |
| POST     | /memos/{id}/share          | 멤버에게 메모 공유                |
| DELETE   | /memos/{id}/share          | 공유 취소                         |
| PATCH    | /memo-shares/{shareId}/pin | 공유받은 메모 고정/해제 토글      |

> **주의:** `/memos/members`는 반드시 `/memos/{id}` 와일드카드 라우트보다 먼저 등록해야 합니다.

### GET /memos 응답 형식
```json
{
  "mine": [
    {
      "id": 1,
      "title": "회의 준비",
      "content": "내용...",
      "color": "yellow",
      "is_pinned": true,
      "updated_at": "3분 전",
      "shared_with": [
        { "user_id": 2, "name": "홍길동", "avatar": null }
      ]
    }
  ],
  "shared": [
    {
      "share_id": 5,
      "id": 3,
      "title": "공유된 메모",
      "content": "내용...",
      "color": "blue",
      "is_pinned": false,
      "updated_at": "1시간 전",
      "shared_with": [],
      "is_received": true,
      "shared_by_id": 2,
      "shared_by_name": "홍길동",
      "shared_at": "2시간 전"
    }
  ]
}
```

### POST /memos 요청/응답
```json
// 요청
{ "title": "제목(선택)", "content": "내용(필수)", "color": "yellow" }

// 응답 201
{ "id": 1, "title": "...", "content": "...", "color": "yellow", "is_pinned": false, "updated_at": "방금 전", "shared_with": [] }
```

### PATCH /memos/{id} 요청
```json
{ "content": "수정된 내용" }
// title이 없으면 기존 title 유지 (덮어쓰지 않음)
```

### POST /memos/{id}/share 요청
```json
{ "user_ids": [2, 3, 5] }
```

### DELETE /memos/{id}/share 요청
```json
{ "user_id": 2 }
```

### GET /memos/members 응답
```json
[
  { "id": 2, "name": "홍길동", "email": "hong@example.com", "avatar": null }
]
// 현재 로그인 사용자 제외, admin/member 역할만
```

---

## 4. UI 명세

### 4-1. 헤더 버튼
- 글로벌 레이아웃 헤더 우측에 연필 아이콘 + "메모" 텍스트 버튼 배치
- 클릭 시 메모 팝업 토글 (열기/닫기)
- 팝업 외부 클릭 시 팝업 닫힘

### 4-2. 메모 팝업
- 위치: `position:fixed; top:60px; right:20px; z-index:9995`
- 크기: `width:360px; max-height:calc(100vh - 80px)`
- 구성:
  1. **헤더**: "메모" 타이틀 + "메모 추가" 버튼 + 닫기(X) 버튼
  2. **추가 폼** (숨김 상태, 버튼 클릭 시 노출):
     - 제목 입력 (선택)
     - 내용 textarea (필수)
     - 색상 선택: 노랑/초록/파랑/핑크/보라 색상 원형 도트
     - 취소/저장 버튼
  3. **메모 목록**:
     - 내 메모 카드 목록
     - 구분선 + "공유 받은 메모" 라벨
     - 공유받은 메모 카드 목록

### 4-3. 메모 카드 (팝업 내)
각 메모 카드의 구성:
- **공유받은 메모**: 상단에 보라색 뱃지로 "**홍길동 · 2시간 전**" 표시
- 제목 (있을 경우, bold)
- 내용 미리보기 (130자 초과 시 "…" 처리)
- 하단 row:
  - 좌: 마지막 수정 시각 ("3분 전" 형식)
  - 우: 버튼들
    - **공유 아이콘** (내 메모만): 공유 중이면 보라색 강조, 공유된 멤버 이니셜 아바타 표시
    - **핀 버튼**: 고정 상태면 노란색 배경
    - **삭제 버튼**: hover 시 빨간색

### 4-4. 색상 팔레트
```javascript
const COLORS = {
  yellow: { bg: '#fef9c3', border: '#fde047', header: '#fef08a' },
  green:  { bg: '#dcfce7', border: '#86efac', header: '#bbf7d0' },
  blue:   { bg: '#dbeafe', border: '#93c5fd', header: '#bfdbfe' },
  pink:   { bg: '#fce7f3', border: '#f9a8d4', header: '#fbcfe8' },
  purple: { bg: '#ede9fe', border: '#c4b5fd', header: '#ddd6fe' },
};
```

### 4-5. Floating 고정 메모 창
핀 버튼 클릭 시 화면 위에 항상 표시되는 sticky note 창 생성.

**기본 위치**: 화면 우하단 (right:24px, bottom:80px), 여러 개면 230px 간격으로 스택
**구조**:
```
┌─────────────────────────────┐  ← 드래그 가능 헤더 (grab 커서)
│ 제목              📌 🗑    │
├─────────────────────────────┤
│ [공유받은 메모] 홍길동 공유  │  ← 보라색 서브헤더 (공유메모만)
├─────────────────────────────┤
│                             │
│  내용 (contenteditable,     │  ← 클릭해서 바로 편집 가능
│  자동 저장)                 │  ← (내 메모만)
│                             │
└─────────────────────────── ◢│  ← 우하단 리사이즈 핸들
```

**기능 요구사항**:
1. **드래그**: 헤더를 드래그해서 화면 어디든 이동 (button 클릭은 드래그 트리거 제외)
2. **리사이즈**: 우하단 핸들 드래그로 크기 변경 (최소 180×100px)
3. **위치/크기 저장**: localStorage에 `memo-pos-{id}`, `memo-size-{id}` 키로 저장, 페이지 새로고침 후 복원
4. **자동 저장** (내 메모만):
   - body에 `contenteditable="true"` 적용
   - 입력 후 700ms debounce로 PATCH API 호출
   - 저장 상태 표시: "..." → "저장 중..." → "✓ 저장됨" (1.8초 후 사라짐)
   - 붙여넣기는 plain text만 허용
5. **터치 지원**: touchstart/touchmove/touchend 이벤트로 모바일 드래그/리사이즈 지원

**초기 로드**: 페이지 로드 시 서버에서 `is_pinned=true`인 메모를 직접 렌더링 (AJAX 불필요)

### 4-6. 공유 모달
공유 버튼 클릭 시 표시되는 모달:
- 위치: `position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999` (가운데 정렬)
- 외부 배경 클릭 시 닫힘
- 구성:
  1. **헤더**: 공유 아이콘 + "메모 공유" 타이틀 + X 버튼
  2. **검색창**: 이름/이메일 실시간 필터
  3. **멤버 목록** (스크롤):
     - 멤버별 row: 이니셜 아바타 + 이름 + 이메일 + (기존 공유 시 "공유중" 뱃지) + 체크박스
     - 체크박스 토글로 선택/해제
  4. **하단**: "N명 선택됨" + 취소/공유하기 버튼

**공유 처리 로직**:
- 새로 체크한 멤버 → POST /memos/{id}/share
- 기존 공유 중이었는데 체크 해제한 멤버 → DELETE /memos/{id}/share
- 두 작업을 `Promise.all`로 동시 처리

---

## 5. JavaScript 구조

전체 JS는 즉시실행함수(IIFE)로 감싸서 전역 오염 방지. 외부에서 호출할 함수만 `window.함수명`으로 노출.

```javascript
(function() {
  // 상수
  var CSRF     = document.querySelector('meta[name="csrf-token"]').content;
  var MEMO_URL = '/memos';  // 서버에서 절대경로로 주입 (subdirectory 대응)

  // 색상 팔레트
  var MC = { yellow: {...}, green: {...}, ... };

  // HTML escape 유틸
  function esc(str) {
    var d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
  }

  // ── 팝업 제어
  window.memoPopupToggle = function() { ... };
  window.memoPopupClose  = function() { ... };
  window.memoShowAddForm = function() { ... };
  window.memoHideAddForm = function() { ... };

  // ── 색상 선택
  window.memoSelectColor = function(dot) { ... };

  // ── 목록 로드
  function memoLoadList() {
    // GET /memos → { mine:[], shared:[] }
    // mine 먼저 렌더링, 그 아래 shared 렌더링
  }

  // ── 카드 렌더링
  function renderMemoCard(m) {
    // m.is_received 여부로 분기
    // 공유받은 카드: receivedBadge, 핀버튼→memoToggleSharedPin
    // 내 카드: shareBtnHtml, sharedAvatars, 핀버튼→memoTogglePin
  }

  // ── CRUD
  window.memoSave        = function() { /* POST /memos */ };
  window.memoTogglePin   = function(id) { /* PATCH /memos/{id}/pin */ };
  window.memoDelete      = function(id) { /* DELETE /memos/{id} */ };

  // ── 공유받은 메모 고정
  window.memoToggleSharedPin = function(shareId, memoId) {
    // PATCH /memo-shares/{shareId}/pin
  };

  // ── 공유 모달
  var _shareTargetId   = null;
  var _shareAllMembers = [];
  var _shareSelected   = {};      // { userId: boolean }
  var _shareAlreadyShared = {};   // { userId: true } (기존 공유 상태)

  window.memoShareOpen        = function(id) { ... };
  window.memoShareToggle      = function(uid, row) { ... };
  window.memoShareFilterMembers = function(q) { ... };
  window.memoShareModalClose  = function() { ... };
  window.memoShareConfirm     = function() { ... };

  // ── Floating 노트 관리
  function memoUpdatePinnedNote(memo) {
    // memo.is_pinned → 노트 생성/제거
    // 생성 후: makeDraggable + makeResizable + setupNoteAutoSave
  }

  function memoUpdatePinnedSharedNote(memo) {
    // memo.is_pinned → 공유 노트 생성/제거
    // data-share-id 속성으로 조회
    // 생성 후: makeDraggable + makeResizable (자동저장 없음)
  }

  // ── 드래그/리사이즈 시스템
  var _drag   = { el: null, ox: 0, oy: 0 };
  var _resize = { el: null, startX: 0, startY: 0, startW: 0, startH: 0 };

  // 단일 전역 핸들러 (mousemove/mouseup/touchmove/touchend)
  document.addEventListener('mousemove', function(e) {
    if (_drag.el)   { /* left/top 업데이트 (화면 경계 클램프) */ }
    if (_resize.el) { /* width/height 업데이트 (최소 180×100) */ }
  });
  document.addEventListener('mouseup', function() {
    if (_drag.el)   { /* localStorage 저장, cursor 복원 */ }
    if (_resize.el) { /* localStorage 저장 */ }
  });

  function makeDraggable(el) {
    // localStorage에서 위치 복원 → left/top 설정, right/bottom 제거
    // header mousedown → _drag 설정
    // button 클릭은 제외: e.target.closest('button') 체크
  }

  function makeResizable(el) {
    // localStorage에서 크기 복원
    // .pinned-memo-resize mousedown → _resize 설정
  }

  function setupNoteAutoSave(el) {
    // body._autoSaveInit 중복 방지
    // body.contentEditable = 'true'
    // status span (헤더 안에 삽입)
    // input → clearTimeout → showStatus('...') → setTimeout(doSave, 700)
    // paste → plain text만 허용
    // mousedown → e.stopPropagation() (드래그 방지)
  }

  // ── 초기화
  document.querySelectorAll('.pinned-memo-note').forEach(function(el) {
    makeDraggable(el);
    makeResizable(el);
    if (!el.dataset.shareId) setupNoteAutoSave(el); // 내 메모만 자동저장
  });

  // 팝업/모달 외부 클릭 닫기
  document.addEventListener('click', function(e) { ... });
})();
```

---

## 6. 서버 사이드 초기 렌더링 (페이지 로드)

글로벌 레이아웃의 `<body>` 내부에서 고정된 메모를 직접 렌더링:

```php
// 내 메모 중 고정된 것
$pinnedMemos = Memo::where('user_id', auth()->id())
    ->where('is_pinned', true)
    ->orderByDesc('updated_at')
    ->get();

// 공유받아 고정한 것
$pinnedSharedMemos = MemoShare::where('shared_to', auth()->id())
    ->where('is_pinned', true)
    ->with(['memo', 'sharedByUser'])
    ->latest()
    ->get();
```

- 각 메모를 `position:fixed` div로 렌더링
- 기본 위치: `right:24px; bottom:{80 + index * 230}px`
- 내 메모 고정 노트: `data-id="{memo.id}"`
- 공유받은 고정 노트: `data-id="{memo.id}" data-share-id="{share.id}"`
- 페이지 로드 후 JS의 `makeDraggable()/makeResizable()` 초기화 실행

---

## 7. 핵심 구현 주의사항

1. **URL 생성**: 서브디렉토리 환경(예: `/app/memos`)에서도 동작하도록 URL은 서버에서 절대경로로 생성해 JS 변수에 주입할 것.
   ```javascript
   var MEMO_URL = '<?= url("/memos") ?>'; // PHP/서버 템플릿에서 생성
   ```

2. **PATCH 시 title 보존**: 내용만 수정하는 자동저장 PATCH 요청에 title을 포함하지 않을 경우, 서버에서 `title`이 없으면 기존 title을 유지하도록 처리.
   ```php
   'title' => $request->has('title') ? $request->title : $memo->title,
   ```

3. **드래그 핸들러 중복 방지**: per-element 이벤트 리스너 대신 단일 전역 `mousemove/mouseup` 핸들러를 사용해 메모리 누수 방지.

4. **버튼 클릭 시 드래그 방지**: `mousedown` 핸들러에서 `e.target.closest('button')` 체크로 버튼 위 클릭이 드래그를 시작하지 않도록 방지.

5. **body 클릭 시 드래그 방지**: 본문 `mousedown`에서 `e.stopPropagation()`으로 헤더의 드래그 핸들러가 발동하지 않도록 방지.

6. **리사이즈 + flex**: floating 노트에 `display:flex; flex-direction:column` 적용, body에 `flex:1`로 남은 공간 채우기.

7. **공유 해제**: 공유 모달에서 기존 공유 멤버(`_shareAlreadyShared`)와 현재 선택(`_shareSelected`)을 비교해 새 공유/해제를 `Promise.all`로 동시 처리.

8. **공유받은 메모의 고정**: `memo_shares.is_pinned` 컬럼 사용 (원본 `memos.is_pinned`와 독립). 수신자 본인만 토글 가능 (`shared_to === currentUserId` 검증).

9. **자동저장 중복 방지**: `body._autoSaveInit` 플래그로 `setupNoteAutoSave`가 같은 요소에 중복 초기화되지 않도록 방지.

10. **공유 모달 기존 공유 상태 복원**: 모달 오픈 시 해당 카드의 `data-shared-with` 속성(JSON)을 읽어 기존 공유 멤버를 체크 상태로 표시.

---

## 8. 기술 스택 참고 (Laravel 기준)

- **Model**: `Memo`, `MemoShare` (Eloquent ORM)
- **Controller**: `MemoController` — `index, store, update, togglePin, destroy, share, unshare, members, toggleSharedPin`
- **Policy**: abort_if로 소유권 검증
- **Response**: `wantsJson()` 체크로 동일 메서드에서 JSON/Redirect 분기
- **Template**: Blade (`@php`, `@foreach`, `{{ url() }}` 헬퍼로 URL 생성)

다른 프레임워크 사용 시 위 API 사양과 JS 구조를 그대로 유지하면 동일하게 구현 가능합니다.
