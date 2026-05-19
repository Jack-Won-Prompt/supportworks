<?php

return [
    // ── Menu / Page ──
    'nav'                 => 'Plan-Do-Act',
    'title'               => 'Plan-Do-Act',
    'subtitle'            => 'Manage feedback through the Plan-Do-Act cycle.',
    'global_subtitle'     => 'Plan-Do-Act items across all your projects.',
    'all_projects'        => 'All projects',
    'new'                 => 'New Plan-Do-Act',

    // ── Create/Edit popup ──
    'modal_create'        => 'New Plan-Do-Act',
    'modal_edit'          => 'Edit Plan-Do-Act',
    'field_project'       => 'Project',
    'project_none'        => '(No project)',
    'field_title'         => 'Title',
    'title_placeholder'   => 'Plan-Do-Act title',
    'field_status'        => 'Status',

    // ── Status ──
    'status_plan'         => 'Plan',
    'status_do'           => 'Do',
    'status_act'          => 'Act',
    'status_done'         => 'Done',

    // ── Phase inputs ──
    'phase_plan'          => 'Plan',
    'phase_do'            => 'Do',
    'phase_act'           => 'Act',
    'plan_placeholder'    => 'Describe what, why and how you plan to do it',
    'do_placeholder'      => 'Describe what was actually carried out',
    'act_placeholder'     => 'Review the results and describe improvements',

    // ── Source ──
    'source_heading'      => 'Original feedback & replies',
    'source_linked'       => 'Linked feedback',

    // ── Buttons ──
    'btn_delete'          => 'Delete',
    'btn_cancel'          => 'Cancel',
    'btn_save'            => 'Save',
    'saving'              => 'Saving…',
    'register_from_source'=> 'Register as Plan-Do-Act',
    'view_edit'           => 'View / edit Plan-Do-Act',

    // ── Alerts ──
    'confirm_delete'      => 'Delete this Plan-Do-Act?',
    'title_required'      => 'Please enter a title.',
    'load_failed'         => 'Failed to load the Plan-Do-Act.',
    'save_failed'         => 'Failed to save.',
    'delete_failed'       => 'Failed to delete.',
    'already_registered'  => 'Already registered as a Plan-Do-Act.',

    // ── Empty list ──
    'empty'               => 'No Plan-Do-Act items yet.',
    'empty_hint_project'  => 'Register one with the [Plan-Do-Act] button next to file feedback, or the button above.',
    'empty_hint_global'   => 'Register one with the [Plan-Do-Act] button next to project file feedback or chat messages.',

    // ── Source snapshot (controller) ──
    'src_comment'         => '[Original feedback] :author · :date',
    'src_message'         => '[Original message] :author · :date',
    'src_reply'           => '↳ :author: :content',
    'ref_comment'         => '[Reference feedback]',
    'ref_message'         => '[Reference message]',
    'chat_message'        => 'Chat message',
    'reviewer_anon'       => 'External reviewer',
    'user_unknown'        => 'Unknown',

    // ── Controller errors ──
    'err_reply'           => 'Replies cannot be registered as a Plan-Do-Act.',
    'err_project_mismatch'=> 'The feedback does not belong to the selected project.',
    'err_message_access'  => 'You do not have access to this message.',
    'err_no_delete_perm'  => 'You do not have permission to delete this.',
];
