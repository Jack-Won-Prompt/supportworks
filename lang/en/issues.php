<?php

return [

    // ── Common / nav
    'issues'                 => 'Issues',
    'breadcrumb_issue'       => 'Issues',
    'csv_export'             => 'Export CSV',
    'new_issue'              => '+ New Issue',

    // ── Filter bar
    'search_placeholder'     => 'Search by title...',
    'filter_all_status'      => 'All Statuses',
    'filter_all_priority'    => 'All Priorities',
    'filter_all_category'    => 'All Categories',
    'filter_all_assignee'    => 'All Assignees',

    // ── View toggle
    'view_table'             => 'Table',
    'view_kanban'            => 'Kanban',

    // ── Kanban
    'kanban_empty'           => 'None',
    'requirement_linked'     => 'Requirement linked',

    // ── Table headers
    'col_category'           => 'Category',
    'col_title'              => 'Title',
    'col_priority'           => 'Priority',
    'col_status'             => 'Status',
    'col_assignee'           => 'Assignee',
    'col_created_at'         => 'Created',
    'no_issues'              => 'No issues registered.',

    // ── New issue modal
    'create_modal_title'     => 'Register New Issue',
    'field_title'            => 'Title *',
    'field_description'      => 'Description',
    'field_category'         => 'Category *',
    'field_priority'         => 'Priority *',
    'field_severity'         => 'Severity',
    'field_environment'      => 'Environment',
    'field_assignee'         => 'Assignee',
    'unassigned'             => 'Unassigned',
    'field_tags'             => 'Tags (comma-separated)',
    'tags_placeholder'       => 'e.g. Urgent, Frontend',
    'create_submit'          => 'Register',
    'creating'               => 'Registering...',
    'create_failed'          => 'Registration failed',

    // ── Detail page
    'resolve_action'         => 'Resolve',
    'edit_issue_btn'         => 'Edit',
    'resolved_done'          => '✓ Resolved',

    // ── SLA
    'sla_info'               => 'SLA Information',
    'sla_due'                => 'SLA Due',
    'sla_breach'             => 'SLA Breach',
    'sla_breached'           => 'Breached',
    'sla_normal'             => 'Normal',
    'sla_breach_label'       => 'Mark SLA breached',

    // ── Linked requirement
    'linked_requirement'     => 'Linked Requirement',
    'link_add'               => '+ Link',
    'select_requirement'     => 'Select a requirement...',
    'link_btn'               => 'Link',
    'unlink_btn'             => 'Unlink',
    'no_linked_requirement'  => 'No linked requirement.',
    'confirm_unlink'         => 'Unlink this requirement?',

    // ── Q&A conversion
    'converted_from_qa'      => 'Converted from Q&A',

    // ── Comments
    'comments_title'         => 'Comments (:count)',
    'comment_placeholder'    => 'Write a comment...',
    'comment_submit'         => 'Post',
    'comment_me'             => 'Me',

    // ── Sidebar
    'status_change'          => 'Change Status',
    'issue_info'             => 'Issue Information',
    'info_assignee'          => 'Assignee',
    'info_reporter'          => 'Reporter',
    'info_created_at'        => 'Created',
    'info_resolved_at'       => 'Resolved',
    'assignee_change'        => 'Change Assignee',

    // ── Watch
    'notification'           => 'Notifications',
    'watching'               => '✓ Watching',
    'watch'                  => 'Watch',
    'watcher_count'          => ':count watching',

    // ── Change history
    'change_history'         => 'Change History',
    'history_changed'        => ':user changed :field: :old → :new',
    'history_empty_value'    => '(none)',

    // ── Resolve modal
    'resolve_modal_title'    => 'Resolve Issue',
    'resolution_label'       => 'Resolution *',
    'resolution_placeholder' => 'Describe the resolution and actions taken...',
    'final_status'           => 'Final Status',
    'resolve_status_resolved'=> 'Resolved',
    'resolve_status_closed'  => 'Closed',
    'resolve_submit'         => 'Submit',
    'resolving'              => 'Processing...',
    'resolution_required'    => 'Please enter the resolution.',
    'error_occurred'         => 'An error occurred.',

    // ── Edit modal
    'edit_modal_title'       => 'Edit Issue',
    'edit_field_title'       => 'Title',
    'edit_field_category'    => 'Category',
    'edit_field_priority'    => 'Priority',
    'saving'                 => 'Saving...',
    'save_failed'            => 'Failed to save',
];
