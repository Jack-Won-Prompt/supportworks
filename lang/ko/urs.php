<?php

return [

    // ── 브레드크럼 / 헤더
    'breadcrumb_projects'       => '프로젝트',
    'page_title'                => 'User Requirements Specification',

    // ── 상태
    'status_draft'              => '초안',
    'status_qa_in_progress'     => 'Q&A 진행중',
    'status_generating'         => '생성중',
    'status_completed'          => '완성',

    // ── index (작성 시작)
    'start_heading'             => 'URS 작성 시작',
    'start_intro'               => '기획서 내용을 기반으로 웍스와 문답을 통해<br>사용자 요구사항 명세서(URS)를 작성합니다.',
    'no_planning_doc_warning'   => '기획서가 없거나 내용이 비어 있습니다. 기획서를 먼저 작성하면 더 정확한 URS를 생성할 수 있습니다.',
    'planning_doc_based'        => '기획서 ":title"를 기반으로 URS를 생성합니다.',
    'start_button'              => 'URS 작성 시작하기 →',

    // ── 다운로드
    'download_word'             => 'Word',
    'download_pdf'              => 'PDF',
    'lang_korean'               => '한국어',
    'lang_english'              => 'English',
    'generate_translation'      => '(번역 생성)',
    'translating'               => '번역 중...',
    'translation_failed'        => '(실패 — 재시도)',

    // ── Q&A 섹션
    'qa_section_title'          => '웍스 연동 URS 작성',
    'qa_intro_with_doc'         => '기획서 ":title"를 분석하여<br>웍스가 URS 작성에 필요한 질문들을 생성합니다.<br><span style="font-size:12px;color:#9ca3af;">답변하지 않으면 웍스 추천 답변이 자동으로 사용됩니다.</span>',
    'qa_intro_without_doc'      => '기획서가 없어 일반적인 질문으로 URS를 작성합니다.<br><span style="font-size:12px;color:#9ca3af;">답변하지 않으면 웍스 추천 답변이 자동으로 사용됩니다.</span>',
    'start_qa_button'           => '웍스 질문 생성 시작',
    'qa_generating_questions'   => '웍스가 질문을 생성하는 중...',
    'qa_ai_suggestion_label'    => '웍스 추천 답변',
    'qa_my_answer_label'        => '내 답변 (비워두면 웍스 추천 답변 사용)',
    'qa_answer_placeholder'     => '직접 답변을 입력하거나, 웍스 추천 답변을 사용하려면 비워 두세요...',
    'qa_use_suggestion'         => '웍스 추천 답변 사용',
    'qa_next'                   => '다음 →',
    'qa_all_done'               => '모든 질문에 답변 완료!',
    'qa_all_done_hint'          => '이제 웍스가 URS 문서를 생성합니다.',
    'generate_urs_button'       => 'URS 문서 생성하기',
    'generating_urs'            => 'URS 문서를 생성하는 중... 잠시만 기다려 주세요.',
    'qa_done_badge'             => '완료',

    // ── URS 탭
    'tab_view'                  => '📄 URS 보기',
    'tab_edit'                  => '✏️ URS 수정 (Markdown)',
    'view_mode'                 => '보기 모드',
    'view_mode_full'            => '전체 보기',
    'view_mode_section'         => '단락별 보기',
    'edit_mode'                 => '편집 모드',
    'edit_mode_full'            => '전체 편집',
    'edit_mode_section'         => '단락별 편집',
    'empty_title'               => 'URS 문서가 아직 생성되지 않았습니다.',
    'empty_hint'                => '위의 웍스 연동 섹션에서 Q&A를 완료하면 자동으로 생성됩니다.',
    'edit_full_hint'            => 'Markdown으로 전체 문서를 수정합니다.',
    'editor_placeholder'        => 'URS Markdown 내용을 입력하세요...',
    'save_all'                  => '전체 저장',

    // ── JS 동적 텍스트
    'section_preamble'          => '제목 / 소개',
    'no_content'                => '내용이 없습니다.',
    'saved_check'               => '저장됨 ✓',
    'saved_all_check'           => '전체 저장됨 ✓',
    'generating_short'          => '생성 중...',
    'qa_question_prefix'        => 'Q',
    'error_prefix'              => '오류: ',
    'generate_error_prefix'     => '생성 오류: ',
    'default_error'             => '오류',
    'save_failed'               => '저장 실패',
    'generate_failed'           => '생성 실패',
    'question_gen_failed'       => '질문 생성 실패',
    'reset_failed'              => '초기화 실패',
    'confirm_reset'             => 'URS를 초기화하시겠습니까? Q&A 내용과 생성된 문서가 모두 삭제됩니다.',
];
