<?php

return [

    // ── 공통/네비
    'issues'                 => '이슈',
    'breadcrumb_issue'       => '이슈',
    'csv_export'             => 'CSV 내보내기',
    'new_issue'              => '+ 새 이슈',

    // ── 필터 바
    'search_placeholder'     => '제목 검색...',
    'filter_all_status'      => '전체 상태',
    'filter_all_priority'    => '전체 우선순위',
    'filter_all_category'    => '전체 분류',
    'filter_all_assignee'    => '전체 담당자',

    // ── 뷰 토글
    'view_table'             => '표',
    'view_kanban'            => '칸반',

    // ── 칸반
    'kanban_empty'           => '없음',
    'requirement_linked'     => '요구사항 연결',

    // ── 테이블 헤더
    'col_category'           => '분류',
    'col_title'              => '제목',
    'col_priority'           => '우선순위',
    'col_status'             => '상태',
    'col_assignee'           => '담당자',
    'col_created_at'         => '등록일',
    'no_issues'              => '등록된 이슈가 없습니다.',

    // ── 새 이슈 모달
    'create_modal_title'     => '새 이슈 등록',
    'field_title'            => '제목 *',
    'field_description'      => '설명',
    'field_category'         => '분류 *',
    'field_priority'         => '우선순위 *',
    'field_severity'         => '심각도',
    'field_environment'      => '환경',
    'field_assignee'         => '담당자',
    'unassigned'             => '미배정',
    'field_tags'             => '태그 (쉼표 구분)',
    'tags_placeholder'       => '예: 긴급, 프론트엔드',
    'create_submit'          => '등록',
    'creating'               => '등록 중...',
    'create_failed'          => '등록 실패',

    // ── 상세 페이지
    'resolve_action'         => '해결 처리',
    'edit_issue_btn'         => '수정',
    'resolved_done'          => '✓ 해결 완료',

    // ── SLA
    'sla_info'               => 'SLA 정보',
    'sla_due'                => 'SLA 마감',
    'sla_breach'             => 'SLA 위반',
    'sla_breached'           => '위반',
    'sla_normal'             => '정상',
    'sla_breach_label'       => 'SLA 위반 표시',

    // ── 연결된 요구사항
    'linked_requirement'     => '연결된 요구사항',
    'link_add'               => '+ 연결',
    'select_requirement'     => '요구사항 선택...',
    'link_btn'               => '연결',
    'unlink_btn'             => '연결 해제',
    'no_linked_requirement'  => '연결된 요구사항이 없습니다.',
    'confirm_unlink'         => '연결을 해제할까요?',

    // ── Q&A 전환
    'converted_from_qa'      => 'Q&A에서 전환',

    // ── 댓글
    'comments_title'         => '댓글 (:count)',
    'comment_placeholder'    => '댓글 입력...',
    'comment_submit'         => '등록',
    'comment_me'             => '나',

    // ── 사이드바
    'status_change'          => '상태 변경',
    'issue_info'             => '이슈 정보',
    'info_assignee'          => '담당자',
    'info_reporter'          => '등록자',
    'info_created_at'        => '등록일',
    'info_resolved_at'       => '해결일',
    'assignee_change'        => '담당자 변경',

    // ── 구독
    'notification'           => '알림',
    'watching'               => '✓ 구독 중',
    'watch'                  => '구독하기',
    'watcher_count'          => ':count명 구독 중',

    // ── 변경 이력
    'change_history'         => '변경 이력',
    'history_changed'        => ':user이(가) :field을 변경: :old → :new',
    'history_empty_value'    => '(없음)',

    // ── 해결 처리 모달
    'resolve_modal_title'    => '해결 처리',
    'resolution_label'       => '해결 내용 *',
    'resolution_placeholder' => '해결 방법, 조치 내용을 입력하세요...',
    'final_status'           => '최종 상태',
    'resolve_status_resolved'=> '해결',
    'resolve_status_closed'  => '종결',
    'resolve_submit'         => '처리',
    'resolving'              => '처리 중...',
    'resolution_required'    => '해결 내용을 입력해주세요.',
    'error_occurred'         => '오류가 발생했습니다.',

    // ── 이슈 수정 모달
    'edit_modal_title'       => '이슈 수정',
    'edit_field_title'       => '제목',
    'edit_field_category'    => '분류',
    'edit_field_priority'    => '우선순위',
    'saving'                 => '저장 중...',
    'save_failed'            => '저장 실패',
];
