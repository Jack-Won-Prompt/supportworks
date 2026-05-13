<?php

return [
    // ── SR / 유지보수 공통
    'sr_receipt'            => 'SR 접수',
    'sr_register'           => 'SR 접수 등록',
    'sr_detail'             => 'SR 접수 상세',
    'sr_edit'               => 'SR 접수 수정',
    'sr_files'              => 'SR 접수 파일',
    'sr_attachment_add'     => 'SR 첨부파일 추가',
    'sr_file_upload'        => 'SR 파일 업로드',
    'sr_empty'              => '등록된 SR 접수가 없습니다.',
    'sr_empty_gantt'        => '등록된 SR이 없습니다.',
    'sr_register_first'     => '첫 요청 등록하기 →',

    // ── 상태
    'status_pending'        => '접수',
    'status_in_progress'    => '처리중',
    'status_completed'      => '완료',
    'status_rejected'       => '반려',

    // ── 우선순위
    'priority_low'          => '낮음',
    'priority_normal'       => '보통',
    'priority_high'         => '높음',
    'priority_urgent'       => '긴급',

    // ── 테이블 헤더
    'col_title'             => '제목',
    'col_priority'          => '우선순위',
    'col_status'            => '상태',
    'col_requester'         => '요청자',
    'col_due_date'          => '처리 희망일',
    'col_scheduled_date'    => '처리 예정일',
    'col_replies'           => '답글',
    'col_created_at'        => '등록일',

    // ── 뷰 전환
    'view_list'             => '리스트',
    'view_gantt'            => '간트',
    'view_files'            => '파일',
    'priority_filter'       => '우선순위',

    // ── 간트 헤더
    'gantt_col_title'       => '제목',
    'gantt_col_status'      => '상태',
    'gantt_col_priority'    => '우선순위',
    'gantt_col_requester'   => '요청자',
    'gantt_col_start'       => '시작일',
    'gantt_col_end'         => '완료예정',

    // ── 간트 뷰 모드
    'gantt_day'             => '일',
    'gantt_week'            => '주',
    'gantt_month'           => '월',
    'gantt_year_suffix'     => '년',

    // ── 간트 월 이름
    'month_1'               => '1월',
    'month_2'               => '2월',
    'month_3'               => '3월',
    'month_4'               => '4월',
    'month_5'               => '5월',
    'month_6'               => '6월',
    'month_7'               => '7월',
    'month_8'               => '8월',
    'month_9'               => '9월',
    'month_10'              => '10월',
    'month_11'              => '11월',
    'month_12'              => '12월',

    // ── 날짜
    'date_requested'        => '요청일',
    'date_due'              => '처리 희망일',
    'date_scheduled'        => '처리 예정일',
    'date_scheduled_admin'  => '처리 예정일 (관리자)',
    'date_overdue'          => '기한 초과',
    'date_save'             => '일정 저장',
    'date_reset'            => '초기화',

    // ── 폼 필드
    'field_title'           => '제목',
    'field_priority'        => '우선순위',
    'field_content'         => '요청 내용',
    'field_attachment'      => '파일 첨부',
    'field_attachment_hint' => '(선택, 최대 50MB, 여러 파일 가능)',
    'field_file_category'   => '파일 카테고리',
    'field_description'     => '설명 (선택)',
    'field_display_name'    => '표시 이름',

    // ── 버튼
    'btn_register'          => '요청 등록',
    'btn_reply_submit'      => '답글 등록',
    'btn_file_add'          => '파일 추가',
    'btn_upload'            => '업로드',
    'btn_url_register'      => '등록',
    'btn_schedule_save'     => '일정 저장',
    'btn_attach_file'       => '파일',
    'btn_attach_url'        => 'URL',

    // ── 첨부파일 섹션
    'attachments'           => '첨부파일',
    'attachment_empty'      => '첨부파일이 없습니다.',
    'attachment_hint'       => '상단 \'파일 업로드\' 버튼 또는 각 SR 접수 상세에서 추가할 수 있습니다',
    'btn_url_open'          => 'URL 열기',
    'btn_preview'           => '미리보기',
    'btn_download'          => '다운로드',
    'btn_sharing'           => '공유중',
    'btn_share'             => '공유',
    'upload_click_or_drag'  => '클릭 또는 드래그하여 파일 첨부',
    'upload_max_size'       => '최대 50MB',
    'upload_click_drag_short'=> '클릭하거나 파일을 드래그하세요',
    'upload_click_drag_idx' => '클릭 또는 드래그하여 업로드',
    'upload_max_hint'       => '최대 50MB, 여러 파일 가능',

    // ── 파일 모달 탭
    'tab_file_upload'       => '파일 업로드',
    'tab_url_register'      => 'URL 등록',

    // ── 답글
    'replies'               => '답글 이력',
    'reply_count'           => ':count건',
    'reply_empty'           => '아직 답글이 없습니다.',
    'reply_write_admin'     => '처리 답글 작성',
    'reply_write_user'      => '추가 문의 작성',
    'reply_placeholder_admin' => '처리 내용, 확인 결과, 조치 사항 등을 작성하세요.',
    'reply_placeholder_user'  => '추가 문의 내용을 작성하세요.',
    'status_completed_msg'  => '처리 완료된 요청입니다.',
    'status_rejected_msg'   => '반려된 요청입니다.',

    // ── 관리자
    'admin_label'           => '관리자',
    'admin_schedule'        => '처리 예정일 설정',

    // ── 확인 메시지
    'confirm_delete_sr'     => 'SR 접수를 삭제하시겠습니까?',
    'confirm_delete_reply'  => '답글을 삭제하시겠습니까?',
    'confirm_delete_file'   => '첨부파일을 삭제하시겠습니까?',

    // ── 인덱스 업로드 모달
    'idx_sr_optional'       => '선택 안 해도 업로드 가능',
    'idx_sr_unlinked'       => '— SR 항목 미연결 —',

    // ── 요청 플레이스홀더 (짧은 버전)
    'request_placeholder_short' => 'SR 접수 내용을 작성하세요. 이미지는 붙여넣기(Ctrl+V)도 가능합니다.',

    // ── 파일 카테고리
    'cat_all'               => '전체',
    'cat_uncategorized'     => '미분류',
    'cat_add'               => '카테고리 추가',
    'cat_name_placeholder'  => '카테고리 이름',
    'cat_delete_confirm'    => '카테고리를 삭제하시겠습니까?\n해당 카테고리의 파일은 미분류로 변경됩니다.',
    'file_empty'            => '등록된 파일이 없습니다.',
    'file_comments'         => '의견 :count',

    // ── 파일 공유 팝업
    'share_title'           => '외부 공유 링크',
    'share_hint'            => '이 링크로 로그인 없이 파일을 열람하고 의견을 남길 수 있습니다.',
    'share_disable'         => '링크 비활성화',

    // ── 상세 모달
    'detail_title'          => 'SR 접수 상세',

    // ── Word 다운로드 / 회의록
    'word_download'         => 'Word 다운로드',

    // ── 회의록
    'meeting_minutes'       => '회의록',
    'meeting_new'           => '새 회의록',
    'meeting_new_write'     => '+ 새 회의록 작성',
    'meeting_create'        => '새 회의록',
    'meeting_edit'          => '회의록 수정',
    'meeting_empty'         => '회의록이 없습니다',
    'meeting_empty_hint'    => '첫 회의록을 작성해보세요',

    // ── 회의록 통계
    'stat_total'            => '전체',
    'stat_this_month'       => '이번 달',
    'stat_general'          => '일반 회의',
    'stat_project'          => '프로젝트 회의',

    // ── 회의록 필터
    'filter_all_types'      => '전체 유형',
    'filter_general'        => '일반 회의',
    'filter_project'        => '프로젝트 회의',
    'filter_all_projects'   => '전체 프로젝트',
    'search_title'          => '제목 검색',

    // ── 회의록 목록
    'attendees_count'       => '참석자 :count명',

    // ── 회의록 상세
    'meeting_date'          => '회의 일시',
    'location'              => '장소',
    'project_code'          => '프로젝트 코드',
    'weekly_dept'           => '주관 부서',
    'attendees'             => '참석자',
    'agenda'                => '주요 안건',
    'discussion'            => '논의 내용',
    'decisions'             => '결정 사항',
    'ai_summary'            => '웍스 요약',
    'memo_section'          => '메모 (:count)',
    'memo_placeholder'      => '회의 중 메모를 자유롭게 입력하세요...',
    'memo_add'              => '메모 추가',
    'memo_empty'            => '메모가 없습니다',
    'confirm_delete_memo'   => '메모를 삭제하시겠습니까?',
    'confirm_delete_meeting'=> '회의록을 삭제하시겠습니까?',
    'confirm_delete_action' => '삭제하시겠습니까?',
    'overdue'               => '기한초과',

    // ── Action Items
    'action_items'          => 'Action Items',
    'action_item_add'       => 'Action Item 추가',
    'action_item_empty'     => 'Action Item이 없습니다',
    'action_status_pending' => '대기',
    'action_status_in_progress' => '진행중',
    'action_status_completed'   => '완료',
    'action_priority_medium'    => '보통',
    'action_priority_high'      => '높음',
    'action_priority_low'       => '낮음',
    'field_task_name'       => '작업명 *',
    'field_task_desc'       => '상세 내용',
    'field_owner'           => '담당자 선택',
    'field_owner_manual'    => '또는 이름 직접 입력',
    'field_related_memo'    => '관련 메모 연결 (선택)',

    // ── 회의록 폼
    'form_basic_info'       => '기본 정보',
    'form_meeting_title'    => '회의 제목',
    'form_meeting_title_ph' => '회의 제목을 입력하세요',
    'form_meeting_type'     => '회의 유형',
    'form_meeting_date'     => '회의 일시',
    'form_project'          => '프로젝트',
    'form_project_select'   => '프로젝트 선택',
    'form_project_code'     => '프로젝트 코드',
    'form_project_code_ph'  => '예: PRJ-2026-001',
    'form_weekly_dept'      => '주관 부서',
    'form_weekly_dept_ph'   => '예: 개발팀',
    'form_location'         => '장소',
    'form_location_ph'      => '예: 3층 회의실 A',
    'form_attendees'        => '참석자',
    'form_attendee_direct'  => '직접 입력',
    'form_attendee_name_ph' => '이름 직접 입력',
    'form_add_attendee'     => '+ 참석자 추가',
    'form_meeting_content'  => '회의 내용',
    'form_agenda_ph'        => '회의 주요 안건을 입력하세요',
    'form_discussion_ph'    => '논의된 내용을 상세히 기록하세요',
    'form_decisions_ph'     => '회의에서 확정된 결정 사항을 입력하세요',

    // ── 프로젝트 네비게이션
    'nav_overview'          => '개요',
    'nav_schedule'          => '일정',
    'nav_qa'                => 'Q&A',
    'nav_files'             => '파일',
    'nav_sr_receipt'        => 'SR 접수',

    // ── 유지보수 패널 (웍스)
    'maintenance_panel'     => '화면 유지보수',
    'tab_edit'              => '수정',
    'tab_versions'          => '버전',
    'step_request'          => '요청',
    'step_ai_analysis'      => '웍스 분석',
    'step_review_apply'     => '리뷰·적용',
    'register_screen'       => '이 화면을 유지보수에 등록합니다',
    'screen_name'           => '화면명',
    'register_btn'          => '등록하기',
    'request_label'         => '수정 요청',
    'request_placeholder'   => "어떻게 수정할까요?\n\n예) 헤더 배경색을 보라색 계열로 변경하고 저장 버튼에 아이콘을 추가해주세요.",
    'ai_gen_prompt'         => '웍스 프롬프트 생성',
    'ai_gen_patch'          => '수정안 생성 →',
    'go_back'               => '← 다시',
    'original_request'      => '원본 요청',
    'review_title'          => '수정안 리뷰',
    'back_to_prompt'        => '프롬프트로 돌아가기',
    'preview_btn'           => '미리보기',
    'approve_apply'         => '승인·적용',
    'change_summary'        => '변경 요약',
    'changed_files'         => '변경 파일',
    'version_list'          => '버전 목록',
    'rollback'              => '이 버전으로 롤백',
    'current_code'          => '현재 코드',
    'view_diff'             => 'Diff',
    'view_before'           => '수정 전',
    'view_after'            => '수정 후',
    'no_versions'           => '저장된 버전이 없습니다',

    // ── 인덱스 JS 메시지
    'js_schedule_fail'      => '일정 변경에 실패했습니다.',
    'js_reply_fail'         => '답글 등록에 실패했습니다.',
    'js_network_error'      => '네트워크 오류가 발생했습니다.',
    'js_save_fail'          => '저장에 실패했습니다.',
    'js_delete_fail'        => '삭제에 실패했습니다.',
    'js_upload_fail'        => '업로드에 실패했습니다.',
    'js_load_fail2'         => '불러오기에 실패했습니다.',
    'js_uploading'          => '업로드 중…',
    'js_saving'             => '저장 중…',
    'js_registering_reply'  => '등록 중…',
    'js_select_file'        => '파일을 선택하세요.',
    'js_enter_content'      => '요청 내용을 입력해 주세요.',
    'js_enter_url_name'     => '표시 이름과 URL을 모두 입력하세요.',
    'js_enter_url'          => 'URL을 입력하세요.',
    'js_enter_display_name' => '표시 이름을 입력하세요.',
    'js_share_disable_confirm' => '공유 링크를 비활성화하시겠습니까?\n기존 링크로는 더 이상 접근할 수 없습니다.',
    'js_copy_copied'        => '복사됨!',
    'js_sharing'            => '공유중',
    'js_sr_attach_add'      => 'SR 첨부파일 추가',

    // ── 패널 JS 메시지
    'js_registering'        => '등록 중…',
    'js_reg_fail'           => '등록 실패',
    'js_prompt_generating'  => '프롬프트 생성 중…',
    'js_prompt_analyzing'   => '웍스가 구조화 프롬프트를 생성하고 있습니다…',
    'js_prompt_fail'        => '프롬프트 생성 실패',
    'js_patch_generating'   => '수정안 분석 중…',
    'js_patch_analyzing'    => '웍스가 소스코드와 UI 구조를 분석하고 수정안을 생성하고 있습니다…',
    'js_patch_fail'         => '수정안 생성 실패',
    'js_preview_preparing'  => '준비 중…',
    'js_preview_generating' => '미리보기를 생성하고 있습니다…',
    'js_preview_fail'       => '미리보기 생성 실패',
    'js_preview_blocked'    => '미리보기 링크 (팝업 차단됨 — 클릭하여 열기)',
    'js_preview_opened'     => '새 탭에서 미리보기가 열렸습니다',
    'js_applying'           => '적용 중…',
    'js_apply_files'        => '파일에 적용하고 있습니다…',
    'js_apply_fail'         => '적용 실패',
    'js_loading'            => '로딩 중…',
    'js_load_fail'          => '로딩 실패',
    'js_no_file'            => '(파일 없음)',
    'js_rolling_back'       => '롤백 중…',
    'js_rollback_done'      => '롤백 완료! v:ver으로 기록되었습니다.',
    'js_rollback_fail'      => '롤백 실패: ',
    'js_confirm_apply'      => '수정안을 실제 파일에 적용하시겠습니까?',
    'js_confirm_rollback'   => 'v:ver 버전으로 롤백하시겠습니까?',
    'js_enter_request'      => '수정 요청을 입력해주세요.',
    'js_system'             => '시스템',
    'refined_prompt_hint'   => '핵심 지시',
];
