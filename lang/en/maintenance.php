<?php

return [
    // ── SR / Maintenance common
    'sr_receipt'            => 'SR',
    'sr_register'           => 'New SR',
    'sr_detail'             => 'SR Detail',
    'sr_edit'               => 'Edit SR',
    'sr_files'              => 'SR Files',
    'sr_attachment_add'     => 'Add SR Attachment',
    'sr_file_upload'        => 'SR File Upload',
    'sr_empty'              => 'No SR requests registered.',
    'sr_empty_gantt'        => 'No SR items found.',
    'sr_register_first'     => 'Create first request →',

    // ── Status
    'status_pending'        => 'Received',
    'status_in_progress'    => 'In Progress',
    'status_completed'      => 'Completed',
    'status_rejected'       => 'Rejected',

    // ── Priority
    'priority_low'          => 'Low',
    'priority_normal'       => 'Normal',
    'priority_high'         => 'High',
    'priority_urgent'       => 'Urgent',

    // ── Table headers
    'col_title'             => 'Title',
    'col_priority'          => 'Priority',
    'col_status'            => 'Status',
    'col_requester'         => 'Requester',
    'col_due_date'          => 'Desired Date',
    'col_scheduled_date'    => 'Scheduled Date',
    'col_replies'           => 'Replies',
    'col_created_at'        => 'Registered',

    // ── View toggle
    'view_list'             => 'List',
    'view_gantt'            => 'Gantt',
    'view_files'            => 'Files',
    'priority_filter'       => 'Priority',

    // ── Gantt headers
    'gantt_col_title'       => 'Title',
    'gantt_col_status'      => 'Status',
    'gantt_col_priority'    => 'Priority',
    'gantt_col_requester'   => 'Requester',
    'gantt_col_start'       => 'Start',
    'gantt_col_end'         => 'Due',

    // ── Gantt view modes
    'gantt_day'             => 'D',
    'gantt_week'            => 'W',
    'gantt_month'           => 'M',
    'gantt_year_suffix'     => '',

    // ── Gantt month names
    'month_1'               => 'Jan',
    'month_2'               => 'Feb',
    'month_3'               => 'Mar',
    'month_4'               => 'Apr',
    'month_5'               => 'May',
    'month_6'               => 'Jun',
    'month_7'               => 'Jul',
    'month_8'               => 'Aug',
    'month_9'               => 'Sep',
    'month_10'              => 'Oct',
    'month_11'              => 'Nov',
    'month_12'              => 'Dec',

    // ── Dates
    'date_requested'        => 'Requested',
    'date_due'              => 'Desired Date',
    'date_scheduled'        => 'Scheduled Date',
    'date_scheduled_admin'  => 'Scheduled Date (Admin)',
    'date_overdue'          => 'Overdue',
    'date_save'             => 'Save Schedule',
    'date_reset'            => 'Reset',

    // ── Form fields
    'field_title'           => 'Title',
    'field_priority'        => 'Priority',
    'field_content'         => 'Request Details',
    'field_attachment'      => 'Attachments',
    'field_attachment_hint' => '(Optional, max 50MB, multiple files allowed)',
    'field_file_category'   => 'File Category',
    'field_description'     => 'Description (optional)',
    'field_display_name'    => 'Display Name',

    // ── Buttons
    'btn_register'          => 'Submit Request',
    'btn_reply_submit'      => 'Post Reply',
    'btn_file_add'          => 'Add File',
    'btn_upload'            => 'Upload',
    'btn_url_register'      => 'Register',
    'btn_schedule_save'     => 'Save Schedule',
    'btn_attach_file'       => 'File',
    'btn_attach_url'        => 'URL',

    // ── Attachments section
    'attachments'           => 'Attachments',
    'attachment_empty'      => 'No attachments.',
    'attachment_hint'       => 'Use the "File Upload" button above or add from each SR detail page.',
    'btn_url_open'          => 'Open URL',
    'btn_preview'           => 'Preview',
    'btn_download'          => 'Download',
    'btn_sharing'           => 'Shared',
    'btn_share'             => 'Share',
    'upload_click_or_drag'  => 'Click or drag to attach file',
    'upload_max_size'       => 'Max 50MB',
    'upload_click_drag_short'=> 'Click or drag file here',
    'upload_click_drag_idx' => 'Click or drag to upload',
    'upload_max_hint'       => 'Max 50MB, multiple files allowed',

    // ── File modal tabs
    'tab_file_upload'       => 'File Upload',
    'tab_url_register'      => 'Register URL',

    // ── Replies
    'replies'               => 'Reply History',
    'reply_count'           => ':count',
    'reply_empty'           => 'No replies yet.',
    'reply_write_admin'     => 'Write Response',
    'reply_write_user'      => 'Write Follow-up',
    'reply_placeholder_admin' => 'Describe the resolution, findings, or actions taken.',
    'reply_placeholder_user'  => 'Write your follow-up question or comment.',
    'status_completed_msg'  => 'This request has been completed.',
    'status_rejected_msg'   => 'This request has been rejected.',

    // ── Admin
    'admin_label'           => 'Admin',
    'admin_schedule'        => 'Set Scheduled Date',

    // ── Confirm messages
    'confirm_delete_sr'     => 'Are you sure you want to delete this SR?',
    'confirm_delete_reply'  => 'Are you sure you want to delete this reply?',
    'confirm_delete_file'   => 'Are you sure you want to delete this attachment?',

    // ── Index upload modal
    'idx_sr_optional'       => 'upload without selecting one',
    'idx_sr_unlinked'       => '— Not linked to an SR —',

    // ── Request placeholder (short version)
    'request_placeholder_short' => 'Describe your request. You can also paste images (Ctrl+V).',

    // ── File categories
    'cat_all'               => 'All',
    'cat_uncategorized'     => 'Uncategorized',
    'cat_add'               => 'Add Category',
    'cat_name_placeholder'  => 'Category name',
    'cat_delete_confirm'    => 'Are you sure you want to delete this category?\nFiles in this category will be moved to Uncategorized.',
    'file_empty'            => 'No files registered.',
    'file_comments'         => ':count comment(s)',

    // ── File share popup
    'share_title'           => 'External Share Link',
    'share_hint'            => 'Anyone with this link can view the file and leave comments without logging in.',
    'share_disable'         => 'Disable Link',

    // ── Detail modal
    'detail_title'          => 'SR Detail',

    // ── Word download / meeting minutes
    'word_download'         => 'Download Word',

    // ── Meeting minutes
    'meeting_minutes'       => 'Meeting Minutes',
    'meeting_new'           => 'New Minutes',
    'meeting_new_write'     => '+ Write New Minutes',
    'meeting_create'        => 'New Meeting Minutes',
    'meeting_edit'          => 'Edit Meeting Minutes',
    'meeting_empty'         => 'No meeting minutes found',
    'meeting_empty_hint'    => 'Start by creating your first meeting minutes',
    'meeting_schedule'      => 'Schedule a Meeting',
    'minute_write'          => 'Write Minutes',
    'minute_write_tooltip'  => 'Write meeting minutes',

    // ── Meeting status filter
    'filter_all_statuses'   => 'All Statuses',
    'status_scheduled'      => 'Scheduled',
    'status_completed_meeting' => 'Completed',
    'scheduled_meeting'     => 'Scheduled Meetings',

    // ── Meeting list count
    'action_count'          => ':count action item(s)',

    // ── Meeting modal / form additions
    'form_project_none'     => 'No project (general meeting)',
    'attendee_search_ph'    => 'Search by name or email, or type and press Enter',
    'attendee_external_hint'=> 'For external attendees who are not team members, type the name and press Enter to add.',
    'attendee_remove'       => 'Remove',
    'attendee_manual_add'   => 'Add ":name" directly',
    'attendee_manual_label' => 'Entered manually',
    'owner_unassigned'      => 'No assignee',
    'action_item_add_btn'   => '+ Add Action Item',
    'action_item_task_ph'   => 'Task (Action Item)',

    // ── Meeting recordings
    'recordings_section'    => 'Meeting Recordings (:count)',
    'recording_default'     => 'Meeting Recording',
    'recording_download'    => 'Download File',
    'recording_transcript'  => 'View Transcript',

    // ── Meeting minutes JS messages
    'js_delete_error'       => 'An error occurred while deleting.',

    // ── Meeting minutes stats
    'stat_total'            => 'Total',
    'stat_this_month'       => 'This Month',
    'stat_general'          => 'General',
    'stat_project'          => 'Project',

    // ── Meeting minutes filter
    'filter_all_types'      => 'All Types',
    'filter_general'        => 'General Meeting',
    'filter_project'        => 'Project Meeting',
    'filter_all_projects'   => 'All Projects',
    'search_title'          => 'Search by title',

    // ── Meeting minutes list
    'attendees_count'       => ':count attendee(s)',

    // ── Meeting minutes detail
    'meeting_date'          => 'Meeting Date',
    'location'              => 'Location',
    'project_code'          => 'Project Code',
    'weekly_dept'           => 'Department',
    'attendees'             => 'Attendees',
    'agenda'                => 'Agenda',
    'discussion'            => 'Discussion',
    'decisions'             => 'Decisions',
    'ai_summary'            => '웍스 Summary',
    'memo_section'          => 'Memos (:count)',
    'memo_placeholder'      => 'Jot down notes freely during the meeting...',
    'memo_add'              => 'Add Memo',
    'memo_empty'            => 'No memos',
    'confirm_delete_memo'   => 'Are you sure you want to delete this memo?',
    'confirm_delete_meeting'=> 'Are you sure you want to delete these meeting minutes?',
    'confirm_delete_action' => 'Are you sure you want to delete this?',
    'overdue'               => 'Overdue',

    // ── Action Items
    'action_items'          => 'Action Items',
    'action_item_add'       => 'Add Action Item',
    'action_item_empty'     => 'No Action Items',
    'action_status_pending' => 'Pending',
    'action_status_in_progress' => 'In Progress',
    'action_status_completed'   => 'Done',
    'action_priority_medium'    => 'Medium',
    'action_priority_high'      => 'High',
    'action_priority_low'       => 'Low',
    'field_task_name'       => 'Task Name *',
    'field_task_desc'       => 'Details',
    'field_owner'           => 'Select Assignee',
    'field_owner_manual'    => 'Or enter name manually',
    'field_related_memo'    => 'Link Related Memo (optional)',

    // ── Meeting minutes form
    'form_basic_info'       => 'Basic Info',
    'form_meeting_title'    => 'Meeting Title',
    'form_meeting_title_ph' => 'Enter meeting title',
    'form_meeting_type'     => 'Meeting Type',
    'form_meeting_date'     => 'Meeting Date & Time',
    'form_project'          => 'Project',
    'form_project_select'   => 'Select Project',
    'form_project_code'     => 'Project Code',
    'form_project_code_ph'  => 'e.g. PRJ-2026-001',
    'form_weekly_dept'      => 'Department',
    'form_weekly_dept_ph'   => 'e.g. Development Team',
    'form_location'         => 'Location',
    'form_location_ph'      => 'e.g. 3F Conference Room A',
    'form_attendees'        => 'Attendees',
    'form_attendee_direct'  => 'Enter manually',
    'form_attendee_name_ph' => 'Enter name directly',
    'form_add_attendee'     => '+ Add Attendee',
    'form_meeting_content'  => 'Meeting Content',
    'form_agenda_ph'        => 'Enter main agenda items',
    'form_discussion_ph'    => 'Record discussion details',
    'form_decisions_ph'     => 'Enter decisions made in the meeting',

    // ── Project navigation
    'nav_overview'          => 'Overview',
    'nav_schedule'          => 'Schedule',
    'nav_qa'                => 'Q&A',
    'nav_files'             => 'Files',
    'nav_sr_receipt'        => 'SR',

    // ── Maintenance panel (웍스)
    'maintenance_panel'     => 'Screen Maintenance',
    'tab_edit'              => 'Edit',
    'tab_versions'          => 'Versions',
    'step_request'          => 'Request',
    'step_ai_analysis'      => '웍스 Analysis',
    'step_review_apply'     => 'Review & Apply',
    'register_screen'       => 'Register this screen for maintenance',
    'screen_name'           => 'Screen Name',
    'register_btn'          => 'Register',
    'request_label'         => 'Modification Request',
    'request_placeholder'   => "What would you like to change?\n\nExample: Change the header background to purple tones and add an icon to the save button.",
    'ai_gen_prompt'         => 'Generate 웍스 Prompt',
    'ai_gen_patch'          => 'Generate Patch →',
    'go_back'               => '← Back',
    'original_request'      => 'Original Request',
    'review_title'          => 'Review Changes',
    'back_to_prompt'        => 'Back to Prompt',
    'preview_btn'           => 'Preview',
    'approve_apply'         => 'Approve & Apply',
    'change_summary'        => 'Change Summary',
    'changed_files'         => 'Changed Files',
    'version_list'          => 'Version List',
    'rollback'              => 'Roll Back to This Version',
    'current_code'          => 'Current Code',
    'view_diff'             => 'Diff',
    'view_before'           => 'Before',
    'view_after'            => 'After',
    'no_versions'           => 'No saved versions',

    // ── Index JS messages
    'js_schedule_fail'      => 'Failed to update schedule.',
    'js_reply_fail'         => 'Failed to post reply.',
    'js_network_error'      => 'A network error occurred.',
    'js_save_fail'          => 'Failed to save.',
    'js_delete_fail'        => 'Failed to delete.',
    'js_upload_fail'        => 'Upload failed.',
    'js_load_fail2'         => 'Failed to load.',
    'js_uploading'          => 'Uploading…',
    'js_saving'             => 'Saving…',
    'js_registering_reply'  => 'Posting…',
    'js_select_file'        => 'Please select a file.',
    'js_enter_content'      => 'Please enter request details.',
    'js_enter_url_name'     => 'Please enter both display name and URL.',
    'js_enter_url'          => 'Please enter a URL.',
    'js_enter_display_name' => 'Please enter a display name.',
    'js_share_disable_confirm' => 'Are you sure you want to disable the share link?\nThe existing link will no longer be accessible.',
    'js_copy_copied'        => 'Copied!',
    'js_sharing'            => 'Shared',
    'js_sr_attach_add'      => 'Add SR Attachment',

    // ── Panel JS messages
    'js_registering'        => 'Registering…',
    'js_reg_fail'           => 'Registration failed',
    'js_prompt_generating'  => 'Generating prompt…',
    'js_prompt_analyzing'   => '웍스 is creating a structured prompt…',
    'js_prompt_fail'        => 'Prompt generation failed',
    'js_patch_generating'   => 'Analyzing patch…',
    'js_patch_analyzing'    => '웍스 is analyzing the source code and UI structure to generate a patch…',
    'js_patch_fail'         => 'Patch generation failed',
    'js_preview_preparing'  => 'Preparing…',
    'js_preview_generating' => 'Generating preview…',
    'js_preview_fail'       => 'Preview generation failed',
    'js_preview_blocked'    => 'Preview link (popup blocked — click to open)',
    'js_preview_opened'     => 'Preview opened in a new tab',
    'js_applying'           => 'Applying…',
    'js_apply_files'        => 'Applying to files…',
    'js_apply_fail'         => 'Apply failed',
    'js_loading'            => 'Loading…',
    'js_load_fail'          => 'Load failed',
    'js_no_file'            => '(No file)',
    'js_rolling_back'       => 'Rolling back…',
    'js_rollback_done'      => 'Rollback complete! Recorded as v:ver.',
    'js_rollback_fail'      => 'Rollback failed: ',
    'js_confirm_apply'      => 'Are you sure you want to apply this patch to the actual files?',
    'js_confirm_rollback'   => 'Are you sure you want to roll back to v:ver?',
    'js_enter_request'      => 'Please enter your modification request.',
    'js_system'             => 'System',
    'refined_prompt_hint'   => 'Core instruction',
];
