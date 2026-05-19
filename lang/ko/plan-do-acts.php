<?php

return [
    // ── 메뉴 · 페이지 ──
    'nav'                 => '실행 계획',
    'title'               => '실행 계획',
    'subtitle'            => '계획(Plan)·실행(Do)·조치(Act) 사이클로 의견을 관리합니다.',
    'global_subtitle'     => '참여 중인 모든 프로젝트의 실행 계획 항목입니다.',
    'all_projects'        => '전체 프로젝트',
    'new'                 => '새 실행 계획',

    // ── 등록/수정 팝업 ──
    'modal_create'        => '실행 계획 등록',
    'modal_edit'          => '실행 계획 수정',
    'field_project'       => '프로젝트',
    'project_none'        => '(프로젝트 미지정)',
    'field_title'         => '제목',
    'title_placeholder'   => '실행 계획 제목',
    'field_status'        => '상태',

    // ── 상태 ──
    'status_plan'         => '계획 (Plan)',
    'status_do'           => '실행 (Do)',
    'status_act'          => '조치 (Act)',
    'status_done'         => '완료',

    // ── 단계 입력 ──
    'phase_plan'          => 'Plan · 계획',
    'phase_do'            => 'Do · 실행',
    'phase_act'           => 'Act · 조치',
    'plan_placeholder'    => '무엇을, 왜, 어떻게 할지 계획을 작성하세요',
    'do_placeholder'      => '실제 수행한 내용을 작성하세요',
    'act_placeholder'     => '결과를 검토하고 개선·조치 사항을 작성하세요',

    // ── 원본 소스 ──
    'source_heading'      => '원본 의견 · 답변',
    'source_linked'       => '의견 연동',

    // ── 버튼 ──
    'btn_delete'          => '삭제',
    'btn_cancel'          => '취소',
    'btn_save'            => '저장',
    'saving'              => '저장 중…',
    'register_from_source'=> '실행 계획으로 등록',
    'view_edit'           => '실행 계획 보기/수정',

    // ── 알림 ──
    'confirm_delete'      => '이 실행 계획을 삭제할까요?',
    'title_required'      => '제목을 입력하세요.',
    'load_failed'         => '실행 계획을 불러오지 못했습니다.',
    'save_failed'         => '저장에 실패했습니다.',
    'delete_failed'       => '삭제에 실패했습니다.',
    'already_registered'  => '이미 실행 계획으로 등록되어 있습니다.',

    // ── 빈 목록 ──
    'empty'               => '등록된 실행 계획이 없습니다.',
    'empty_hint_project'  => '파일 의견 옆의 [실행 계획] 버튼이나 위의 버튼으로 등록하세요.',
    'empty_hint_global'   => '프로젝트 파일 의견·채팅 메시지 옆의 [실행 계획] 버튼으로 등록하세요.',

    // ── 원본 스냅샷 (컨트롤러) ──
    'src_comment'         => '[원본 의견] :author · :date',
    'src_message'         => '[원본 메시지] :author · :date',
    'src_reply'           => '↳ :author: :content',
    'ref_comment'         => '[참고 의견]',
    'ref_message'         => '[참고 메시지]',
    'chat_message'        => '채팅 메시지',
    'reviewer_anon'       => '외부 리뷰어',
    'user_unknown'        => '알 수 없음',

    // ── 컨트롤러 오류 ──
    'err_reply'           => '답글은 실행 계획으로 등록할 수 없습니다.',
    'err_project_mismatch'=> '의견과 프로젝트가 일치하지 않습니다.',
    'err_message_access'  => '이 메시지에 접근할 권한이 없습니다.',
    'err_no_delete_perm'  => '삭제 권한이 없습니다.',
];
