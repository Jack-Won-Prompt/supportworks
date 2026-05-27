<?php

/*
 * 구축 설계 산출물 (Build Design Deliverables) — S1 ~ S13
 *
 * 신규 산출물 13종 — 시스템 구축 시 설계 단계에 작성하는 상세 명세
 * 카테고리 그룹은 메인 산출물(deliverables.php)과 별도 탭으로 노출.
 *
 * 종속 관계 (사용자 입력 요구사항 §):
 *   S1 Menu        (최상위)        — 계층 트리 (자기참조)
 *   S2 Screen      (S1 하위)       — 계층 트리 노드
 *   S3 ScreenFlow  (S2 참조)       — 화면 간 관계 그래프
 *   S4 UILayout    (S2 종속 1:1)   — 화면당 영역/레이아웃
 *   S5 Component   (S4 종속)       — 마스터-디테일 (1:N)
 *   S6 CompProp    (S5 종속)       — 속성 행 집합
 *   S7 DataModel   (공통 참조)     — 엔티티-속성 정의
 *   S8 FieldMap    (S5 ↔ S7)      — 매핑 테이블
 *   S9 Config      (공통 참조)     — 키-값 파라미터 테이블 [이관·운영]
 *   S10 Interface  (공통 참조)     — API/연계 항목 표
 *   S11 CodeDef    (공통 참조)     — 코드 그룹-값 테이블
 *   S12 Permission (S2 ↔ 역할)    — 화면×역할 매트릭스 [보안]
 *   S13 SysArch    (공통 참조)     — 서버/계층 구조도 [이관·운영]
 *
 * MVP 단계: 기존 도구(TABLE-DATA, MATRIX-RBAC, DIAGRAM-FLOW 등) 재사용.
 * 향후 자체 도구(TREE-MENU, GRAPH-FLOW, GRID-LAYOUT 등) 신규 구축 예정.
 */

