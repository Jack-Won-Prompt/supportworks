<?php

return [
    'nav'                 => 'Shared Folder',
    'title'               => 'Shared Folder',
    'subtitle'            => 'A shared file repository used by members of the same company.',
    'no_company'          => 'You do not belong to a company, so the shared folder is unavailable.',

    // Upload
    'upload'              => 'Upload Files',
    'upload_btn'          => 'Upload',
    'choose_files'        => 'Choose files',
    'no_file_selected'    => 'No file selected',
    'n_files'             => ':n files selected',
    'description_ph'      => 'Description (optional)',
    'uploaded'            => 'Uploaded :n file(s).',
    'personal'            => 'Personal',
    'personal_hint'       => 'Check to keep private (not shared with the company).',
    'my_personal'         => 'My personal files',

    // Folders (categories)
    'folders'             => 'Folders',
    'category_all'        => 'All',
    'category_none'       => 'Uncategorized',
    'category_add'        => 'Add folder',
    'category_name_ph'    => 'Folder name',
    'category_select'     => 'Select folder',
    'category_added'      => 'Folder added.',
    'category_deleted'    => 'Folder deleted.',
    'category_delete_confirm' => 'Delete this folder? Files in it will move to Uncategorized.',
    'category_has_children' => 'Cannot delete a folder that has subfolders. Please remove subfolders first.',
    'max_depth_reached'   => 'Folders can be nested up to :max levels.',
    'invalid_parent'      => 'Invalid parent folder.',
    'add_subfolder'       => 'Add subfolder',
    'subfolder_name_ph'   => 'Subfolder name',
    'subfolder_of'        => 'Add under :parent',

    // List
    'file_list'           => 'Files',
    'col_name'            => 'Name',
    'col_category'        => 'Folder',
    'col_size'            => 'Size',
    'col_uploader'        => 'Uploader',
    'col_date'            => 'Date',
    'empty'               => 'No files.',
    'empty_hint'          => 'Upload files above to get started.',

    // Actions
    'download'            => 'Download',
    'delete'              => 'Delete',
    'delete_confirm'      => 'Delete this file?',
    'deleted'             => 'File deleted.',

    // Move category
    'more_actions'        => 'More',
    'move_category'       => 'Move folder',
    'move_category_title' => 'Move to folder',
    'move_target'         => 'Target folder',
    'move_to_none'        => 'Uncategorized',
    'move_submit'         => 'Move',
    'moved'               => 'Folder updated.',

    // Project link
    'project_section'     => 'Shared folder files',
    'project_section_hint'=> 'Reference files from the company shared folder in this project.',
    'link_btn'            => 'Add from shared folder',
    'link_modal_title'    => 'Add shared folder file',
    'link_search_ph'      => 'Search file name',
    'link_no_files'       => 'No shareable files available.',
    'link_submit'         => 'Add',
    'linked'              => 'File linked.',
    'unlink'              => 'Unlink',
    'unlink_confirm'      => 'Unlink this file from the project? (Original stays in the shared folder.)',
    'unlinked'            => 'Unlinked.',
    'link_forbidden'      => 'Cannot link files from another company or personal files.',
    'project_empty'       => 'No linked shared folder files.',
];
