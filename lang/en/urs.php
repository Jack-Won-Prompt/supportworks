<?php

return [

    // ── Breadcrumb / Header
    'breadcrumb_projects'       => 'Projects',
    'page_title'                => 'User Requirements Specification',

    // ── Status
    'status_draft'              => 'Draft',
    'status_qa_in_progress'     => 'Q&A In Progress',
    'status_generating'         => 'Generating',
    'status_completed'          => 'Completed',

    // ── index (start writing)
    'start_heading'             => 'Start Writing the URS',
    'start_intro'               => 'Based on the planning document, you will create a<br>User Requirements Specification (URS) through a Q&A with Works.',
    'no_planning_doc_warning'   => 'There is no planning document or its content is empty. Writing the planning document first will produce a more accurate URS.',
    'planning_doc_based'        => 'The URS will be generated based on the planning document ":title".',
    'start_button'              => 'Start Writing the URS →',

    // ── Download
    'download_word'             => 'Word',
    'download_pdf'              => 'PDF',
    'lang_korean'               => 'Korean',
    'lang_english'              => 'English',
    'generate_translation'      => '(Generate translation)',
    'translating'               => 'Translating...',
    'translation_failed'        => '(Failed — retry)',

    // ── Q&A section
    'qa_section_title'          => 'Works-Assisted URS Writing',
    'qa_intro_with_doc'         => 'Works will analyze the planning document ":title"<br>and generate the questions needed to write the URS.<br><span style="font-size:12px;color:#9ca3af;">If you leave an answer blank, the Works suggested answer is used automatically.</span>',
    'qa_intro_without_doc'      => 'Since there is no planning document, the URS will be written using general questions.<br><span style="font-size:12px;color:#9ca3af;">If you leave an answer blank, the Works suggested answer is used automatically.</span>',
    'start_qa_button'           => 'Start Generating Works Questions',
    'qa_generating_questions'   => 'Works is generating questions...',
    'qa_ai_suggestion_label'    => 'Works Suggested Answer',
    'qa_my_answer_label'        => 'My Answer (leave blank to use the Works suggested answer)',
    'qa_answer_placeholder'     => 'Type your own answer, or leave this blank to use the Works suggested answer...',
    'qa_use_suggestion'         => 'Use Works Suggested Answer',
    'qa_next'                   => 'Next →',
    'qa_all_done'               => 'All questions answered!',
    'qa_all_done_hint'          => 'Works will now generate the URS document.',
    'generate_urs_button'       => 'Generate URS Document',
    'generating_urs'            => 'Generating the URS document... Please wait a moment.',
    'qa_done_badge'             => 'Done',

    // ── URS tabs
    'tab_view'                  => '📄 View URS',
    'tab_edit'                  => '✏️ Edit URS (Markdown)',
    'view_mode'                 => 'View Mode',
    'view_mode_full'            => 'Full View',
    'view_mode_section'         => 'Section View',
    'edit_mode'                 => 'Edit Mode',
    'edit_mode_full'            => 'Full Edit',
    'edit_mode_section'         => 'Section Edit',
    'empty_title'               => 'The URS document has not been generated yet.',
    'empty_hint'                => 'It will be generated automatically once you complete the Q&A in the Works integration section above.',
    'edit_full_hint'            => 'Edit the entire document in Markdown.',
    'editor_placeholder'        => 'Enter the URS Markdown content...',
    'save_all'                  => 'Save All',

    // ── JS dynamic text
    'section_preamble'          => 'Title / Introduction',
    'no_content'                => 'There is no content.',
    'saved_check'               => 'Saved ✓',
    'saved_all_check'           => 'All Saved ✓',
    'generating_short'          => 'Generating...',
    'qa_question_prefix'        => 'Q',
    'error_prefix'              => 'Error: ',
    'generate_error_prefix'     => 'Generation error: ',
    'default_error'             => 'Error',
    'save_failed'               => 'Failed to save',
    'generate_failed'           => 'Failed to generate',
    'question_gen_failed'       => 'Failed to generate questions',
    'reset_failed'              => 'Failed to reset',
    'confirm_reset'             => 'Reset the URS? All Q&A content and the generated document will be deleted.',
];