return [

    'tab_label' => '구축 설계 산출물',

    'deliverables' => [

        // ─────────────────────────────────────────────────────────────────
        // S1 — Menu 메뉴 구조도
        // ─────────────────────────────────────────────────────────────────
        'S1_Menu' => [
            'no'             => 'S1',
            'name'           => '메뉴 구조도',
            'shortName'      => 'Menu',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계 초기',
            'depends_on'     => null,
            'depend_label'   => '최상위',
            'form_type'      => '계층 트리 (자기참조)',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'메뉴 구조 개요','description'=>'설계 방향, 메뉴 그룹 분류 원칙','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'최상위 메뉴 그룹','description'=>'1뎁스 메뉴 정의','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'topMenus','label'=>'최상위 메뉴 (ID·이름·순서·아이콘)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'하위 메뉴 트리','description'=>'2뎁스 이상 메뉴, 부모ID 참조','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'menuTree','label'=>'메뉴 트리 (ID·부모ID·이름·순서·URL)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>4,'title'=>'메뉴 표시 조건','description'=>'역할·환경별 노출 규칙','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'visibility','label'=>'메뉴 노출 조건 (메뉴ID·조건·설명)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S2 — Screen 화면 목록 (레벨)
        // ─────────────────────────────────────────────────────────────────
        'S2_Screen' => [
            'no'             => 'S2',
            'name'           => '화면 목록 (레벨)',
            'shortName'      => 'Screen',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => 'S1_Menu',
            'depend_label'   => 'S1 하위',
            'form_type'      => '계층 트리 노드',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'화면 분류 원칙','description'=>'화면 분류 기준, 명명 규칙','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'화면 목록','description'=>'전체 화면 인벤토리','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'screens','label'=>'화면 (ID·이름·메뉴ID·레벨·설명·상태)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>3,'title'=>'화면 분류','description'=>'기능 그룹·유형별 분류','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'screenGroups','label'=>'분류 (그룹·화면ID·설명)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'화면별 책임자','description'=>'개발·검토 담당자','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'screenOwners','label'=>'책임자 (화면ID·개발자·검토자·기한)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S3 — ScreenFlow 화면 흐름/네비게이션
        // ─────────────────────────────────────────────────────────────────
        'S3_ScreenFlow' => [
            'no'             => 'S3',
            'name'           => '화면 흐름/네비게이션',
            'shortName'      => 'ScreenFlow',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => 'S2_Screen',
            'depend_label'   => 'S2 참조',
            'form_type'      => '화면 간 관계 그래프',
            'primaryTools'   => ['DIAGRAM-FLOW', 'TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'사용자 시나리오','description'=>'주요 업무 시나리오 정리','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'scenarios','label'=>'시나리오 설명','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'화면 전환 정의','description'=>'From → To 관계','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'transitions','label'=>'전환 (From·To·트리거·조건)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DIAGRAM-FLOW']],'required'=>true],
                ['order'=>3,'title'=>'분기/조건부 흐름','description'=>'조건별 화면 분기','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'branches','label'=>'분기 (시작화면·조건·경로A·경로B)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'네비게이션 패턴','description'=>'공통 네비 구조 (헤더·사이드·탭)','inputType'=>'form','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'navPatterns','label'=>'네비게이션 패턴 설명','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S4 — UILayout 화면별 UI 구성도
        // ─────────────────────────────────────────────────────────────────
        'S4_UILayout' => [
            'no'             => 'S4',
            'name'           => '화면별 UI 구성도',
            'shortName'      => 'UILayout',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => 'S2_Screen',
            'depend_label'   => 'S2 종속 (1:1)',
            'form_type'      => '화면당 영역/레이아웃',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'레이아웃 원칙','description'=>'공통 그리드·반응형 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'화면별 영역 정의','description'=>'화면당 영역(헤더·본문·사이드 등)','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'layouts','label'=>'레이아웃 (화면ID·영역·위치·비율·설명)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'반응형 분기','description'=>'화면 너비별 레이아웃 변화','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'responsive','label'=>'반응형 (화면ID·브레이크포인트·변경 사항)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'디자인 토큰 매핑','description'=>'색상·타이포·간격 토큰 적용','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'tokens','label'=>'토큰 매핑 (영역·토큰명·값)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S5 — Component 화면당 컴포넌트 정의
        // ─────────────────────────────────────────────────────────────────
        'S5_Component' => [
            'no'             => 'S5',
            'name'           => '화면당 컴포넌트 정의',
            'shortName'      => 'Component',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => 'S4_UILayout',
            'depend_label'   => 'S4 종속',
            'form_type'      => '마스터-디테일 (1:N)',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'컴포넌트 분류 원칙','description'=>'공통·전용 컴포넌트 구분','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'화면별 컴포넌트 배치','description'=>'화면-영역-컴포넌트 매핑','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'components','label'=>'컴포넌트 (화면ID·영역·컴포넌트명·타입·순서)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'공통 컴포넌트 카탈로그','description'=>'재사용 컴포넌트 정의','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'commonComps','label'=>'공통 컴포넌트 (이름·타입·용도·예시)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'사용 규칙','description'=>'컴포넌트 사용 가이드','inputType'=>'form','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'usageRules','label'=>'사용 규칙','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S6 — CompProp 컴포넌트 속성/이벤트
        // ─────────────────────────────────────────────────────────────────
        'S6_CompProp' => [
            'no'             => 'S6',
            'name'           => '컴포넌트 속성/이벤트',
            'shortName'      => 'CompProp',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => 'S5_Component',
            'depend_label'   => 'S5 종속',
            'form_type'      => '속성 행 집합',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'속성 정의 원칙','description'=>'속성 명명·타입 규칙','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'컴포넌트별 속성','description'=>'각 컴포넌트의 입력·표시 속성','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'props','label'=>'속성 (컴포넌트·속성명·타입·기본값·필수)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'이벤트 정의','description'=>'사용자 액션 → 시스템 반응','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'events','label'=>'이벤트 (컴포넌트·이벤트명·트리거·액션·대상)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>4,'title'=>'유효성 규칙','description'=>'입력 검증 정책','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'validations','label'=>'유효성 (컴포넌트·규칙·메시지)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S7 — DataModel 데이터 모델/ERD
        // ─────────────────────────────────────────────────────────────────
        'S7_DataModel' => [
            'no'             => 'S7',
            'name'           => '데이터 모델/ERD',
            'shortName'      => 'DataModel',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => null,
            'depend_label'   => '공통 참조',
            'form_type'      => '엔티티-속성 정의',
            'primaryTools'   => ['DIAGRAM-ERD', 'TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'데이터 모델링 원칙','description'=>'정규화·명명·이력 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'엔티티 목록','description'=>'테이블·도메인 단위','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'entities','label'=>'엔티티 (테이블명·논리명·설명·도메인)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>3,'title'=>'속성 정의','description'=>'테이블별 컬럼·타입·제약','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'columns','label'=>'컬럼 (테이블·컬럼·타입·길이·NULL·PK·FK·설명)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>4,'title'=>'관계 정의','description'=>'엔티티 간 PK/FK 관계','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'relations','label'=>'관계 (Parent·Child·유형·카디널리티·On Delete)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DIAGRAM-ERD']],'required'=>true],
                ['order'=>5,'title'=>'인덱스/제약','description'=>'성능·무결성 정책','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'indexes','label'=>'인덱스/제약 (테이블·이름·유형·컬럼·이유)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S8 — FieldMap 화면-필드 매핑
        // ─────────────────────────────────────────────────────────────────
        'S8_FieldMap' => [
            'no'             => 'S8',
            'name'           => '화면-필드 매핑',
            'shortName'      => 'FieldMap',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계 후반',
            'depends_on'     => 'S7_DataModel',
            'depend_label'   => 'S5 ↔ S7',
            'form_type'      => '매핑 테이블',
            'primaryTools'   => ['TABLE-DATA', 'MAPPING', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'매핑 원칙','description'=>'양방향 매핑·변환 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'화면-엔티티 매핑','description'=>'화면 컴포넌트 ↔ 테이블 컬럼','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'fieldMap','label'=>'매핑 (화면·컴포넌트·엔티티·컬럼·방향)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>3,'title'=>'입력 검증 매핑','description'=>'UI 검증 ↔ DB 제약','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'inputValidation','label'=>'입력 검증 (필드·UI 규칙·DB 제약)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'출력 변환','description'=>'표시 포맷·코드값 변환','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'outputTransform','label'=>'출력 변환 (필드·DB 값·표시 형식·변환 로직)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S9 — Config 컨피그 구성도 (이관·운영)
        // ─────────────────────────────────────────────────────────────────
        'S9_Config' => [
            'no'             => 'S9',
            'name'           => '컨피그 구성도',
            'shortName'      => 'Config',
            'category'       => 'operations',
            'responsibility' => 'B',
            'timing'         => '설계·이관 단계',
            'depends_on'     => null,
            'depend_label'   => '공통 참조',
            'form_type'      => '키-값 파라미터 테이블',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'설정 관리 원칙','description'=>'환경 분리·시크릿 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'환경별 설정','description'=>'Dev·Stage·Prod 컨피그','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'envConfig','label'=>'설정 (키·Dev·Stage·Prod·설명)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'외부 연동 컨피그','description'=>'API 키·URL·옵션','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'externalConfig','label'=>'외부 (서비스·키·환경·암호화 여부)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'시크릿/암호화','description'=>'민감 정보 저장 정책','inputType'=>'form','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'secrets','label'=>'시크릿 관리 정책','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S10 — Interface 인터페이스/연계 정의
        // ─────────────────────────────────────────────────────────────────
        'S10_Interface' => [
            'no'             => 'S10',
            'name'           => '인터페이스/연계 정의',
            'shortName'      => 'Interface',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => null,
            'depend_label'   => '공통 참조',
            'form_type'      => 'API/연계 항목 표',
            'primaryTools'   => ['TABLE-DATA', 'DIAGRAM-SEQ', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'연계 설계 원칙','description'=>'동기·비동기·재시도 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'인바운드 API','description'=>'시스템이 제공하는 API','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'inboundApi','label'=>'인바운드 (Method·URL·Request·Response·인증)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'아웃바운드 연계','description'=>'외부 시스템 호출','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'outboundApi','label'=>'아웃바운드 (대상·Method·URL·트리거·실패 처리)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DIAGRAM-SEQ']],'required'=>true],
                ['order'=>4,'title'=>'인증/보안','description'=>'토큰·암호화 정책','inputType'=>'form','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'apiSecurity','label'=>'인증·보안 정책','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S11 — CodeDef 공통코드 정의서
        // ─────────────────────────────────────────────────────────────────
        'S11_CodeDef' => [
            'no'             => 'S11',
            'name'           => '공통코드 정의서',
            'shortName'      => 'CodeDef',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '설계 단계',
            'depends_on'     => null,
            'depend_label'   => '공통 참조',
            'form_type'      => '코드 그룹-값 테이블',
            'primaryTools'   => ['TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'코드 관리 원칙','description'=>'명명 규칙·다국어 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'코드 그룹','description'=>'논리적 코드 묶음 정의','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'codeGroups','label'=>'그룹 (GroupID·이름·설명·사용처)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'코드 값','description'=>'그룹별 값 정의','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'codeValues','label'=>'값 (GroupID·Value·Label·순서·사용여부)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>4,'title'=>'다국어 매핑','description'=>'코드 값 번역','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'i18n','label'=>'다국어 (Value·KO·EN·기타)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S12 — Permission 화면별 권한 정의 (보안)
        // ─────────────────────────────────────────────────────────────────
        'S12_Permission' => [
            'no'             => 'S12',
            'name'           => '화면별 권한 정의',
            'shortName'      => 'Permission',
            'category'       => 'security',
            'responsibility' => 'B',
            'timing'         => '설계 단계 후반',
            'depends_on'     => 'S2_Screen',
            'depend_label'   => 'S2 ↔ 역할',
            'form_type'      => '화면×역할 매트릭스',
            'primaryTools'   => ['MATRIX-RBAC', 'TABLE-DATA', 'AI-CHAT'],
            'steps' => [
                ['order'=>1,'title'=>'권한 설계 원칙','description'=>'역할 기반 접근 제어 정책','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'역할 정의','description'=>'시스템 역할·책임','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'roles','label'=>'역할 (Role·설명·소속 그룹·기본 권한)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'화면×역할 매트릭스','description'=>'역할별 화면 접근 권한','inputType'=>'matrix','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'screenPerm','label'=>'권한 매트릭스 (화면·역할·접근·CRUD)','type'=>'table']],'tools'=>[['toolId'=>'MATRIX-RBAC']],'required'=>true],
                ['order'=>4,'title'=>'데이터 권한','description'=>'역할별 데이터 가시성·CRUD','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'dataPerm','label'=>'데이터 권한 (엔티티·역할·R·C·U·D·조건)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // S13 — SysArch 시스템 구성도 (이관·운영)
        // ─────────────────────────────────────────────────────────────────
        'S13_SysArch' => [
            'no'             => 'S13',
            'name'           => '시스템 구성도',
            'shortName'      => 'SysArch',
            'category'       => 'operations',
            'responsibility' => 'B',
            'timing'         => '설계·이관 단계',
            'depends_on'     => null,
            'depend_label'   => '공통 참조',
            'form_type'      => '서버/계층 구조도',
            'primaryTools'   => ['DIAGRAM-ARCH', 'DIAGRAM-NET', 'TABLE-DATA'],
            'steps' => [
                ['order'=>1,'title'=>'시스템 아키텍처 개요','description'=>'전체 구성 방향','inputType'=>'textarea','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'archDesc','label'=>'아키텍처 설명','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-ARCH'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'서버 구성','description'=>'서버·서비스 인벤토리','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'servers','label'=>'서버 (이름·역할·사양·OS·환경)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'네트워크 구성','description'=>'VPC·서브넷·방화벽','inputType'=>'diagram','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'network','label'=>'네트워크 구성 (영역·CIDR·게이트웨이·접근 규칙)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DIAGRAM-NET']],'required'=>true],
                ['order'=>4,'title'=>'보안 영역','description'=>'DMZ·내부망·관리망 분리','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'secZone','label'=>'보안 영역 (영역·포함 시스템·접근 통제)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'환경 분리','description'=>'Dev·Stage·Prod 구성 비교','inputType'=>'table','fields'=>[['key'=>'overview','label'=>'개요','type'=>'textarea'],['key'=>'envCompare','label'=>'환경 (구분·서버 수·스펙·접근 권한)','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

    ],

];
