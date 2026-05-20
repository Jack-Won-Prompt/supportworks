<?php

/*
 * 산출물(Deliverable) 20종 정의
 * 각 항목: id, no, name, shortName, category, responsibility, timing, primaryTools, steps[]
 * steps: order, title, description, inputType, fields[], tools[], required
 */

return [

    'categories' => [
        'design'       => ['label' => '설계',       'color' => '#7c3aed'],
        'security'     => ['label' => '보안',       'color' => '#dc2626'],
        'operations'   => ['label' => '이관·운영',  'color' => '#0891b2'],
        'test_deploy'  => ['label' => '테스트·배포', 'color' => '#059669'],
        'contract'     => ['label' => '계약·이관',  'color' => '#d97706'],
    ],

    'tools' => [
        // 다이어그램
        'DIAGRAM-FLOW'  => ['category' => 'diagram',       'name' => '프로세스/플로우 차트 빌더'],
        'DIAGRAM-ARCH'  => ['category' => 'diagram',       'name' => '시스템 아키텍처 다이어그램'],
        'DIAGRAM-DFD'   => ['category' => 'diagram',       'name' => '데이터 흐름도(DFD) 빌더'],
        'DIAGRAM-ERD'   => ['category' => 'diagram',       'name' => 'ER 다이어그램 빌더'],
        'DIAGRAM-NET'   => ['category' => 'diagram',       'name' => '네트워크 토폴로지 빌더'],
        'DIAGRAM-SEQ'   => ['category' => 'diagram',       'name' => '시퀀스 다이어그램 빌더'],
        'DIAGRAM-LIFE'  => ['category' => 'diagram',       'name' => '데이터 라이프사이클 다이어그램'],
        // 매트릭스
        'MATRIX-RBAC'   => ['category' => 'matrix',        'name' => '권한 매트릭스(RBAC) 빌더'],
        'MATRIX-RACI'   => ['category' => 'matrix',        'name' => 'RACI 매트릭스 빌더'],
        'MATRIX-RISK'   => ['category' => 'matrix',        'name' => '위험 평가 매트릭스'],
        'MATRIX-CONTACT'=> ['category' => 'matrix',        'name' => '연락처/에스컬레이션 매트릭스'],
        // 폼/계산기
        'FORM-CHECKLIST'=> ['category' => 'form',          'name' => '체크리스트 빌더'],
        'FORM-QA'       => ['category' => 'form',          'name' => '질의응답 폼(OneTrust 스타일)'],
        'FORM-SLA-CALC' => ['category' => 'form',          'name' => 'SLA 계산기'],
        'FORM-RPO-RTO'  => ['category' => 'form',          'name' => 'RPO/RTO 계산기'],
        // 테이블
        'TABLE-DATA'    => ['category' => 'table',         'name' => '동적 데이터 테이블 빌더'],
        'TABLE-CASE'    => ['category' => 'table',         'name' => '테스트 케이스 테이블 빌더'],
        'TABLE-CONFIG'  => ['category' => 'table',         'name' => '구성 정보 테이블(CMDB)'],
        'TABLE-SCHEDULE'=> ['category' => 'table',         'name' => '스케줄 작업 테이블'],
        // 시각화 / 기타
        'TIMELINE'      => ['category' => 'visualization', 'name' => '타임라인/간트 빌더'],
        'DASHBOARD'     => ['category' => 'visualization', 'name' => '지표 대시보드 미리보기'],
        'UPLOAD-DOC'    => ['category' => 'upload',        'name' => '문서 업로드(보안 백업·인증서)'],
        'UPLOAD-EVIDENCE'=> ['category' => 'upload',       'name' => '증빙 업로드'],
        'RUNBOOK'       => ['category' => 'runbook',       'name' => 'Runbook/SOP 에디터'],
        'MAPPING'       => ['category' => 'mapping',       'name' => '산출물 간 매핑 도구'],
        // 공통 시스템
        'AI-CHAT'       => ['category' => 'system',        'name' => '웍스 어시스턴트 사이드 패널'],
        'VERSION'       => ['category' => 'system',        'name' => '버전·변경 이력 비교'],
        'APPROVE'       => ['category' => 'system',        'name' => '전자 서명·승인 워크플로'],
        'EXPORT'        => ['category' => 'system',        'name' => '다중 포맷 출력'],
    ],

    'deliverables' => [

        // ─────────────────────────────────────────────────────────────────
        // 카테고리: 설계 (Party B 단독)
        // ─────────────────────────────────────────────────────────────────
        'USR' => [
            'no'             => 2,
            'name'           => '사용자 요구사항 명세서',
            'shortName'      => 'USR',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '계약 분석 및 체결',
            'primaryTools'   => ['DIAGRAM-FLOW', 'FORM-CHECKLIST', 'TABLE-DATA', 'AI-CHAT'],
            'steps'          => [
                ['order'=>1,'title'=>'비즈니스 배경','description'=>'프로젝트 배경, 추진 사유','inputType'=>'textarea','fields'=>[['key'=>'background','label'=>'배경 및 추진 사유','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'범위 및 목표','description'=>'프로젝트 범위, 측정 가능 목표','inputType'=>'form','fields'=>[['key'=>'scope','label'=>'범위','type'=>'textarea'],['key'=>'objectives','label'=>'목표','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>3,'title'=>'비즈니스 기능 요구사항','description'=>'기능 단위 요구 목록','inputType'=>'table','fields'=>[['key'=>'funcReqs','label'=>'기능 요구사항','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>4,'title'=>'비즈니스 이관 요구사항','description'=>'이관 절차, 가용성','inputType'=>'table','fields'=>[['key'=>'migrationReqs','label'=>'이관 요구사항','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'법규/컴플라이언스','description'=>'관련 법규·내부 정책','inputType'=>'form','fields'=>[['key'=>'compliance','label'=>'법규 및 정책','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST'],['toolId'=>'FORM-QA']],'required'=>false],
                ['order'=>6,'title'=>'IT 요구사항','description'=>'성능/보안/연계','inputType'=>'table','fields'=>[['key'=>'itReqs','label'=>'IT 요구사항','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>7,'title'=>'비즈니스 프로세스(As-is/To-be)','description'=>'프로세스 흐름','inputType'=>'diagram','fields'=>[['key'=>'processDesc','label'=>'프로세스 설명','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-FLOW']],'required'=>true],
                ['order'=>8,'title'=>'검토 및 확정','description'=>'이해관계자 검토, 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'FRS' => [
            'no'             => 3,
            'name'           => '기능 요구사항 명세서',
            'shortName'      => 'FRS',
            'category'       => 'design',
            'responsibility' => 'B',
            'timing'         => '개발/구성 작업 시작 전',
            'primaryTools'   => ['DIAGRAM-ARCH', 'DIAGRAM-DFD', 'DIAGRAM-SEQ', 'MAPPING', 'TABLE-DATA'],
            'steps'          => [
                ['order'=>1,'title'=>'FRS 개요','description'=>'기능 요구사항 명세서(FRS) 개요','inputType'=>'review','fields'=>[['key'=>'ursRef','label'=>'FRS 산출물 설명','type'=>'textarea']],'tools'=>[],'required'=>true],
                ['order'=>2,'title'=>'시스템 기능 아키텍처','description'=>'모듈/계층 구조','inputType'=>'diagram','fields'=>[['key'=>'archDescription','label'=>'아키텍처 설명','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-ARCH']],'required'=>true],
                ['order'=>3,'title'=>'데이터 흐름도','description'=>'입출력·처리 흐름','inputType'=>'diagram','fields'=>[['key'=>'dfdDescription','label'=>'DFD 설명','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-DFD']],'required'=>true],
                ['order'=>4,'title'=>'기능 명세','description'=>'화면·기능별 동작','inputType'=>'table','fields'=>[['key'=>'funcSpec','label'=>'기능 명세','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>5,'title'=>'인터페이스 정의','description'=>'API/연계 시스템','inputType'=>'table','fields'=>[['key'=>'interfaces','label'=>'인터페이스 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DIAGRAM-SEQ']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'검토 지적 사항','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // 카테고리: 보안 (A+B 공동)
        // ─────────────────────────────────────────────────────────────────
        'InfoSec' => [
            'no'             => 4,
            'name'           => '정보보안 평가서',
            'shortName'      => 'InfoSec',
            'category'       => 'security',
            'responsibility' => 'A+B',
            'timing'         => '계약 체결 전 OneTrust',
            'primaryTools'   => ['FORM-QA', 'MATRIX-RISK', 'FORM-CHECKLIST', 'UPLOAD-DOC'],
            'steps'          => [
                ['order'=>1,'title'=>'자산·데이터 분류','description'=>'자산·민감도 입력(A)','inputType'=>'table','fields'=>[['key'=>'assets','label'=>'자산 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>2,'title'=>'위험·취약점 식별','description'=>'기술 통제 입력(B)','inputType'=>'matrix','fields'=>[['key'=>'riskDesc','label'=>'위험 설명','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RISK'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>3,'title'=>'통제 항목 평가','description'=>'A+B 합의','inputType'=>'form','fields'=>[['key'=>'controls','label'=>'통제 평가 내용','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-QA'],['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>4,'title'=>'Transition 평가','description'=>'이관 리스크(A+B)','inputType'=>'form','fields'=>[['key'=>'transition','label'=>'이관 체크리스트','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST']],'required'=>false],
                ['order'=>5,'title'=>'잔여 위험 및 승인','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'residualRisk','label'=>'잔여 위험 내용','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RISK'],['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'RoPA' => [
            'no'             => 5,
            'name'           => '개인정보 처리활동 기록(RoPA)',
            'shortName'      => 'RoPA',
            'category'       => 'security',
            'responsibility' => 'A+B',
            'timing'         => '계약 체결 전 OneTrust',
            'primaryTools'   => ['FORM-QA', 'DIAGRAM-LIFE', 'TABLE-DATA'],
            'steps'          => [
                ['order'=>1,'title'=>'처리 목적·법적 근거','description'=>'A 입력','inputType'=>'form','fields'=>[['key'=>'purpose','label'=>'처리 목적 및 법적 근거','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-QA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'개인정보 항목','description'=>'A+B 수집 항목','inputType'=>'table','fields'=>[['key'=>'piiItems','label'=>'개인정보 항목','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'처리 흐름','description'=>'B 시스템 흐름','inputType'=>'diagram','fields'=>[['key'=>'flowDesc','label'=>'데이터 처리 흐름','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-LIFE'],['toolId'=>'DIAGRAM-DFD']],'required'=>true],
                ['order'=>4,'title'=>'보관·폐기','description'=>'A+B','inputType'=>'table','fields'=>[['key'=>'retention','label'=>'보관 및 폐기 정책','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>5,'title'=>'3자 제공·국외 이전','description'=>'A+B','inputType'=>'form','fields'=>[['key'=>'thirdParty','label'=>'제3자 제공 정보','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-QA'],['toolId'=>'MATRIX-RISK']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 등록','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Access' => [
            'no'             => 6,
            'name'           => '접근 통제',
            'shortName'      => 'Access',
            'category'       => 'security',
            'responsibility' => 'A+B',
            'timing'         => '개발/구성 작업 시작 전',
            'primaryTools'   => ['MATRIX-RBAC', 'DIAGRAM-FLOW', 'FORM-CHECKLIST'],
            'steps'          => [
                ['order'=>1,'title'=>'역할 정의','description'=>'A 비즈니스 역할','inputType'=>'table','fields'=>[['key'=>'roles','label'=>'역할 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'권한 매트릭스','description'=>'A+B 역할 × 기능','inputType'=>'matrix','fields'=>[['key'=>'rbacDesc','label'=>'권한 구성 설명','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RBAC']],'required'=>true],
                ['order'=>3,'title'=>'데이터 접근 분류','description'=>'A+B 역할 × 데이터','inputType'=>'matrix','fields'=>[['key'=>'dataAccess','label'=>'데이터 접근 분류','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RBAC']],'required'=>true],
                ['order'=>4,'title'=>'인증 워크플로','description'=>'B 승인 절차','inputType'=>'diagram','fields'=>[['key'=>'authFlow','label'=>'인증 흐름 설명','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-FLOW']],'required'=>false],
                ['order'=>5,'title'=>'SSO/계정 관리','description'=>'B 구현','inputType'=>'form','fields'=>[['key'=>'ssoPolicy','label'=>'SSO 및 계정 정책','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT'],['toolId'=>'FORM-CHECKLIST']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Audit' => [
            'no'             => 8,
            'name'           => '감사 추적 및 로그',
            'shortName'      => 'Audit',
            'category'       => 'security',
            'responsibility' => 'B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['TABLE-DATA', 'FORM-CHECKLIST', 'RUNBOOK'],
            'steps'          => [
                ['order'=>1,'title'=>'로그 대상 식별','description'=>'트랜잭션·데이터','inputType'=>'table','fields'=>[['key'=>'logTargets','label'=>'로그 대상 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>2,'title'=>'로그 항목 정의','description'=>'Who/What/When/Where/Why','inputType'=>'table','fields'=>[['key'=>'logSchema','label'=>'로그 스키마','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'보존 및 무결성','description'=>'보존 기간, 변조 방지','inputType'=>'form','fields'=>[['key'=>'retentionPolicy','label'=>'보존 및 무결성 정책','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>4,'title'=>'조회·감사 절차','description'=>'조회 권한·절차','inputType'=>'runbook','fields'=>[['key'=>'auditProcedure','label'=>'감사 절차','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'승인자 검토','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'PhySec' => [
            'no'             => 17,
            'name'           => '물리적 보안',
            'shortName'      => 'PhySec',
            'category'       => 'security',
            'responsibility' => 'B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['UPLOAD-DOC', 'FORM-CHECKLIST'],
            'steps'          => [
                ['order'=>1,'title'=>'데이터센터/시설 정보','description'=>'위치, 인증(ISO27001 등)','inputType'=>'upload','fields'=>[['key'=>'facilityInfo','label'=>'시설 정보','type'=>'textarea']],'tools'=>[['toolId'=>'UPLOAD-DOC']],'required'=>true],
                ['order'=>2,'title'=>'출입 통제','description'=>'출입 정책, CCTV, 경비','inputType'=>'form','fields'=>[['key'=>'accessControl','label'=>'출입 통제 내용','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>3,'title'=>'환경 통제','description'=>'전기·냉방·소방·재해','inputType'=>'form','fields'=>[['key'=>'envControl','label'=>'환경 통제 내용','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST'],['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>4,'title'=>'백업 첨부·요약','description'=>'보안 백업 업로드','inputType'=>'upload','fields'=>[['key'=>'backupDoc','label'=>'백업 문서','type'=>'upload']],'tools'=>[['toolId'=>'UPLOAD-DOC']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'검토 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // 카테고리: 이관·운영 (A+B 공동)
        // ─────────────────────────────────────────────────────────────────
        'Backup' => [
            'no'             => 11,
            'name'           => '백업 및 복구',
            'shortName'      => 'Backup',
            'category'       => 'operations',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['FORM-RPO-RTO', 'RUNBOOK', 'TIMELINE', 'DIAGRAM-NET'],
            'steps'          => [
                ['order'=>1,'title'=>'RPO/RTO 정의','description'=>'A 업무 요구','inputType'=>'form','fields'=>[['key'=>'rpoRto','label'=>'RPO/RTO 값','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-RPO-RTO']],'required'=>true],
                ['order'=>2,'title'=>'백업 대상·주기','description'=>'B 기술 정책','inputType'=>'table','fields'=>[['key'=>'backupPolicy','label'=>'백업 대상 및 주기','type'=>'table']],'tools'=>[['toolId'=>'FORM-RPO-RTO'],['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>3,'title'=>'백업 매체·암호화','description'=>'B 보안 요건','inputType'=>'form','fields'=>[['key'=>'mediaEncryption','label'=>'매체 및 암호화','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-NET'],['toolId'=>'FORM-CHECKLIST']],'required'=>false],
                ['order'=>4,'title'=>'복구 절차','description'=>'B Runbook','inputType'=>'runbook','fields'=>[['key'=>'recoveryProcedure','label'=>'복구 절차','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>true],
                ['order'=>5,'title'=>'복구 훈련 계획','description'=>'A+B 훈련','inputType'=>'form','fields'=>[['key'=>'drPlan','label'=>'DR 훈련 계획','type'=>'textarea']],'tools'=>[['toolId'=>'TIMELINE']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'BCP' => [
            'no'             => 16,
            'name'           => '업무 연속성 계획',
            'shortName'      => 'BCP',
            'category'       => 'operations',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['MATRIX-RISK', 'RUNBOOK', 'TIMELINE', 'DIAGRAM-FLOW'],
            'steps'          => [
                ['order'=>1,'title'=>'핵심 업무 식별','description'=>'A BIA','inputType'=>'table','fields'=>[['key'=>'criticalBiz','label'=>'핵심 업무 목록','type'=>'table']],'tools'=>[['toolId'=>'FORM-RPO-RTO'],['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>2,'title'=>'위험 시나리오','description'=>'A+B 재해·장애','inputType'=>'matrix','fields'=>[['key'=>'scenarios','label'=>'위험 시나리오','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RISK']],'required'=>true],
                ['order'=>3,'title'=>'대응 전략','description'=>'B 기술적·A 업무적','inputType'=>'diagram','fields'=>[['key'=>'strategy','label'=>'대응 전략','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-FLOW'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>4,'title'=>'BCP 절차','description'=>'A+B 절차 작성','inputType'=>'runbook','fields'=>[['key'=>'procedure','label'=>'BCP 절차','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>true],
                ['order'=>5,'title'=>'훈련 계획','description'=>'A+B DR 훈련 시나리오','inputType'=>'form','fields'=>[['key'=>'trainPlan','label'=>'훈련 계획','type'=>'textarea']],'tools'=>[['toolId'=>'TIMELINE']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Mon' => [
            'no'             => 20,
            'name'           => '플랫폼/시스템 모니터링',
            'shortName'      => 'Mon',
            'category'       => 'operations',
            'responsibility' => 'B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['DASHBOARD', 'TABLE-DATA', 'RUNBOOK'],
            'steps'          => [
                ['order'=>1,'title'=>'모니터링 대상','description'=>'시스템·서비스·지표','inputType'=>'table','fields'=>[['key'=>'targets','label'=>'모니터링 대상 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>2,'title'=>'임계값·알람','description'=>'경계/심각 임계값','inputType'=>'table','fields'=>[['key'=>'thresholds','label'=>'임계값 설정','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DASHBOARD']],'required'=>true],
                ['order'=>3,'title'=>'알람 전달 채널','description'=>'통보 대상·채널','inputType'=>'form','fields'=>[['key'=>'alertChannels','label'=>'알람 채널 정보','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING']],'required'=>false],
                ['order'=>4,'title'=>'대응 절차','description'=>'알람별 RUN BOOK','inputType'=>'runbook','fields'=>[['key'=>'runbook','label'=>'대응 절차','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>true],
                ['order'=>5,'title'=>'리포트','description'=>'일/주/월간 리포트','inputType'=>'form','fields'=>[['key'=>'reportTemplate','label'=>'리포트 템플릿','type'=>'textarea']],'tools'=>[['toolId'=>'DASHBOARD'],['toolId'=>'EXPORT']],'required'=>false],
            ],
        ],

        'Jobs' => [
            'no'             => 10,
            'name'           => '예약 작업(배치)',
            'shortName'      => 'Jobs',
            'category'       => 'operations',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['TABLE-SCHEDULE', 'RUNBOOK', 'MAPPING'],
            'steps'          => [
                ['order'=>1,'title'=>'작업 목록','description'=>'B 자동화 작업','inputType'=>'table','fields'=>[['key'=>'jobList','label'=>'스케줄 작업 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-SCHEDULE']],'required'=>true],
                ['order'=>2,'title'=>'스케줄·의존성','description'=>'B Cron/의존','inputType'=>'table','fields'=>[['key'=>'schedule','label'=>'스케줄 및 의존성','type'=>'table']],'tools'=>[['toolId'=>'TABLE-SCHEDULE'],['toolId'=>'TIMELINE']],'required'=>true],
                ['order'=>3,'title'=>'실패 처리','description'=>'A+B 재시도·알람','inputType'=>'form','fields'=>[['key'=>'failureHandling','label'=>'실패 처리 방침','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>false],
                ['order'=>4,'title'=>'모니터링 연동','description'=>'B 지표·알람','inputType'=>'form','fields'=>[['key'=>'monitoringLink','label'=>'모니터링 연동 내용','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Retention' => [
            'no'             => 12,
            'name'           => '데이터 보존 및 아카이빙',
            'shortName'      => 'Retention',
            'category'       => 'operations',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['DIAGRAM-LIFE', 'TABLE-DATA', 'RUNBOOK'],
            'steps'          => [
                ['order'=>1,'title'=>'데이터 분류','description'=>'A 업무 데이터','inputType'=>'table','fields'=>[['key'=>'dataClasses','label'=>'데이터 분류 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>2,'title'=>'보관 기간','description'=>'A+B 법규·정책','inputType'=>'table','fields'=>[['key'=>'retentionPeriods','label'=>'보관 기간','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'DIAGRAM-LIFE']],'required'=>true],
                ['order'=>3,'title'=>'아카이빙 정책','description'=>'B 저장소·접근성','inputType'=>'form','fields'=>[['key'=>'archivePolicy','label'=>'아카이빙 정책','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-LIFE'],['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>4,'title'=>'폐기 절차','description'=>'A+B SOP','inputType'=>'runbook','fields'=>[['key'=>'disposalProcedure','label'=>'폐기 절차','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Review' => [
            'no'             => 13,
            'name'           => '정기 검토 및 점검',
            'shortName'      => 'Review',
            'category'       => 'operations',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['FORM-CHECKLIST', 'MAPPING', 'TIMELINE'],
            'steps'          => [
                ['order'=>1,'title'=>'점검 항목 식별','description'=>'A+B 다른 산출물 자동 추출','inputType'=>'form','fields'=>[['key'=>'reviewItems','label'=>'점검 항목','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>2,'title'=>'주기 및 담당자','description'=>'A 담당 지정','inputType'=>'table','fields'=>[['key'=>'schedule','label'=>'주기 및 담당자','type'=>'table']],'tools'=>[['toolId'=>'TIMELINE'],['toolId'=>'MATRIX-RACI']],'required'=>true],
                ['order'=>3,'title'=>'점검 체크리스트','description'=>'A+B 자동 생성','inputType'=>'form','fields'=>[['key'=>'checklist','label'=>'체크리스트 내용','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>4,'title'=>'이상 발견 시 절차','description'=>'B Escalation 연결','inputType'=>'form','fields'=>[['key'=>'escalation','label'=>'에스컬레이션 절차','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING'],['toolId'=>'RUNBOOK']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Config' => [
            'no'             => 19,
            'name'           => '구성 정보 목록',
            'shortName'      => 'Config',
            'category'       => 'operations',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['TABLE-CONFIG', 'DIAGRAM-NET', 'MAPPING', 'VERSION'],
            'steps'          => [
                ['order'=>1,'title'=>'하드웨어 구성','description'=>'B CMDB 표준','inputType'=>'table','fields'=>[['key'=>'hardware','label'=>'하드웨어 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-CONFIG']],'required'=>true],
                ['order'=>2,'title'=>'소프트웨어 구성','description'=>'B 버전·라이선스','inputType'=>'table','fields'=>[['key'=>'software','label'=>'소프트웨어 목록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-CONFIG']],'required'=>true],
                ['order'=>3,'title'=>'네트워크 구성','description'=>'B 토폴로지','inputType'=>'diagram','fields'=>[['key'=>'netDesc','label'=>'네트워크 구성 설명','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-NET']],'required'=>false],
                ['order'=>4,'title'=>'환경별 차이','description'=>'B Dev/Stg/Prd','inputType'=>'table','fields'=>[['key'=>'envDiff','label'=>'환경별 차이','type'=>'table']],'tools'=>[['toolId'=>'TABLE-CONFIG']],'required'=>false],
                ['order'=>5,'title'=>'변경 이력 연동','description'=>'A+B Change 연동','inputType'=>'review','fields'=>[['key'=>'changeLink','label'=>'변경 이력 연동','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING'],['toolId'=>'VERSION']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // 카테고리: 테스트·배포 (Party B 단독)
        // ─────────────────────────────────────────────────────────────────
        'Test' => [
            'no'             => 7,
            'name'           => '테스트 스크립트 및 결과 보고서',
            'shortName'      => 'Test',
            'category'       => 'test_deploy',
            'responsibility' => 'B',
            'timing'         => 'UAT 시작 전',
            'primaryTools'   => ['TABLE-CASE', 'MAPPING', 'UPLOAD-EVIDENCE', 'DASHBOARD'],
            'steps'          => [
                ['order'=>1,'title'=>'테스트 시나리오 정의','description'=>'URS/FRS 기반','inputType'=>'form','fields'=>[['key'=>'scenarios','label'=>'테스트 시나리오','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>2,'title'=>'테스트 케이스 작성','description'=>'입력값/기대결과/우선순위','inputType'=>'table','fields'=>[['key'=>'testCases','label'=>'테스트 케이스','type'=>'table']],'tools'=>[['toolId'=>'TABLE-CASE']],'required'=>true],
                ['order'=>3,'title'=>'테스트 데이터 준비','description'=>'마스킹된 샘플 데이터','inputType'=>'table','fields'=>[['key'=>'testData','label'=>'테스트 데이터','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>4,'title'=>'실행 및 결과 기록','description'=>'Pass/Fail, 결함 ID','inputType'=>'table','fields'=>[['key'=>'execResults','label'=>'실행 결과','type'=>'table']],'tools'=>[['toolId'=>'TABLE-CASE'],['toolId'=>'UPLOAD-EVIDENCE']],'required'=>true],
                ['order'=>5,'title'=>'테스트 리포트','description'=>'요약·통계·결론','inputType'=>'form','fields'=>[['key'=>'report','label'=>'테스트 리포트 내용','type'=>'textarea']],'tools'=>[['toolId'=>'DASHBOARD'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Deploy' => [
            'no'             => 18,
            'name'           => '배포 문서',
            'shortName'      => 'Deploy',
            'category'       => 'test_deploy',
            'responsibility' => 'B',
            'timing'         => '이관 운영 환경 배포 전',
            'primaryTools'   => ['RUNBOOK', 'FORM-CHECKLIST', 'TABLE-DATA', 'UPLOAD-EVIDENCE'],
            'steps'          => [
                ['order'=>1,'title'=>'배포 범위','description'=>'대상 모듈, 환경','inputType'=>'form','fields'=>[['key'=>'scope','label'=>'배포 범위','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>2,'title'=>'사전 점검','description'=>'의존성, 사전 작업','inputType'=>'form','fields'=>[['key'=>'precheck','label'=>'사전 점검 내용','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>3,'title'=>'배포 절차','description'=>'단계별 명령/작업','inputType'=>'runbook','fields'=>[['key'=>'procedure','label'=>'배포 절차','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>true],
                ['order'=>4,'title'=>'롤백 계획','description'=>'롤백 트리거·절차','inputType'=>'form','fields'=>[['key'=>'rollback','label'=>'롤백 계획','type'=>'textarea']],'tools'=>[['toolId'=>'RUNBOOK']],'required'=>false],
                ['order'=>5,'title'=>'배포 기록','description'=>'실시일·담당자·결과','inputType'=>'table','fields'=>[['key'=>'deployLog','label'=>'배포 기록','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'UPLOAD-EVIDENCE']],'required'=>false],
                ['order'=>6,'title'=>'사후 검증','description'=>'모니터링·검증 결과','inputType'=>'review','fields'=>[['key'=>'postVerify','label'=>'사후 검증 결과','type'=>'textarea']],'tools'=>[['toolId'=>'DASHBOARD'],['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Train' => [
            'no'             => 14,
            'name'           => '교육 계획 및 자료',
            'shortName'      => 'Train',
            'category'       => 'test_deploy',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['TIMELINE', 'UPLOAD-DOC', 'TABLE-DATA'],
            'steps'          => [
                ['order'=>1,'title'=>'대상·역할별 커리큘럼','description'=>'A+B 역할별 항목 매핑','inputType'=>'table','fields'=>[['key'=>'curriculum','label'=>'교육 커리큘럼','type'=>'table']],'tools'=>[['toolId'=>'MATRIX-RACI'],['toolId'=>'TABLE-DATA']],'required'=>true],
                ['order'=>2,'title'=>'교육 자료','description'=>'B 매뉴얼·동영상','inputType'=>'upload','fields'=>[['key'=>'materials','label'=>'교육 자료','type'=>'upload']],'tools'=>[['toolId'=>'UPLOAD-DOC'],['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>3,'title'=>'일정·방식','description'=>'A 일정·장소','inputType'=>'form','fields'=>[['key'=>'trainingSchedule','label'=>'교육 일정 및 방식','type'=>'textarea']],'tools'=>[['toolId'=>'TIMELINE']],'required'=>true],
                ['order'=>4,'title'=>'평가 방법','description'=>'A+B 퀴즈·실습','inputType'=>'form','fields'=>[['key'=>'evaluation','label'=>'평가 방법','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-QA']],'required'=>false],
                ['order'=>5,'title'=>'이수 관리','description'=>'A 이수 현황 대시보드','inputType'=>'form','fields'=>[['key'=>'completion','label'=>'이수 관리 내용','type'=>'textarea']],'tools'=>[['toolId'=>'DASHBOARD']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        // ─────────────────────────────────────────────────────────────────
        // 카테고리: 계약·이관 (A+B 공동)
        // ─────────────────────────────────────────────────────────────────
        'SLA' => [
            'no'             => 15,
            'name'           => '서비스 수준 협약(SLA)',
            'shortName'      => 'SLA',
            'category'       => 'contract',
            'responsibility' => 'A+B',
            'timing'         => '계약 분석 및 체결',
            'primaryTools'   => ['FORM-SLA-CALC', 'TABLE-DATA', 'MAPPING'],
            'steps'          => [
                ['order'=>1,'title'=>'서비스 범위','description'=>'A+B 계약 범위 매핑','inputType'=>'form','fields'=>[['key'=>'serviceScope','label'=>'서비스 범위','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT'],['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>2,'title'=>'Severity 정의','description'=>'A+B P1~P4','inputType'=>'table','fields'=>[['key'=>'severity','label'=>'Severity 등급 정의','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>3,'title'=>'응답·해결 시간','description'=>'A+B Severity별','inputType'=>'form','fields'=>[['key'=>'slaValues','label'=>'SLA 시간 기준','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-SLA-CALC']],'required'=>true],
                ['order'=>4,'title'=>'측정 방법','description'=>'B 도구·수단','inputType'=>'form','fields'=>[['key'=>'measurement','label'=>'측정 방법','type'=>'textarea']],'tools'=>[['toolId'=>'FORM-SLA-CALC'],['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>5,'title'=>'패널티/포상','description'=>'A+B 계약 조항','inputType'=>'table','fields'=>[['key'=>'penalties','label'=>'패널티 및 포상','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA']],'required'=>false],
                ['order'=>6,'title'=>'보고 및 리뷰','description'=>'A+B 주기','inputType'=>'form','fields'=>[['key'=>'reporting','label'=>'보고 및 리뷰 주기','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT']],'required'=>false],
                ['order'=>7,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Escal' => [
            'no'             => 21,
            'name'           => '에스컬레이션 매트릭스',
            'shortName'      => 'Escal',
            'category'       => 'contract',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['MATRIX-CONTACT', 'MATRIX-RACI', 'MAPPING'],
            'steps'          => [
                ['order'=>1,'title'=>'서비스 티어 정의','description'=>'A+B L1/L2/L3','inputType'=>'table','fields'=>[['key'=>'tiers','label'=>'서비스 티어','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'AI-CHAT']],'required'=>true],
                ['order'=>2,'title'=>'티어별 책임 범위','description'=>'A+B RACI','inputType'=>'matrix','fields'=>[['key'=>'raciDesc','label'=>'RACI 구성','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RACI']],'required'=>true],
                ['order'=>3,'title'=>'연락처 매트릭스','description'=>'A+B 24/7','inputType'=>'matrix','fields'=>[['key'=>'contacts','label'=>'연락처 정보','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-CONTACT']],'required'=>true],
                ['order'=>4,'title'=>'에스컬레이션 트리거','description'=>'A+B 시간·심각도','inputType'=>'form','fields'=>[['key'=>'triggers','label'=>'에스컬레이션 트리거','type'=>'textarea']],'tools'=>[['toolId'=>'MAPPING']],'required'=>true],
                ['order'=>5,'title'=>'통보 채널','description'=>'B 전화·메일·메신저','inputType'=>'form','fields'=>[['key'=>'channels','label'=>'통보 채널 정보','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT'],['toolId'=>'FORM-CHECKLIST']],'required'=>false],
                ['order'=>6,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],

        'Change' => [
            'no'             => 9,
            'name'           => '변경 관리',
            'shortName'      => 'Change',
            'category'       => 'contract',
            'responsibility' => 'A+B',
            'timing'         => '프로젝트 인수 전',
            'primaryTools'   => ['DIAGRAM-FLOW', 'MATRIX-RACI', 'RUNBOOK'],
            'steps'          => [
                ['order'=>1,'title'=>'변경 분류 정의','description'=>'A+B Std/Normal/Emergency','inputType'=>'table','fields'=>[['key'=>'changeTypes','label'=>'변경 유형 정의','type'=>'table']],'tools'=>[['toolId'=>'TABLE-DATA'],['toolId'=>'FORM-CHECKLIST']],'required'=>true],
                ['order'=>2,'title'=>'분류별 절차','description'=>'B 절차 작성','inputType'=>'diagram','fields'=>[['key'=>'procedure','label'=>'변경 절차 흐름','type'=>'textarea']],'tools'=>[['toolId'=>'DIAGRAM-FLOW'],['toolId'=>'RUNBOOK']],'required'=>true],
                ['order'=>3,'title'=>'CAB 구성','description'=>'A 위원 지정','inputType'=>'matrix','fields'=>[['key'=>'cab','label'=>'CAB 구성원','type'=>'textarea']],'tools'=>[['toolId'=>'MATRIX-RACI']],'required'=>true],
                ['order'=>4,'title'=>'변경 양식','description'=>'A+B 양식 합의','inputType'=>'form','fields'=>[['key'=>'changeForm','label'=>'변경 양식 내용','type'=>'textarea']],'tools'=>[['toolId'=>'AI-CHAT'],['toolId'=>'EXPORT']],'required'=>false],
                ['order'=>5,'title'=>'검토 및 확정','description'=>'A 승인','inputType'=>'review','fields'=>[['key'=>'reviewNotes','label'=>'검토 의견','type'=>'textarea']],'tools'=>[['toolId'=>'APPROVE'],['toolId'=>'EXPORT']],'required'=>true],
            ],
        ],
    ],
];
