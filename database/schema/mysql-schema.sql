/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
DROP TABLE IF EXISTS `action_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `action_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `source_message_id` bigint(20) unsigned DEFAULT NULL,
  `source_context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`source_context`)),
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `action_items_user_id_foreign` (`user_id`),
  KEY `action_items_assigned_to_foreign` (`assigned_to`),
  KEY `action_items_project_id_foreign` (`project_id`),
  KEY `action_items_source_message_id_foreign` (`source_message_id`),
  CONSTRAINT `action_items_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `action_items_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `action_items_source_message_id_foreign` FOREIGN KEY (`source_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `action_items_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `activity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `activity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `action` varchar(20) NOT NULL,
  `subject_type` varchar(150) NOT NULL,
  `subject_id` bigint(20) unsigned NOT NULL,
  `subject_label` varchar(200) DEFAULT NULL,
  `changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changes`)),
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_created` (`user_id`,`created_at`),
  KEY `activity_logs_subject_created` (`subject_type`(100),`created_at`),
  KEY `activity_logs_created_at` (`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_access_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_access_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` bigint(20) unsigned NOT NULL,
  `access_token` varchar(80) NOT NULL,
  `refresh_token` varchar(80) NOT NULL,
  `access_expires_at` timestamp NOT NULL,
  `refresh_expires_at` timestamp NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `revoked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_access_tokens_access_token_unique` (`access_token`),
  UNIQUE KEY `admin_access_tokens_refresh_token_unique` (`refresh_token`),
  KEY `admin_access_tokens_admin_user_id_foreign` (`admin_user_id`),
  CONSTRAINT `admin_access_tokens_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_company_group_access`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_company_group_access` (
  `admin_user_id` bigint(20) unsigned NOT NULL,
  `company_group_id` bigint(20) unsigned NOT NULL,
  `can_manage_users` tinyint(1) NOT NULL DEFAULT 0,
  `can_view_chats` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_user_id`,`company_group_id`),
  KEY `admin_company_group_access_company_group_id_foreign` (`company_group_id`),
  CONSTRAINT `admin_company_group_access_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_company_group_access_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `role` enum('admin','operator','support_agent') NOT NULL DEFAULT 'support_agent',
  `token` varchar(80) NOT NULL,
  `status` enum('invited','accepted','expired','disabled') NOT NULL DEFAULT 'invited',
  `invited_by` bigint(20) unsigned NOT NULL,
  `company_group_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`company_group_ids`)),
  `expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_invitations_token_unique` (`token`),
  KEY `admin_invitations_invited_by_foreign` (`invited_by`),
  KEY `admin_invitations_email_index` (`email`),
  CONSTRAINT `admin_invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `login_id` varchar(50) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `result` enum('success','fail','locked') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `admin_login_logs_admin_user_id_foreign` (`admin_user_id`),
  CONSTRAINT `admin_login_logs_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_user_project`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_user_project` (
  `admin_user_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`admin_user_id`,`project_id`),
  KEY `admin_user_project_project_id_foreign` (`project_id`),
  CONSTRAINT `admin_user_project_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `admin_user_project_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `admin_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `login_id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('super_admin','admin','operator','support_agent') NOT NULL DEFAULT 'support_agent',
  `status` enum('active','inactive','locked') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `login_fail_count` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `locked_until` timestamp NULL DEFAULT NULL,
  `must_change_pw` tinyint(1) NOT NULL DEFAULT 0,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `admin_users_login_id_unique` (`login_id`),
  UNIQUE KEY `admin_users_email_unique` (`email`),
  KEY `admin_users_invited_by_foreign` (`invited_by`),
  CONSTRAINT `admin_users_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_approval_gates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_approval_gates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `stage_id` bigint(20) unsigned NOT NULL,
  `artifact_id` bigint(20) unsigned DEFAULT NULL,
  `gate_type` enum('stage_completion','artifact_approval') NOT NULL DEFAULT 'artifact_approval',
  `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `requested_by` bigint(20) unsigned NOT NULL,
  `requested_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `reviewed_by` bigint(20) unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `request_comment` text DEFAULT NULL,
  `review_comment` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_approval_gates_artifact_id_foreign` (`artifact_id`),
  KEY `ai_agent_approval_gates_requested_by_foreign` (`requested_by`),
  KEY `ai_agent_approval_gates_reviewed_by_foreign` (`reviewed_by`),
  KEY `ai_agent_approval_gates_project_id_status_index` (`project_id`,`status`),
  KEY `ai_agent_approval_gates_stage_id_status_index` (`stage_id`,`status`),
  CONSTRAINT `ai_agent_approval_gates_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_approval_gates_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_agent_approval_gates_requested_by_foreign` FOREIGN KEY (`requested_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ai_agent_approval_gates_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_approval_gates_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `ai_agent_project_stages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_artifact_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_artifact_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artifact_id` bigint(20) unsigned NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_type` enum('text','excel','pptx','pdf','image','other') NOT NULL,
  `file_size` bigint(20) unsigned NOT NULL,
  `mime_type` varchar(100) NOT NULL,
  `storage_path` varchar(500) NOT NULL,
  `parsed_content` longtext DEFAULT NULL,
  `parse_status` enum('pending','parsing','completed','failed') NOT NULL DEFAULT 'pending',
  `parse_error` text DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_artifact_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `ai_agent_artifact_files_artifact_id_index` (`artifact_id`),
  KEY `ai_agent_artifact_files_parse_status_index` (`parse_status`),
  KEY `ai_agent_artifact_files_file_type_index` (`file_type`),
  CONSTRAINT `ai_agent_artifact_files_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_agent_artifact_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_artifact_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_artifact_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `artifact_id` bigint(20) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `change_summary` varchar(500) DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_artifact_versions_artifact_id_version_unique` (`artifact_id`,`version`),
  KEY `ai_agent_artifact_versions_created_by_foreign` (`created_by`),
  KEY `ai_agent_artifact_versions_artifact_id_index` (`artifact_id`),
  CONSTRAINT `ai_agent_artifact_versions_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_agent_artifact_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_artifacts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_artifacts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `stage_id` bigint(20) unsigned NOT NULL,
  `scope_type` enum('project','screen') NOT NULL DEFAULT 'project',
  `scope_id` bigint(20) unsigned NOT NULL DEFAULT 0,
  `type` enum('as_is_analysis','to_be_requirements','gap_analysis','planning_doc','ia_flow','screen_prompts','mockup','design_tokens','component_spec','design_system_doc','erd','api_spec','rbac_model','frontend_code','backend_code','release_package') NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `status` enum('draft','pending_approval','approved','rejected') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_artifacts_stage_id_foreign` (`stage_id`),
  KEY `ai_agent_artifacts_created_by_foreign` (`created_by`),
  KEY `ai_agent_artifacts_approved_by_foreign` (`approved_by`),
  KEY `ai_agent_artifacts_project_id_stage_id_type_index` (`project_id`,`stage_id`,`type`),
  KEY `ai_agent_artifacts_project_id_status_index` (`project_id`,`status`),
  KEY `ai_artifacts_scope_idx` (`project_id`,`scope_type`,`scope_id`,`type`),
  CONSTRAINT `ai_agent_artifacts_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_artifacts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ai_agent_artifacts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_agent_artifacts_stage_id_foreign` FOREIGN KEY (`stage_id`) REFERENCES `ai_agent_project_stages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_gaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_gaps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `gap_id` varchar(20) NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `artifact_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `current_state` text DEFAULT NULL,
  `target_state` text DEFAULT NULL,
  `category` enum('보안','기능','UX','성능','데이터','인프라','기타') NOT NULL DEFAULT '기타',
  `severity` enum('high','medium','low') NOT NULL DEFAULT 'medium',
  `estimated_effort` enum('high','medium','low') DEFAULT NULL,
  `recommended_actions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`recommended_actions`)),
  `related_requirement_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`related_requirement_ids`)),
  `source` enum('ai','manual') NOT NULL DEFAULT 'ai',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_gaps_project_id_gap_id_unique` (`project_id`,`gap_id`),
  KEY `ai_agent_gaps_created_by_foreign` (`created_by`),
  KEY `ai_agent_gaps_project_id_severity_index` (`project_id`,`severity`),
  KEY `ai_agent_gaps_project_id_category_index` (`project_id`,`category`),
  KEY `ai_agent_gaps_artifact_id_index` (`artifact_id`),
  CONSTRAINT `ai_agent_gaps_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_gaps_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ai_agent_gaps_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_planning_templates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_planning_templates` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(100) NOT NULL,
  `name` varchar(255) NOT NULL,
  `version` varchar(20) NOT NULL DEFAULT '1.0.0',
  `description` text DEFAULT NULL,
  `structure` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`structure`)),
  `template_path` varchar(500) DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_default` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_planning_templates_key_version_unique` (`key`,`version`),
  KEY `ai_agent_planning_templates_created_by_foreign` (`created_by`),
  KEY `ai_agent_planning_templates_is_active_is_default_index` (`is_active`,`is_default`),
  CONSTRAINT `ai_agent_planning_templates_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_project_configs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_project_configs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `frontend_stack` enum('html','react','vue','blade') NOT NULL,
  `backend_stack` varchar(50) DEFAULT NULL,
  `ai_agent_enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_project_configs_project_id_unique` (`project_id`),
  KEY `ai_agent_project_configs_created_by_foreign` (`created_by`),
  KEY `ai_agent_project_configs_frontend_stack_index` (`frontend_stack`),
  CONSTRAINT `ai_agent_project_configs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ai_agent_project_configs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_project_stages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_project_stages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `type` enum('planning','design','dev_prep','development','release') NOT NULL,
  `name` varchar(100) NOT NULL,
  `status` enum('locked','in_progress','pending_approval','approved') NOT NULL DEFAULT 'locked',
  `order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_project_stages_project_id_type_unique` (`project_id`,`type`),
  KEY `ai_agent_project_stages_approved_by_foreign` (`approved_by`),
  KEY `ai_agent_project_stages_project_id_status_index` (`project_id`,`status`),
  CONSTRAINT `ai_agent_project_stages_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_project_stages_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_prompts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_prompts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `stage` enum('planning','design','dev_prep','development','release','common') NOT NULL,
  `task_type` enum('as_is_analysis','requirements_extraction','gap_analysis','planning_doc','ia_flow','screen_prompt','mockup_generation','design_consistency','erd_generation','api_spec','rbac_model','code_generation','code_review','custom') NOT NULL,
  `name` varchar(100) NOT NULL,
  `template` text NOT NULL,
  `variables` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`variables`)),
  `version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_prompts_created_by_foreign` (`created_by`),
  KEY `ai_agent_prompts_stage_task_type_is_active_index` (`stage`,`task_type`,`is_active`),
  KEY `ai_agent_prompts_project_id_index` (`project_id`),
  CONSTRAINT `ai_agent_prompts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ai_agent_prompts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_requirements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `artifact_id` bigint(20) unsigned DEFAULT NULL,
  `req_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `rationale` text DEFAULT NULL,
  `source_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`source_files`)),
  `priority` enum('must','should','could','wont') NOT NULL DEFAULT 'should',
  `category` varchar(100) DEFAULT NULL,
  `source` enum('as_is','to_be','gap') NOT NULL DEFAULT 'to_be',
  `status` enum('draft','confirmed','deferred','removed') NOT NULL DEFAULT 'draft',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_requirements_project_id_req_id_unique` (`project_id`,`req_id`),
  KEY `ai_agent_requirements_artifact_id_foreign` (`artifact_id`),
  KEY `ai_agent_requirements_project_id_priority_index` (`project_id`,`priority`),
  KEY `ai_agent_requirements_project_id_source_index` (`project_id`,`source`),
  CONSTRAINT `ai_agent_requirements_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_requirements_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_screens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_screens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `artifact_id` bigint(20) unsigned DEFAULT NULL,
  `gantt_task_id` bigint(20) unsigned DEFAULT NULL,
  `screen_id` varchar(20) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `figma_url` varchar(1000) DEFAULT NULL,
  `figma_frame_id` varchar(255) DEFAULT NULL,
  `figma_dev_mode_url` varchar(1000) DEFAULT NULL,
  `figma_file_key` varchar(100) DEFAULT NULL,
  `figma_frame_name` varchar(255) DEFAULT NULL,
  `figma_mapped_at` timestamp NULL DEFAULT NULL,
  `figma_mapped_by` bigint(20) unsigned DEFAULT NULL,
  `generation_prompt` text DEFAULT NULL,
  `mockup_content` text DEFAULT NULL,
  `stack` enum('html','react','vue','blade') DEFAULT NULL,
  `status` enum('draft','designed','approved') NOT NULL DEFAULT 'draft',
  `order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `source` enum('gantt','manual') NOT NULL DEFAULT 'manual',
  `assigned_to_user_id` bigint(20) unsigned DEFAULT NULL,
  `scheduled_start` date DEFAULT NULL,
  `scheduled_end` date DEFAULT NULL,
  `archived_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_screens_project_id_screen_id_unique` (`project_id`,`screen_id`),
  KEY `ai_agent_screens_artifact_id_foreign` (`artifact_id`),
  KEY `ai_agent_screens_project_id_status_index` (`project_id`,`status`),
  KEY `ai_agent_screens_gantt_task_id_foreign` (`gantt_task_id`),
  KEY `ai_agent_screens_assigned_to_user_id_foreign` (`assigned_to_user_id`),
  KEY `ai_agent_screens_project_id_archived_at_index` (`project_id`,`archived_at`),
  KEY `ai_agent_screens_figma_mapped_by_foreign` (`figma_mapped_by`),
  KEY `idx_figma_mapping` (`figma_file_key`,`figma_frame_id`),
  CONSTRAINT `ai_agent_screens_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_screens_assigned_to_user_id_foreign` FOREIGN KEY (`assigned_to_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_screens_figma_mapped_by_foreign` FOREIGN KEY (`figma_mapped_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_screens_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `output_type` enum('html','react','vue','blade') NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'draft',
  `current_step` varchar(48) NOT NULL DEFAULT 'project_selected',
  `ai_provider` varchar(16) NOT NULL DEFAULT 'auto',
  `last_activity_at` timestamp NULL DEFAULT NULL,
  `paused_at` timestamp NULL DEFAULT NULL,
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_sessions_project_id_status_index` (`project_id`,`status`),
  KEY `ai_agent_sessions_user_id_status_index` (`user_id`,`status`),
  KEY `ai_agent_sessions_last_activity_at_index` (`last_activity_at`),
  CONSTRAINT `ai_agent_sessions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_agent_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_stack_standards`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_stack_standards` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `stack` enum('html','react','vue','blade') NOT NULL,
  `category` enum('folder_structure','naming','component','state','api','styling','testing') NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `definition` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`definition`)),
  `validation_rules` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`validation_rules`)),
  `examples` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`examples`)),
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_stack_standards_stack_category_is_active_index` (`stack`,`category`,`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_traceability_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_traceability_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `source_type` varchar(50) NOT NULL,
  `source_id` bigint(20) unsigned NOT NULL,
  `source_ref` varchar(50) DEFAULT NULL,
  `target_type` varchar(50) NOT NULL,
  `target_id` bigint(20) unsigned NOT NULL,
  `target_ref` varchar(50) DEFAULT NULL,
  `link_type` varchar(50) NOT NULL DEFAULT 'implements',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tl_source_idx` (`project_id`,`source_type`,`source_id`),
  KEY `tl_target_idx` (`project_id`,`target_type`,`target_id`),
  CONSTRAINT `ai_agent_traceability_links_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_usage_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_usage_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `artifact_id` bigint(20) unsigned DEFAULT NULL,
  `stage` varchar(50) DEFAULT NULL,
  `task_type` varchar(100) DEFAULT NULL,
  `model` varchar(100) NOT NULL,
  `provider` varchar(50) NOT NULL DEFAULT 'anthropic',
  `input_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `output_tokens` int(10) unsigned NOT NULL DEFAULT 0,
  `cost_usd` decimal(10,6) NOT NULL DEFAULT 0.000000,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `status` enum('success','error') NOT NULL DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_agent_usage_logs_artifact_id_foreign` (`artifact_id`),
  KEY `ai_agent_usage_logs_project_id_created_at_index` (`project_id`,`created_at`),
  KEY `ai_agent_usage_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `ai_agent_usage_logs_status_index` (`status`),
  CONSTRAINT `ai_agent_usage_logs_artifact_id_foreign` FOREIGN KEY (`artifact_id`) REFERENCES `ai_agent_artifacts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_usage_logs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_agent_usage_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_agent_user_credentials`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_agent_user_credentials` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `figma_pat_encrypted` text DEFAULT NULL,
  `figma_pat_validated_at` timestamp NULL DEFAULT NULL,
  `figma_pat_validation_status` enum('valid','invalid','expired') DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_agent_user_credentials_user_id_unique` (`user_id`),
  CONSTRAINT `ai_agent_user_credentials_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_analysis_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_analysis_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `step_key` varchar(64) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `requires_user_decision` tinyint(1) NOT NULL DEFAULT 0,
  `user_decision` varchar(32) DEFAULT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`meta`)),
  `failure_reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_analysis_steps_session_id_step_key_unique` (`session_id`,`step_key`),
  KEY `ai_analysis_steps_session_id_status_index` (`session_id`,`status`),
  CONSTRAINT `ai_analysis_steps_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `ai_agent_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_confirmed_outputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_confirmed_outputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `output_id` bigint(20) unsigned NOT NULL,
  `confirmed_by` bigint(20) unsigned NOT NULL,
  `confirmed_at` timestamp NOT NULL,
  `summary` text DEFAULT NULL,
  `context_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context_meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_confirmed_outputs_output_id_unique` (`output_id`),
  KEY `ai_confirmed_outputs_confirmed_by_foreign` (`confirmed_by`),
  KEY `ai_confirmed_outputs_project_id_confirmed_at_index` (`project_id`,`confirmed_at`),
  CONSTRAINT `ai_confirmed_outputs_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `ai_confirmed_outputs_output_id_foreign` FOREIGN KEY (`output_id`) REFERENCES `ai_outputs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_confirmed_outputs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_conflicts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_conflicts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `output_id` bigint(20) unsigned DEFAULT NULL,
  `conflict_type` varchar(64) NOT NULL,
  `severity` varchar(16) NOT NULL DEFAULT 'medium',
  `description` text NOT NULL,
  `suggested_options_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`suggested_options_json`)),
  `user_decision` varchar(255) DEFAULT NULL,
  `user_decision_note` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_conflicts_session_id_status_index` (`session_id`,`status`),
  KEY `ai_conflicts_output_id_severity_index` (`output_id`,`severity`),
  CONSTRAINT `ai_conflicts_output_id_foreign` FOREIGN KEY (`output_id`) REFERENCES `ai_outputs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_conflicts_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `ai_agent_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_feedbacks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_feedbacks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `output_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `feedback_type` varchar(32) NOT NULL,
  `message` text DEFAULT NULL,
  `screenshot_path` varchar(500) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'open',
  `analysis_meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`analysis_meta`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_feedbacks_output_id_status_index` (`output_id`,`status`),
  KEY `ai_feedbacks_user_id_index` (`user_id`),
  CONSTRAINT `ai_feedbacks_output_id_foreign` FOREIGN KEY (`output_id`) REFERENCES `ai_outputs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_feedbacks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_figma_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_figma_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `figma_source_id` bigint(20) unsigned NOT NULL,
  `snapshot_version` smallint(5) unsigned NOT NULL DEFAULT 1,
  `raw_json_path` varchar(500) DEFAULT NULL,
  `normalized_json_path` varchar(500) DEFAULT NULL,
  `thumbnail_path` varchar(500) DEFAULT NULL,
  `checksum` char(64) DEFAULT NULL,
  `analyzed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_figma_snapshots_figma_source_id_snapshot_version_index` (`figma_source_id`,`snapshot_version`),
  KEY `ai_figma_snapshots_checksum_index` (`checksum`),
  CONSTRAINT `ai_figma_snapshots_figma_source_id_foreign` FOREIGN KEY (`figma_source_id`) REFERENCES `ai_figma_sources` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_figma_sources`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_figma_sources` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `session_id` bigint(20) unsigned NOT NULL,
  `source_type` varchar(32) NOT NULL DEFAULT 'figma_url',
  `figma_url` varchar(1000) DEFAULT NULL,
  `figma_file_key` varchar(64) DEFAULT NULL,
  `figma_node_id` varchar(64) DEFAULT NULL,
  `figma_version` varchar(64) DEFAULT NULL,
  `oauth_user_id` bigint(20) unsigned DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'disconnected',
  `last_error` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_figma_sources_oauth_user_id_foreign` (`oauth_user_id`),
  KEY `ai_figma_sources_session_id_status_index` (`session_id`,`status`),
  KEY `ai_figma_sources_project_id_figma_file_key_index` (`project_id`,`figma_file_key`),
  CONSTRAINT `ai_figma_sources_oauth_user_id_foreign` FOREIGN KEY (`oauth_user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_figma_sources_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `ai_figma_sources_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `ai_agent_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_fix_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_fix_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `system_error_log_id` bigint(20) unsigned NOT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `decision` varchar(16) DEFAULT NULL,
  `red_signals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`red_signals`)),
  `yellow_signals` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`yellow_signals`)),
  `decision_reason` text DEFAULT NULL,
  `blocked_path` varchar(255) DEFAULT NULL,
  `branch_name` varchar(255) DEFAULT NULL,
  `worktree_path` varchar(255) DEFAULT NULL,
  `proposed_fix_summary` text DEFAULT NULL,
  `changed_files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`changed_files`)),
  `test_result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`test_result`)),
  `pr_url` varchar(255) DEFAULT NULL,
  `deployed_commit` varchar(40) DEFAULT NULL,
  `deploy_log` text DEFAULT NULL,
  `approved_by_admin_id` bigint(20) unsigned DEFAULT NULL,
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `approved_by_type` varchar(60) DEFAULT NULL,
  `escalated_at` timestamp NULL DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `deployed_at` timestamp NULL DEFAULT NULL,
  `finished_at` timestamp NULL DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `retry_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `ai_fix_jobs_approved_by_admin_id_foreign` (`approved_by_admin_id`),
  KEY `ai_fix_jobs_status_created_at_index` (`status`,`created_at`),
  KEY `ai_fix_jobs_system_error_log_id_index` (`system_error_log_id`),
  KEY `ai_fix_jobs_decision_index` (`decision`),
  KEY `ai_fix_jobs_approved_by_morph_idx` (`approved_by_type`,`approved_by_id`),
  CONSTRAINT `ai_fix_jobs_system_error_log_id_foreign` FOREIGN KEY (`system_error_log_id`) REFERENCES `system_error_logs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_outputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_outputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_id` bigint(20) unsigned NOT NULL,
  `analysis_step_id` bigint(20) unsigned DEFAULT NULL,
  `version_no` smallint(5) unsigned NOT NULL DEFAULT 1,
  `output_type` varchar(16) NOT NULL,
  `files_json` longtext DEFAULT NULL,
  `zip_path` varchar(500) DEFAULT NULL,
  `preview_url` varchar(1000) DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'pending',
  `generated_by` varchar(32) DEFAULT NULL,
  `model_used` varchar(64) DEFAULT NULL,
  `input_tokens` int(10) unsigned DEFAULT NULL,
  `output_tokens` int(10) unsigned DEFAULT NULL,
  `change_summary` text DEFAULT NULL,
  `generated_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ai_outputs_session_id_version_no_unique` (`session_id`,`version_no`),
  KEY `ai_outputs_analysis_step_id_foreign` (`analysis_step_id`),
  KEY `ai_outputs_session_id_status_index` (`session_id`,`status`),
  CONSTRAINT `ai_outputs_analysis_step_id_foreign` FOREIGN KEY (`analysis_step_id`) REFERENCES `ai_analysis_steps` (`id`) ON DELETE SET NULL,
  CONSTRAINT `ai_outputs_session_id_foreign` FOREIGN KEY (`session_id`) REFERENCES `ai_agent_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `ai_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ai_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `anthropic_key` text DEFAULT NULL,
  `openai_key` text DEFAULT NULL,
  `figma_token` text DEFAULT NULL,
  `manus_key` text DEFAULT NULL,
  `manus_endpoint` varchar(500) DEFAULT NULL,
  `withworks_github_token` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analysis_session_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analysis_session_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `analysis_session_id` bigint(20) unsigned NOT NULL,
  `original_filename` varchar(255) NOT NULL,
  `stored_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `extracted_text` longtext DEFAULT NULL,
  `extraction_status` enum('pending','success','failed') NOT NULL DEFAULT 'pending',
  `extraction_error` text DEFAULT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analysis_session_files_analysis_session_id_foreign` (`analysis_session_id`),
  CONSTRAINT `analysis_session_files_analysis_session_id_foreign` FOREIGN KEY (`analysis_session_id`) REFERENCES `analysis_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `analysis_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `analysis_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_by_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','processing','review','approved','rejected','failed') NOT NULL DEFAULT 'pending',
  `input_text` text DEFAULT NULL,
  `llm_provider` enum('anthropic','openai') NOT NULL DEFAULT 'anthropic',
  `llm_model` varchar(255) NOT NULL DEFAULT 'claude-sonnet-4-6',
  `system_prompt_version` varchar(255) NOT NULL DEFAULT 'v1.0',
  `ai_raw_output` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_raw_output`)),
  `ai_structured_output` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_structured_output`)),
  `token_input` int(11) DEFAULT NULL,
  `token_output` int(11) DEFAULT NULL,
  `cost_estimated` decimal(10,4) DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `analysis_sessions_created_by_id_foreign` (`created_by_id`),
  KEY `analysis_sessions_project_id_status_index` (`project_id`,`status`),
  KEY `analysis_sessions_project_id_created_by_id_index` (`project_id`,`created_by_id`),
  CONSTRAINT `analysis_sessions_created_by_id_foreign` FOREIGN KEY (`created_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `analysis_sessions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `announcements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `announcements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `type` enum('info','warning','maintenance','update') NOT NULL DEFAULT 'info',
  `target_type` varchar(20) NOT NULL DEFAULT 'all',
  `target_company_group_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`target_company_group_ids`)),
  `send_email` tinyint(1) NOT NULL DEFAULT 0,
  `email_sent_at` timestamp NULL DEFAULT NULL,
  `email_sent_count` int(10) unsigned NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `starts_at` timestamp NULL DEFAULT NULL,
  `ends_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `announcements_created_by_foreign` (`created_by`),
  CONSTRAINT `announcements_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `answers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `answers` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `question_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `is_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `answers_question_id_foreign` (`question_id`),
  KEY `answers_user_id_foreign` (`user_id`),
  CONSTRAINT `answers_question_id_foreign` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `answers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `app_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `app_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `version` varchar(20) NOT NULL,
  `download_url` text NOT NULL,
  `release_notes` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache` (
  `key` varchar(255) NOT NULL,
  `value` mediumtext NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `cache_locks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cache_locks` (
  `key` varchar(255) NOT NULL,
  `owner` varchar(255) NOT NULL,
  `expiration` int(11) NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `collab_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `collab_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `session_key` varchar(64) NOT NULL,
  `initiator_id` bigint(20) unsigned NOT NULL,
  `participant_id` bigint(20) unsigned NOT NULL,
  `status` enum('pending','active','ended') NOT NULL DEFAULT 'pending',
  `permission` enum('view','guide','control') NOT NULL DEFAULT 'view',
  `current_url` varchar(1000) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `collab_sessions_session_key_unique` (`session_key`),
  KEY `collab_sessions_initiator_id_foreign` (`initiator_id`),
  KEY `collab_sessions_participant_id_foreign` (`participant_id`),
  CONSTRAINT `collab_sessions_initiator_id_foreign` FOREIGN KEY (`initiator_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `collab_sessions_participant_id_foreign` FOREIGN KEY (`participant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `commentable_type` varchar(255) NOT NULL,
  `commentable_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `comments_commentable_type_commentable_id_index` (`commentable_type`,`commentable_id`),
  KEY `comments_user_id_foreign` (`user_id`),
  CONSTRAINT `comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `company_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `company_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `code` varchar(50) NOT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `uses_withworks` tinyint(1) NOT NULL DEFAULT 0,
  `path_prefix` varchar(200) DEFAULT NULL,
  `shows_in_sr_menu` tinyint(1) NOT NULL DEFAULT 0,
  `features` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`features`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `company_groups_code_unique` (`code`),
  KEY `company_groups_uses_withworks_index` (`uses_withworks`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversation_user`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversation_user` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `last_read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `conversation_user_conversation_id_user_id_unique` (`conversation_id`,`user_id`),
  KEY `conversation_user_user_id_foreign` (`user_id`),
  CONSTRAINT `conversation_user_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `conversation_user_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `conversations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `is_group` tinyint(1) NOT NULL DEFAULT 0,
  `type` varchar(20) DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_agent_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_admin_id` bigint(20) unsigned DEFAULT NULL,
  `guest_token` varchar(80) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `conversations_company_group_id_foreign` (`company_group_id`),
  KEY `conversations_assigned_agent_id_foreign` (`assigned_agent_id`),
  KEY `conversations_assigned_admin_id_foreign` (`assigned_admin_id`),
  KEY `conversations_guest_token_index` (`guest_token`),
  CONSTRAINT `conversations_assigned_admin_id_foreign` FOREIGN KEY (`assigned_admin_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_assigned_agent_id_foreign` FOREIGN KEY (`assigned_agent_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `conversations_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverable_approvals`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverable_approvals` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deliverable_id` bigint(20) unsigned NOT NULL,
  `step_order` int(11) NOT NULL,
  `requester_id` bigint(20) unsigned NOT NULL,
  `approver_id` bigint(20) unsigned NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `responded_at` timestamp NULL DEFAULT NULL,
  `note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deliverable_approvals_requester_id_foreign` (`requester_id`),
  KEY `deliverable_approvals_approver_id_foreign` (`approver_id`),
  KEY `deliverable_approvals_deliverable_id_step_order_index` (`deliverable_id`,`step_order`),
  CONSTRAINT `deliverable_approvals_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliverable_approvals_deliverable_id_foreign` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliverable_approvals_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverable_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverable_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deliverable_id` bigint(20) unsigned NOT NULL,
  `step_order` smallint(5) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deliverable_comments_user_id_foreign` (`user_id`),
  KEY `deliverable_comments_deliverable_id_step_order_index` (`deliverable_id`,`step_order`),
  CONSTRAINT `deliverable_comments_deliverable_id_foreign` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliverable_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverable_file_registrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverable_file_registrations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deliverable_id` bigint(20) unsigned NOT NULL,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `file_version` smallint(5) unsigned NOT NULL,
  `lang` varchar(4) NOT NULL DEFAULT 'ko',
  `change_note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `deliverable_file_registrations_created_by_foreign` (`created_by`),
  KEY `dlv_freg_idx` (`deliverable_id`,`created_at`),
  KEY `dlv_freg_pf_idx` (`project_file_id`),
  CONSTRAINT `deliverable_file_registrations_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deliverable_file_registrations_deliverable_id_foreign` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE,
  CONSTRAINT `deliverable_file_registrations_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `project_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverable_step_data`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverable_step_data` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deliverable_id` bigint(20) unsigned NOT NULL,
  `step_order` int(11) NOT NULL,
  `field_key` varchar(100) NOT NULL,
  `value` longtext DEFAULT NULL,
  `en_value` longtext DEFAULT NULL,
  `en_hash` varchar(64) DEFAULT NULL,
  `image_map` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`image_map`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deliverable_step_data_deliverable_id_step_order_field_key_unique` (`deliverable_id`,`step_order`,`field_key`),
  CONSTRAINT `deliverable_step_data_deliverable_id_foreign` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverable_step_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverable_step_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deliverable_id` bigint(20) unsigned NOT NULL,
  `step_order` int(11) NOT NULL,
  `version_no` smallint(5) unsigned NOT NULL,
  `snapshot_fields` longtext DEFAULT NULL,
  `snapshot_tools` longtext DEFAULT NULL,
  `change_note` text DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dlv_step_ver_unique` (`deliverable_id`,`step_order`,`version_no`),
  KEY `deliverable_step_versions_created_by_foreign` (`created_by`),
  KEY `dlv_step_ver_idx` (`deliverable_id`,`step_order`),
  CONSTRAINT `deliverable_step_versions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `deliverable_step_versions_deliverable_id_foreign` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverable_tool_results`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverable_tool_results` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `deliverable_id` bigint(20) unsigned NOT NULL,
  `step_order` int(11) NOT NULL,
  `tool_id` varchar(30) NOT NULL,
  `result` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `dlv_tool_results_unique` (`deliverable_id`,`step_order`,`tool_id`),
  CONSTRAINT `deliverable_tool_results_deliverable_id_foreign` FOREIGN KEY (`deliverable_id`) REFERENCES `deliverables` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `deliverables`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `deliverables` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `type_id` varchar(30) NOT NULL,
  `current_step` int(11) NOT NULL DEFAULT 1,
  `status` varchar(20) NOT NULL DEFAULT 'not_started',
  `responsibility` varchar(5) NOT NULL DEFAULT 'B',
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `share_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `deliverables_share_token_unique` (`share_token`),
  KEY `deliverables_project_id_type_id_index` (`project_id`,`type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `desktop_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `desktop_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `access_token` varchar(80) NOT NULL,
  `refresh_token` varchar(80) NOT NULL,
  `access_expires_at` timestamp NOT NULL,
  `refresh_expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `desktop_tokens_access_token_unique` (`access_token`),
  UNIQUE KEY `desktop_tokens_refresh_token_unique` (`refresh_token`),
  KEY `desktop_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `desktop_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `device_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `device_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `token` varchar(512) NOT NULL,
  `platform` varchar(20) NOT NULL DEFAULT 'android',
  `last_used_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `device_tokens_token_unique` (`token`),
  KEY `device_tokens_user_id_index` (`user_id`),
  CONSTRAINT `device_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discussion_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussion_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discussion_id` bigint(20) unsigned NOT NULL,
  `discussion_comment_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discussion_attachments_discussion_comment_id_foreign` (`discussion_comment_id`),
  KEY `discussion_attachments_user_id_foreign` (`user_id`),
  KEY `discussion_attachments_discussion_id_index` (`discussion_id`),
  CONSTRAINT `discussion_attachments_discussion_comment_id_foreign` FOREIGN KEY (`discussion_comment_id`) REFERENCES `discussion_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussion_attachments_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussion_attachments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discussion_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussion_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discussion_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` longtext NOT NULL,
  `share_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discussion_comments_share_token_unique` (`share_token`),
  KEY `discussion_comments_user_id_foreign` (`user_id`),
  KEY `discussion_comments_discussion_id_index` (`discussion_id`),
  CONSTRAINT `discussion_comments_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussion_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discussion_participants`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussion_participants` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `discussion_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `discussion_participants_discussion_id_user_id_unique` (`discussion_id`,`user_id`),
  KEY `discussion_participants_user_id_foreign` (`user_id`),
  CONSTRAINT `discussion_participants_discussion_id_foreign` FOREIGN KEY (`discussion_id`) REFERENCES `discussions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussion_participants_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `discussions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `discussions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `source_file_comment_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `conclusion` longtext DEFAULT NULL,
  `comments_summary` longtext DEFAULT NULL,
  `comments_summary_at` timestamp NULL DEFAULT NULL,
  `comments_summary_count` int(10) unsigned DEFAULT NULL,
  `discussion_date` date DEFAULT NULL,
  `status` enum('open','in_progress','resolved') NOT NULL DEFAULT 'open',
  `reflection_status` enum('pending','reflected','rejected') NOT NULL DEFAULT 'pending',
  `reflection_note` text DEFAULT NULL,
  `reflected_planning_doc_id` bigint(20) unsigned DEFAULT NULL,
  `reflection_decided_by` bigint(20) unsigned DEFAULT NULL,
  `reflection_decided_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `discussions_user_id_foreign` (`user_id`),
  KEY `discussions_project_id_status_index` (`project_id`,`status`),
  KEY `discussions_discussion_date_index` (`discussion_date`),
  KEY `discussions_source_file_comment_id_index` (`source_file_comment_id`),
  KEY `discussions_reflected_planning_doc_id_foreign` (`reflected_planning_doc_id`),
  KEY `discussions_reflection_decided_by_foreign` (`reflection_decided_by`),
  KEY `discussions_reflection_status_index` (`reflection_status`),
  CONSTRAINT `discussions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `discussions_reflected_planning_doc_id_foreign` FOREIGN KEY (`reflected_planning_doc_id`) REFERENCES `planning_docs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discussions_reflection_decided_by_foreign` FOREIGN KEY (`reflection_decided_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discussions_source_file_comment_id_foreign` FOREIGN KEY (`source_file_comment_id`) REFERENCES `file_comments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `discussions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `failed_jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `failed_jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) NOT NULL,
  `connection` text NOT NULL,
  `queue` text NOT NULL,
  `payload` longtext NOT NULL,
  `exception` longtext NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_action_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_action_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `action` varchar(20) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `file_action_logs_project_file_id_foreign` (`project_file_id`),
  KEY `file_action_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `file_action_logs_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `project_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `file_action_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_annotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL DEFAULT 1 COMMENT '주석이 속한 파일 버전',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `page` smallint(5) unsigned DEFAULT NULL COMMENT '슬라이드/페이지 번호',
  `type` varchar(20) NOT NULL COMMENT 'number|rect|circle|line|text',
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT '좌표·크기·색상·텍스트 (% 단위)' CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_annotations_user_id_foreign` (`user_id`),
  KEY `file_annotations_project_file_id_version_index` (`project_file_id`,`version`),
  CONSTRAINT `file_annotations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_comment_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_comment_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `sent_date` date NOT NULL COMMENT '당일 중복 발송 방지용 날짜 키',
  `email_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sms_sent` tinyint(1) NOT NULL DEFAULT 0,
  `sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `fcn_file_date_unique` (`project_file_id`,`sent_date`),
  KEY `file_comment_notifications_user_id_foreign` (`user_id`),
  CONSTRAINT `file_comment_notifications_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `project_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `file_comment_notifications_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `guest_name` varchar(100) DEFAULT NULL,
  `page` smallint(5) unsigned DEFAULT NULL COMMENT '슬라이드/페이지 번호',
  `video_time` decimal(10,2) DEFAULT NULL,
  `content` text NOT NULL,
  `resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at_version` bigint(20) unsigned DEFAULT NULL COMMENT '어느 버전에서 반영 완료 처리되었는지',
  `frozen_at_version` smallint(5) unsigned DEFAULT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `reflected_at` timestamp NULL DEFAULT NULL,
  `reflected_by` bigint(20) unsigned DEFAULT NULL,
  `applied_step_order` smallint(5) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `file_comments_user_id_foreign` (`user_id`),
  KEY `file_comments_parent_id_foreign` (`parent_id`),
  KEY `file_comments_resolved_by_foreign` (`resolved_by`),
  KEY `file_comments_resolved_index` (`resolved`),
  KEY `fc_pf_frozen_idx` (`project_file_id`,`frozen_at_version`),
  KEY `file_comments_reflected_by_foreign` (`reflected_by`),
  CONSTRAINT `file_comments_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `file_comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `file_comments_reflected_by_foreign` FOREIGN KEY (`reflected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `file_comments_resolved_by_foreign` FOREIGN KEY (`resolved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `file_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `file_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `file_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `version` smallint(5) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `converted_pdf_path` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `change_note` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `file_versions_project_file_id_version_unique` (`project_file_id`,`version`),
  KEY `file_versions_uploaded_by_foreign` (`uploaded_by`),
  KEY `file_versions_project_file_id_index` (`project_file_id`),
  CONSTRAINT `file_versions_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `project_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `file_versions_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `git_commits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `git_commits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(30) NOT NULL DEFAULT 'withworks',
  `branch` varchar(100) NOT NULL DEFAULT 'origin/master',
  `branches` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`branches`)),
  `sha` varchar(40) NOT NULL,
  `patch_id` varchar(64) DEFAULT NULL,
  `author_name` varchar(100) NOT NULL,
  `author_email` varchar(200) DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `committed_at` datetime NOT NULL,
  `subject` text DEFAULT NULL,
  `sr_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sr_ids`)),
  `is_merge` tinyint(1) NOT NULL DEFAULT 0,
  `body` longtext DEFAULT NULL,
  `files_changed` int(10) unsigned NOT NULL DEFAULT 0,
  `files_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`files_json`)),
  `insertions` int(10) unsigned NOT NULL DEFAULT 0,
  `deletions` int(10) unsigned NOT NULL DEFAULT 0,
  `difficulty` decimal(3,1) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `git_commits_sha_unique` (`sha`),
  KEY `git_commits_source_committed_at_index` (`source`,`committed_at`),
  KEY `git_commits_user_id_committed_at_index` (`user_id`,`committed_at`),
  KEY `git_commits_author_email_index` (`author_email`),
  KEY `git_commits_difficulty_index` (`difficulty`),
  KEY `idx_git_commits_patch_id` (`patch_id`),
  KEY `idx_git_commits_is_merge` (`is_merge`),
  CONSTRAINT `git_commits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `git_sync_runs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `git_sync_runs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `source` varchar(30) NOT NULL DEFAULT 'withworks',
  `branch` varchar(100) NOT NULL DEFAULT 'origin/master',
  `since` datetime DEFAULT NULL,
  `until` datetime DEFAULT NULL,
  `inserted` int(10) unsigned NOT NULL DEFAULT 0,
  `skipped` int(10) unsigned NOT NULL DEFAULT 0,
  `status` varchar(20) NOT NULL DEFAULT 'success',
  `error_message` text DEFAULT NULL,
  `triggered_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `git_sync_runs_triggered_by_foreign` (`triggered_by`),
  CONSTRAINT `git_sync_runs_triggered_by_foreign` FOREIGN KEY (`triggered_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `invitations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `invitations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `email` varchar(255) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `project_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`project_ids`)),
  `token` varchar(64) NOT NULL,
  `invited_by` bigint(20) unsigned DEFAULT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `accepted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invitations_token_unique` (`token`),
  KEY `invitations_company_group_id_foreign` (`company_group_id`),
  KEY `invitations_invited_by_foreign` (`invited_by`),
  CONSTRAINT `invitations_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `invitations_invited_by_foreign` FOREIGN KEY (`invited_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `issues`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `issues` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` enum('버그','장애','문의','개선요청','기타') NOT NULL DEFAULT '기타',
  `status` enum('신규','처리중','해결','검증중','종결','보류','반려') NOT NULL DEFAULT '신규',
  `priority` enum('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `severity` enum('Critical','Major','Minor','Trivial') DEFAULT NULL,
  `environment` enum('운영','스테이징','개발') DEFAULT NULL,
  `reporter_id` bigint(20) unsigned NOT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `resolution` text DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `resolved_by_id` bigint(20) unsigned DEFAULT NULL,
  `sla_due` timestamp NULL DEFAULT NULL,
  `sla_breached` tinyint(1) NOT NULL DEFAULT 0,
  `linked_requirement_id` bigint(20) unsigned DEFAULT NULL,
  `converted_from_question_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `issues_project_id_foreign` (`project_id`),
  KEY `issues_reporter_id_foreign` (`reporter_id`),
  KEY `issues_assignee_id_foreign` (`assignee_id`),
  KEY `issues_resolved_by_id_foreign` (`resolved_by_id`),
  KEY `issues_linked_requirement_id_foreign` (`linked_requirement_id`),
  KEY `issues_converted_from_question_id_foreign` (`converted_from_question_id`),
  CONSTRAINT `issues_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_converted_from_question_id_foreign` FOREIGN KEY (`converted_from_question_id`) REFERENCES `questions` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_linked_requirement_id_foreign` FOREIGN KEY (`linked_requirement_id`) REFERENCES `requirements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `issues_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `issues_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`),
  CONSTRAINT `issues_resolved_by_id_foreign` FOREIGN KEY (`resolved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_type` varchar(255) NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `filename` varchar(255) NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `uploaded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `item_attachments_item_type_item_id_index` (`item_type`,`item_id`),
  CONSTRAINT `item_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_change_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_change_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_type` varchar(255) NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `changed_by_id` bigint(20) unsigned NOT NULL,
  `changed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `field_name` varchar(255) NOT NULL,
  `old_value` text DEFAULT NULL,
  `new_value` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_change_histories_changed_by_id_foreign` (`changed_by_id`),
  KEY `item_change_histories_item_type_item_id_index` (`item_type`,`item_id`),
  CONSTRAINT `item_change_histories_changed_by_id_foreign` FOREIGN KEY (`changed_by_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `item_type` varchar(255) NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `author_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `item_comments_author_id_foreign` (`author_id`),
  KEY `item_comments_item_type_item_id_index` (`item_type`,`item_id`),
  CONSTRAINT `item_comments_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `item_watchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `item_watchers` (
  `item_type` varchar(255) NOT NULL,
  `item_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`item_type`,`item_id`,`user_id`),
  KEY `item_watchers_user_id_foreign` (`user_id`),
  CONSTRAINT `item_watchers_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `job_batches`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `job_batches` (
  `id` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext NOT NULL,
  `options` mediumtext DEFAULT NULL,
  `cancelled_at` int(11) DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `jobs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `jobs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) NOT NULL,
  `payload` longtext NOT NULL,
  `attempts` tinyint(3) unsigned NOT NULL,
  `reserved_at` int(10) unsigned DEFAULT NULL,
  `available_at` int(10) unsigned NOT NULL,
  `created_at` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `legacy_schedules`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `legacy_schedules` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `group_name` varchar(100) DEFAULT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `start_date` datetime NOT NULL,
  `end_date` datetime DEFAULT NULL,
  `status` enum('pending','in_progress','completed','cancelled','review_submitted','review_completed') NOT NULL DEFAULT 'pending',
  `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
  `assigned_to` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `schedules_project_id_foreign` (`project_id`),
  KEY `schedules_assigned_to_foreign` (`assigned_to`),
  KEY `schedules_created_by_foreign` (`created_by`),
  CONSTRAINT `schedules_assigned_to_foreign` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `schedules_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `schedules_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailbox_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mailbox_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `disk` varchar(32) NOT NULL DEFAULT 'local',
  `path` varchar(500) NOT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `mime` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mailbox_attachments_message_id_index` (`message_id`),
  CONSTRAINT `mailbox_attachments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `mailbox_messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailbox_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mailbox_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `thread_id` bigint(20) unsigned DEFAULT NULL,
  `sender_id` bigint(20) unsigned DEFAULT NULL,
  `subject` varchar(300) NOT NULL,
  `body_html` longtext DEFAULT NULL,
  `body_text` longtext DEFAULT NULL,
  `message_id` varchar(255) NOT NULL,
  `in_reply_to` varchar(255) DEFAULT NULL,
  `references_chain` text DEFAULT NULL,
  `has_attachment` tinyint(1) NOT NULL DEFAULT 0,
  `recipient_count` int(10) unsigned NOT NULL DEFAULT 0,
  `sent_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mailbox_messages_message_id_unique` (`message_id`),
  KEY `mailbox_messages_sender_id_index` (`sender_id`),
  KEY `mailbox_messages_thread_id_index` (`thread_id`),
  KEY `mailbox_messages_in_reply_to_index` (`in_reply_to`),
  KEY `mailbox_messages_sent_at_index` (`sent_at`),
  CONSTRAINT `mailbox_messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mailbox_recipients`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mailbox_recipients` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `name` varchar(150) DEFAULT NULL,
  `type` enum('to','cc','bcc') NOT NULL DEFAULT 'to',
  `folder` enum('inbox','sent','trash','spam') NOT NULL DEFAULT 'inbox',
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mailbox_recipients_user_id_folder_is_read_index` (`user_id`,`folder`,`is_read`),
  KEY `mailbox_recipients_message_id_type_index` (`message_id`,`type`),
  CONSTRAINT `mailbox_recipients_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `mailbox_messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `mailbox_recipients_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_menus` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL COMMENT '메뉴명 (엑셀 [메뉴] 컬럼 원본)',
  `request_cnt` int(11) NOT NULL DEFAULT 0 COMMENT '연결된 요청 건수 (집계용)',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_maint_menus_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_request_attachments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_request_attachments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `uploaded_by` bigint(20) unsigned DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `disk` varchar(32) NOT NULL DEFAULT 'local',
  `path` varchar(500) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `mime` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maint_request_attachments_uploaded_by_foreign` (`uploaded_by`),
  KEY `maint_request_attachments_request_id_index` (`request_id`),
  CONSTRAINT `maint_request_attachments_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `maint_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maint_request_attachments_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_request_image_annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_request_image_annotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `maint_request_id` bigint(20) unsigned NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `shape` varchar(16) NOT NULL,
  `color` varchar(16) NOT NULL DEFAULT '#ef4444',
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`payload`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maint_request_image_annotations_user_id_foreign` (`user_id`),
  KEY `mr_img_ann_lookup_idx` (`maint_request_id`,`image_url`),
  CONSTRAINT `maint_request_image_annotations_maint_request_id_foreign` FOREIGN KEY (`maint_request_id`) REFERENCES `maint_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maint_request_image_annotations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_request_image_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_request_image_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `maint_request_id` bigint(20) unsigned NOT NULL,
  `image_url` varchar(500) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maint_request_image_comments_user_id_foreign` (`user_id`),
  KEY `mr_img_cmt_lookup_idx` (`maint_request_id`,`image_url`),
  CONSTRAINT `maint_request_image_comments_maint_request_id_foreign` FOREIGN KEY (`maint_request_id`) REFERENCES `maint_requests` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maint_request_image_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_request_notes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_request_notes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `request_id` bigint(20) unsigned NOT NULL,
  `note_type` enum('colo','link') NOT NULL COMMENT 'colo=콜로 비고, link=링크 비고',
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `body` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notes_request` (`request_id`),
  KEY `maint_request_notes_parent_id_index` (`parent_id`),
  CONSTRAINT `maint_request_notes_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `maint_request_notes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `maint_request_notes_request_id_foreign` FOREIGN KEY (`request_id`) REFERENCES `maint_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `excel_no` int(11) DEFAULT NULL COMMENT '엑셀 원본 No 컬럼 (추적용)',
  `source_sheet` varchar(30) NOT NULL DEFAULT 'sheet1' COMMENT '엑셀 시트명',
  `menu_id` bigint(20) unsigned NOT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `request_date` date DEFAULT NULL COMMENT '엑셀 [콜로 요청 일자]',
  `priority` enum('normal','urgent','recheck') NOT NULL DEFAULT 'normal',
  `category` varchar(100) DEFAULT NULL COMMENT '엑셀 [구분]',
  `summary` varchar(500) NOT NULL COMMENT '엑셀 [내용] 첫 줄 (요약)',
  `content` text DEFAULT NULL COMMENT '엑셀 [내용] 원문',
  `ai_summary` text DEFAULT NULL,
  `ai_summary_at` timestamp NULL DEFAULT NULL,
  `ai_summary_context_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_summary_context_ids`)),
  `ai_classification` varchar(20) DEFAULT NULL,
  `ai_review_summary` text DEFAULT NULL,
  `ai_review_difficulty` tinyint(3) unsigned DEFAULT NULL,
  `ai_review_effort` varchar(50) DEFAULT NULL,
  `ai_review_questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`ai_review_questions`)),
  `ai_review_status` varchar(20) DEFAULT NULL,
  `ai_review_at` datetime DEFAULT NULL,
  `ai_review_error` text DEFAULT NULL,
  `status` varchar(32) NOT NULL DEFAULT 'requested',
  `difficulty_score` tinyint(3) unsigned DEFAULT NULL,
  `progress_raw` varchar(100) DEFAULT NULL COMMENT '엑셀 [진행사항] 원본 텍스트',
  `colo_check_raw` varchar(50) DEFAULT NULL COMMENT '엑셀 [콜로 완료 확인] 원본 텍스트',
  `colo_user_id` bigint(20) unsigned DEFAULT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `assigned_at` datetime DEFAULT NULL,
  `assignee_raw` varchar(100) DEFAULT NULL COMMENT '복수 담당자/특이표기 원본 보존',
  `eta` date DEFAULT NULL COMMENT '엑셀 [완료예상일자]',
  `gantt_sort_order` int(11) DEFAULT NULL,
  `grid_refresh` varchar(100) DEFAULT NULL COMMENT '엑셀 [그리드 새로고침]',
  `completed_at` timestamp NULL DEFAULT NULL COMMENT '완료처리 시각',
  `reopen_count` int(10) unsigned NOT NULL DEFAULT 0,
  `paid_dev_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `paid_dev_days` smallint(5) unsigned DEFAULT NULL,
  `paid_dev_cost` bigint(20) unsigned DEFAULT NULL,
  `paid_dev_description` text DEFAULT NULL,
  `paid_dev_sent_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_requests_menu` (`menu_id`),
  KEY `idx_requests_status` (`status`),
  KEY `idx_requests_priority` (`priority`),
  KEY `idx_requests_date` (`request_date`),
  KEY `idx_requests_colo_user` (`colo_user_id`),
  KEY `idx_requests_assignee` (`assignee_id`),
  KEY `idx_maint_requests_company_group` (`company_group_id`),
  KEY `maint_requests_gantt_sort_order_index` (`gantt_sort_order`),
  CONSTRAINT `maint_requests_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `maint_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maint_requests_colo_user_id_foreign` FOREIGN KEY (`colo_user_id`) REFERENCES `maint_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maint_requests_menu_id_foreign` FOREIGN KEY (`menu_id`) REFERENCES `maint_menus` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maint_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maint_users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL COMMENT '담당자 이름',
  `team` enum('colo','withworks') NOT NULL COMMENT 'colo=콜로담당자, withworks=위드웍스 개발자',
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_maint_users_team_name` (`team`,`name`),
  KEY `maint_users_user_id_foreign` (`user_id`),
  KEY `idx_maint_users_company_group` (`company_group_id`),
  CONSTRAINT `maint_users_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_file_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_file_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `sr_target_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#7c3aed',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_file_categories_sr_target_id_foreign` (`sr_target_id`),
  KEY `maintenance_file_categories_project_id_foreign` (`project_id`),
  CONSTRAINT `maintenance_file_categories_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_file_categories_sr_target_id_foreign` FOREIGN KEY (`sr_target_id`) REFERENCES `sr_targets` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `sr_target_id` bigint(20) unsigned DEFAULT NULL,
  `maintenance_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `maintenance_category_id` bigint(20) unsigned DEFAULT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL DEFAULT '',
  `converted_pdf_path` varchar(255) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `description` varchar(255) DEFAULT NULL,
  `source_url` varchar(2048) DEFAULT NULL,
  `file_type` varchar(20) DEFAULT NULL,
  `share_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_files_share_token_unique` (`share_token`),
  KEY `maintenance_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `maintenance_files_maintenance_category_id_foreign` (`maintenance_category_id`),
  KEY `maintenance_files_maintenance_id_foreign` (`maintenance_id`),
  KEY `maintenance_files_sr_target_id_foreign` (`sr_target_id`),
  KEY `maintenance_files_project_id_foreign` (`project_id`),
  CONSTRAINT `maintenance_files_maintenance_category_id_foreign` FOREIGN KEY (`maintenance_category_id`) REFERENCES `maintenance_file_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_files_maintenance_id_foreign` FOREIGN KEY (`maintenance_id`) REFERENCES `project_maintenances` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_files_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_files_sr_target_id_foreign` FOREIGN KEY (`sr_target_id`) REFERENCES `sr_targets` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_screens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_screens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `screen_key` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `blade_path` varchar(255) NOT NULL,
  `url_pattern` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `maintenance_screens_screen_key_unique` (`screen_key`),
  KEY `maintenance_screens_user_id_foreign` (`user_id`),
  CONSTRAINT `maintenance_screens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `maintenance_versions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `maintenance_versions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `screen_id` bigint(20) unsigned NOT NULL,
  `version_no` int(10) unsigned NOT NULL DEFAULT 1,
  `change_summary` varchar(255) NOT NULL,
  `files` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`files`)),
  `prompt` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`prompt`)),
  `user_request` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'draft',
  `applied_at` timestamp NULL DEFAULT NULL,
  `applied_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `maintenance_versions_screen_id_foreign` (`screen_id`),
  KEY `maintenance_versions_applied_by_foreign` (`applied_by`),
  CONSTRAINT `maintenance_versions_applied_by_foreign` FOREIGN KEY (`applied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `maintenance_versions_screen_id_foreign` FOREIGN KEY (`screen_id`) REFERENCES `maintenance_screens` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_action_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_action_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `minute_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `owner_id` bigint(20) unsigned DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `priority` enum('high','medium','low') NOT NULL DEFAULT 'medium',
  `status` enum('pending','in_progress','completed') NOT NULL DEFAULT 'pending',
  `memo_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_action_items_minute_id_foreign` (`minute_id`),
  KEY `meeting_action_items_owner_id_foreign` (`owner_id`),
  KEY `meeting_action_items_memo_id_foreign` (`memo_id`),
  CONSTRAINT `meeting_action_items_memo_id_foreign` FOREIGN KEY (`memo_id`) REFERENCES `meeting_memos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_action_items_minute_id_foreign` FOREIGN KEY (`minute_id`) REFERENCES `meeting_minutes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_action_items_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_attendees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_attendees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `minute_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_attendees_minute_id_foreign` (`minute_id`),
  KEY `meeting_attendees_user_id_foreign` (`user_id`),
  CONSTRAINT `meeting_attendees_minute_id_foreign` FOREIGN KEY (`minute_id`) REFERENCES `meeting_minutes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_attendees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_memos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_memos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `minute_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_memos_minute_id_foreign` (`minute_id`),
  KEY `meeting_memos_user_id_foreign` (`user_id`),
  CONSTRAINT `meeting_memos_minute_id_foreign` FOREIGN KEY (`minute_id`) REFERENCES `meeting_minutes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_memos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_minutes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_minutes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'completed',
  `type` enum('general','project') NOT NULL DEFAULT 'general',
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `project_code` varchar(255) DEFAULT NULL,
  `weekly_department` varchar(255) DEFAULT NULL,
  `meeting_date` datetime NOT NULL,
  `location` varchar(255) DEFAULT NULL,
  `author_id` bigint(20) unsigned NOT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `agenda` text DEFAULT NULL,
  `discussion` text DEFAULT NULL,
  `decisions` text DEFAULT NULL,
  `ai_summary` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_minutes_project_id_foreign` (`project_id`),
  KEY `meeting_minutes_author_id_foreign` (`author_id`),
  KEY `meeting_minutes_company_group_id_foreign` (`company_group_id`),
  KEY `meeting_minutes_status_index` (`status`),
  CONSTRAINT `meeting_minutes_author_id_foreign` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `meeting_minutes_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_minutes_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `meeting_recordings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `meeting_recordings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `meeting_minute_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) DEFAULT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `duration_seconds` int(10) unsigned NOT NULL DEFAULT 0,
  `status` enum('uploaded','transcribing','transcribed','summarizing','completed','failed') NOT NULL DEFAULT 'uploaded',
  `transcription` text DEFAULT NULL,
  `transcription_segments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`transcription_segments`)),
  `summary` longtext DEFAULT NULL,
  `error_message` text DEFAULT NULL,
  `recorded_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `meeting_recordings_meeting_minute_id_foreign` (`meeting_minute_id`),
  KEY `meeting_recordings_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `meeting_recordings_project_id_created_at_index` (`project_id`,`created_at`),
  CONSTRAINT `meeting_recordings_meeting_minute_id_foreign` FOREIGN KEY (`meeting_minute_id`) REFERENCES `meeting_minutes` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_recordings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `meeting_recordings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `memo_shares`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memo_shares` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `memo_id` bigint(20) unsigned NOT NULL,
  `shared_by` bigint(20) unsigned NOT NULL,
  `shared_to` bigint(20) unsigned NOT NULL,
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `memo_shares_memo_id_shared_to_unique` (`memo_id`,`shared_to`),
  KEY `memo_shares_shared_by_foreign` (`shared_by`),
  KEY `memo_shares_shared_to_foreign` (`shared_to`),
  CONSTRAINT `memo_shares_memo_id_foreign` FOREIGN KEY (`memo_id`) REFERENCES `memos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `memo_shares_shared_by_foreign` FOREIGN KEY (`shared_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `memo_shares_shared_to_foreign` FOREIGN KEY (`shared_to`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `memos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `memos` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) DEFAULT NULL,
  `content` text NOT NULL,
  `color` varchar(20) NOT NULL DEFAULT 'yellow',
  `is_pinned` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `memos_user_id_foreign` (`user_id`),
  CONSTRAINT `memos_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_analyses`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_analyses` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `result` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`result`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `message_analyses_message_id_unique` (`message_id`),
  CONSTRAINT `message_analyses_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_image_annotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_image_annotations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `type` varchar(20) NOT NULL,
  `data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`data`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_image_annotations_message_id_foreign` (`message_id`),
  KEY `message_image_annotations_user_id_foreign` (`user_id`),
  CONSTRAINT `message_image_annotations_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_image_annotations_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `message_image_comments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `message_image_comments` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `admin_name` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `message_image_comments_message_id_foreign` (`message_id`),
  KEY `message_image_comments_user_id_foreign` (`user_id`),
  KEY `message_image_comments_admin_user_id_foreign` (`admin_user_id`),
  CONSTRAINT `message_image_comments_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `message_image_comments_message_id_foreign` FOREIGN KEY (`message_id`) REFERENCES `messages` (`id`) ON DELETE CASCADE,
  CONSTRAINT `message_image_comments_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `reply_to_id` bigint(20) unsigned DEFAULT NULL,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `sender_id` bigint(20) unsigned NOT NULL,
  `body` text DEFAULT NULL,
  `translated_body` text DEFAULT NULL,
  `translate_lang` varchar(10) DEFAULT NULL,
  `edited_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `file_size` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `messages_conversation_id_foreign` (`conversation_id`),
  KEY `messages_sender_id_foreign` (`sender_id`),
  KEY `messages_reply_to_id_foreign` (`reply_to_id`),
  CONSTRAINT `messages_conversation_id_foreign` FOREIGN KEY (`conversation_id`) REFERENCES `conversations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `messages_reply_to_id_foreign` FOREIGN KEY (`reply_to_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `messages_sender_id_foreign` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `migrations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `migrations` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `milestones`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `milestones` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `target_date` date DEFAULT NULL,
  `status` enum('planned','in_progress','completed','cancelled') NOT NULL DEFAULT 'planned',
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `milestones_project_id_display_order_index` (`project_id`,`display_order`),
  CONSTRAINT `milestones_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `mobile_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mobile_tokens` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `access_token` varchar(80) NOT NULL,
  `refresh_token` varchar(80) NOT NULL,
  `access_expires_at` timestamp NOT NULL,
  `refresh_expires_at` timestamp NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `mobile_tokens_access_token_unique` (`access_token`),
  UNIQUE KEY `mobile_tokens_refresh_token_unique` (`refresh_token`),
  KEY `mobile_tokens_user_id_foreign` (`user_id`),
  CONSTRAINT `mobile_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `password_reset_tokens`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plan_applications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_applications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `requirement_id` bigint(20) unsigned NOT NULL,
  `plan_id` bigint(20) unsigned NOT NULL,
  `applied_by_id` bigint(20) unsigned NOT NULL,
  `applied_at` timestamp NOT NULL,
  `insertion_position` enum('end','beginning','after_section') NOT NULL DEFAULT 'end',
  `section_anchor` varchar(255) DEFAULT NULL,
  `template_used` varchar(255) NOT NULL DEFAULT 'default',
  `inserted_markdown` text NOT NULL,
  `is_completed` tinyint(1) NOT NULL DEFAULT 0,
  `completed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_applications_requirement_id_foreign` (`requirement_id`),
  KEY `plan_applications_plan_id_foreign` (`plan_id`),
  KEY `plan_applications_applied_by_id_foreign` (`applied_by_id`),
  CONSTRAINT `plan_applications_applied_by_id_foreign` FOREIGN KEY (`applied_by_id`) REFERENCES `users` (`id`),
  CONSTRAINT `plan_applications_plan_id_foreign` FOREIGN KEY (`plan_id`) REFERENCES `planning_docs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plan_applications_requirement_id_foreign` FOREIGN KEY (`requirement_id`) REFERENCES `requirements` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `plan_do_acts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `plan_do_acts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `source_file_comment_id` bigint(20) unsigned DEFAULT NULL,
  `source_message_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `plan` text DEFAULT NULL,
  `do` text DEFAULT NULL,
  `act` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'plan',
  `source_excerpt` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `plan_do_acts_user_id_foreign` (`user_id`),
  KEY `plan_do_acts_project_id_status_index` (`project_id`,`status`),
  KEY `plan_do_acts_source_file_comment_id_index` (`source_file_comment_id`),
  KEY `plan_do_acts_source_message_id_index` (`source_message_id`),
  CONSTRAINT `plan_do_acts_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `plan_do_acts_source_file_comment_id_foreign` FOREIGN KEY (`source_file_comment_id`) REFERENCES `file_comments` (`id`) ON DELETE SET NULL,
  CONSTRAINT `plan_do_acts_source_message_id_foreign` FOREIGN KEY (`source_message_id`) REFERENCES `messages` (`id`) ON DELETE SET NULL,
  CONSTRAINT `plan_do_acts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planning_doc_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planning_doc_histories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `planning_doc_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL,
  `change_type` enum('user_add','user_edit','ai_integrate','ai_suggest','approved','rejected') NOT NULL,
  `before_content` longtext DEFAULT NULL,
  `after_content` longtext DEFAULT NULL,
  `summary` text DEFAULT NULL,
  `changed_by` bigint(20) unsigned NOT NULL,
  `approval_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planning_doc_histories_planning_doc_id_foreign` (`planning_doc_id`),
  KEY `planning_doc_histories_changed_by_foreign` (`changed_by`),
  KEY `planning_doc_histories_approved_by_foreign` (`approved_by`),
  CONSTRAINT `planning_doc_histories_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `planning_doc_histories_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `planning_doc_histories_planning_doc_id_foreign` FOREIGN KEY (`planning_doc_id`) REFERENCES `planning_docs` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planning_doc_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planning_doc_inputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `planning_doc_id` bigint(20) unsigned NOT NULL,
  `input_type` enum('text','memo','requirement','file','discussion') NOT NULL,
  `content` text DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `file_name` varchar(255) DEFAULT NULL,
  `status` enum('pending','processed') NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned NOT NULL,
  `source_discussion_id` bigint(20) unsigned DEFAULT NULL,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `planning_doc_inputs_planning_doc_id_foreign` (`planning_doc_id`),
  KEY `planning_doc_inputs_created_by_foreign` (`created_by`),
  KEY `planning_doc_inputs_source_discussion_id_index` (`source_discussion_id`),
  CONSTRAINT `planning_doc_inputs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `planning_doc_inputs_planning_doc_id_foreign` FOREIGN KEY (`planning_doc_id`) REFERENCES `planning_docs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `planning_doc_inputs_source_discussion_id_foreign` FOREIGN KEY (`source_discussion_id`) REFERENCES `discussions` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `planning_docs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `planning_docs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `content` longtext DEFAULT NULL,
  `pending_content` longtext DEFAULT NULL,
  `ai_summary` text DEFAULT NULL,
  `ai_conflicts` text DEFAULT NULL,
  `ai_suggestions` text DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `share_token` varchar(64) DEFAULT NULL,
  `status` enum('draft','ai_processed','pending_review','approved','rejected') NOT NULL DEFAULT 'draft',
  `created_by` bigint(20) unsigned NOT NULL,
  `approved_by` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `planning_docs_share_token_unique` (`share_token`),
  KEY `planning_docs_project_id_foreign` (`project_id`),
  KEY `planning_docs_created_by_foreign` (`created_by`),
  KEY `planning_docs_approved_by_foreign` (`approved_by`),
  CONSTRAINT `planning_docs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `planning_docs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `planning_docs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_feature_suggestions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_feature_suggestions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `reason` text DEFAULT NULL,
  `is_applied` tinyint(1) NOT NULL DEFAULT 0,
  `applied_at` timestamp NULL DEFAULT NULL,
  `requirement_id` bigint(20) unsigned DEFAULT NULL,
  `planning_doc_id` bigint(20) unsigned DEFAULT NULL,
  `inserted_markdown` text DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_feature_suggestions_project_id_foreign` (`project_id`),
  KEY `project_feature_suggestions_created_by_foreign` (`created_by`),
  KEY `project_feature_suggestions_requirement_id_foreign` (`requirement_id`),
  KEY `project_feature_suggestions_planning_doc_id_foreign` (`planning_doc_id`),
  CONSTRAINT `project_feature_suggestions_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `project_feature_suggestions_planning_doc_id_foreign` FOREIGN KEY (`planning_doc_id`) REFERENCES `planning_docs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_feature_suggestions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_feature_suggestions_requirement_id_foreign` FOREIGN KEY (`requirement_id`) REFERENCES `requirements` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_file_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_file_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `name` varchar(80) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_file_categories_project_id_foreign` (`project_id`),
  CONSTRAINT `project_file_categories_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_file_review_requests`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_file_review_requests` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_file_id` bigint(20) unsigned NOT NULL,
  `requester_id` bigint(20) unsigned NOT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `message` text DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_file_review_requests_project_file_id_reviewer_id_unique` (`project_file_id`,`reviewer_id`),
  KEY `project_file_review_requests_requester_id_foreign` (`requester_id`),
  KEY `project_file_review_requests_reviewer_id_foreign` (`reviewer_id`),
  CONSTRAINT `project_file_review_requests_project_file_id_foreign` FOREIGN KEY (`project_file_id`) REFERENCES `project_files` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_file_review_requests_requester_id_foreign` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_file_review_requests_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `maintenance_id` bigint(20) unsigned DEFAULT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL,
  `sub_task_id` bigint(20) unsigned DEFAULT NULL,
  `maintenance_category_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `converted_pdf_path` varchar(255) DEFAULT NULL,
  `mime_type` varchar(255) NOT NULL,
  `size` bigint(20) unsigned NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `source_url` varchar(2048) DEFAULT NULL,
  `file_type` varchar(10) NOT NULL DEFAULT 'file',
  `share_token` varchar(64) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_files_share_token_unique` (`share_token`),
  KEY `project_files_project_id_foreign` (`project_id`),
  KEY `project_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `project_files_category_id_foreign` (`category_id`),
  KEY `project_files_maintenance_id_foreign` (`maintenance_id`),
  KEY `project_files_maintenance_category_id_foreign` (`maintenance_category_id`),
  KEY `project_files_schedule_id_foreign` (`schedule_id`),
  KEY `project_files_sub_task_id_foreign` (`sub_task_id`),
  CONSTRAINT `project_files_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `project_file_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_files_maintenance_category_id_foreign` FOREIGN KEY (`maintenance_category_id`) REFERENCES `maintenance_file_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_files_maintenance_id_foreign` FOREIGN KEY (`maintenance_id`) REFERENCES `project_maintenances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_files_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_files_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `legacy_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_files_sub_task_id_foreign` FOREIGN KEY (`sub_task_id`) REFERENCES `sub_tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_git_links`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_git_links` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `source` varchar(30) NOT NULL DEFAULT 'withworks',
  `repo` varchar(200) NOT NULL DEFAULT 'dhlogitsticsPlatform/withworks',
  `path_prefix` varchar(200) DEFAULT NULL,
  `linked_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `pgl_project_source_unique` (`project_id`,`source`),
  KEY `project_git_links_linked_by_foreign` (`linked_by`),
  CONSTRAINT `project_git_links_linked_by_foreign` FOREIGN KEY (`linked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_git_links_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_leaves`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_leaves` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `leave_type` varchar(255) NOT NULL DEFAULT 'annual',
  `reason` text DEFAULT NULL,
  `status` varchar(255) NOT NULL DEFAULT 'pending',
  `created_by` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `approver_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_leaves_project_id_foreign` (`project_id`),
  KEY `project_leaves_user_id_foreign` (`user_id`),
  KEY `project_leaves_created_by_foreign` (`created_by`),
  KEY `project_leaves_approver_id_foreign` (`approver_id`),
  CONSTRAINT `project_leaves_approver_id_foreign` FOREIGN KEY (`approver_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_leaves_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_leaves_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_leaves_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_maintenance_replies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_maintenance_replies` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_maintenance_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `admin_user_id` bigint(20) unsigned DEFAULT NULL,
  `content` text NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_maintenance_replies_project_maintenance_id_foreign` (`project_maintenance_id`),
  KEY `project_maintenance_replies_user_id_foreign` (`user_id`),
  KEY `project_maintenance_replies_admin_user_id_foreign` (`admin_user_id`),
  CONSTRAINT `project_maintenance_replies_admin_user_id_foreign` FOREIGN KEY (`admin_user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_maintenance_replies_project_maintenance_id_foreign` FOREIGN KEY (`project_maintenance_id`) REFERENCES `project_maintenances` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_maintenance_replies_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_maintenances`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_maintenances` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `sr_target_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` enum('low','normal','high','urgent') NOT NULL DEFAULT 'normal',
  `status` enum('pending','in_progress','completed','rejected') NOT NULL DEFAULT 'pending',
  `requested_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `scheduled_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_maintenances_user_id_foreign` (`user_id`),
  KEY `project_maintenances_sr_target_id_foreign` (`sr_target_id`),
  KEY `project_maintenances_project_id_foreign` (`project_id`),
  CONSTRAINT `project_maintenances_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `project_maintenances_sr_target_id_foreign` FOREIGN KEY (`sr_target_id`) REFERENCES `sr_targets` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_maintenances_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_members`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_members` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `role` enum('manager','member','viewer') NOT NULL DEFAULT 'member',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_members_project_id_user_id_unique` (`project_id`,`user_id`),
  KEY `project_members_user_id_foreign` (`user_id`),
  CONSTRAINT `project_members_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_members_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_shared_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_shared_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `shared_file_id` bigint(20) unsigned NOT NULL,
  `attached_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `project_shared_files_project_id_shared_file_id_unique` (`project_id`,`shared_file_id`),
  KEY `project_shared_files_project_id_index` (`project_id`),
  KEY `project_shared_files_shared_file_id_index` (`shared_file_id`),
  CONSTRAINT `project_shared_files_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `project_shared_files_shared_file_id_foreign` FOREIGN KEY (`shared_file_id`) REFERENCES `shared_files` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `project_urs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `project_urs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `status` enum('draft','qa_in_progress','generating','completed') NOT NULL DEFAULT 'draft',
  `qa_questions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`qa_questions`)),
  `current_q_index` int(10) unsigned NOT NULL DEFAULT 0,
  `content` longtext DEFAULT NULL,
  `content_en` longtext DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `project_urs_project_id_foreign` (`project_id`),
  KEY `project_urs_created_by_foreign` (`created_by`),
  CONSTRAINT `project_urs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `project_urs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `projects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `projects` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `pb_data_fetching` varchar(255) DEFAULT NULL,
  `pb_auto_update_mode` enum('disabled','suggest_only','auto_high_confidence','fully_automated') NOT NULL DEFAULT 'suggest_only',
  `status` enum('active','on_hold','completed','cancelled') NOT NULL DEFAULT 'active',
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `created_by` bigint(20) unsigned NOT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `si_mode_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `sm_mode_enabled` tinyint(1) NOT NULL DEFAULT 0,
  `preferred_llm_provider` enum('anthropic','openai') NOT NULL DEFAULT 'anthropic',
  `preferred_llm_model` varchar(255) DEFAULT NULL,
  `client_name` varchar(255) DEFAULT NULL,
  `client_email` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `projects_created_by_foreign` (`created_by`),
  KEY `projects_company_group_id_foreign` (`company_group_id`),
  CONSTRAINT `projects_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE SET NULL,
  CONSTRAINT `projects_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prompt_histories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prompt_histories` (
  `history_id` varchar(40) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `session_id` varchar(40) DEFAULT NULL,
  `mode` enum('general','project') NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL,
  `task_type` varchar(40) NOT NULL,
  `original_input` text NOT NULL,
  `clarification_rounds` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`clarification_rounds`)),
  `refined_prompt` longtext NOT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`metadata`)),
  `llm_model` varchar(60) DEFAULT NULL,
  `provider_used` varchar(20) DEFAULT NULL,
  `fallback_reason` varchar(200) DEFAULT NULL,
  `total_tokens` int(10) unsigned DEFAULT NULL,
  `elapsed_ms` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`history_id`),
  KEY `prompt_histories_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `prompt_histories_project_id_index` (`project_id`),
  KEY `prompt_histories_task_id_index` (`schedule_id`),
  KEY `prompt_histories_task_id_created_at_index` (`schedule_id`,`created_at`),
  KEY `prompt_histories_task_type_index` (`task_type`),
  KEY `prompt_histories_provider_used_index` (`provider_used`),
  CONSTRAINT `prompt_histories_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prompt_histories_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `legacy_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prompt_histories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prompt_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prompt_sessions` (
  `session_id` varchar(40) NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `mode` enum('general','project') NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `schedule_id` bigint(20) unsigned DEFAULT NULL,
  `original_input` text NOT NULL,
  `current_round` smallint(5) unsigned NOT NULL DEFAULT 1,
  `rounds_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`rounds_data`)),
  `status` enum('in_progress','completed','expired','abandoned') NOT NULL DEFAULT 'in_progress',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `expires_at` timestamp NOT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`session_id`),
  KEY `prompt_sessions_user_id_index` (`user_id`),
  KEY `prompt_sessions_status_expires_at_index` (`status`,`expires_at`),
  KEY `prompt_sessions_project_id_foreign` (`project_id`),
  KEY `prompt_sessions_schedule_id_foreign` (`schedule_id`),
  CONSTRAINT `prompt_sessions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prompt_sessions_schedule_id_foreign` FOREIGN KEY (`schedule_id`) REFERENCES `legacy_schedules` (`id`) ON DELETE SET NULL,
  CONSTRAINT `prompt_sessions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `prompt_suffixes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `prompt_suffixes` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `label` varchar(100) NOT NULL,
  `body` text NOT NULL,
  `sort_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `prompt_suffixes_user_id_sort_order_index` (`user_id`,`sort_order`),
  CONSTRAINT `prompt_suffixes_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `questions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `questions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `status` enum('open','answered','closed') NOT NULL DEFAULT 'open',
  `is_private` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `converted_to_issue_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `questions_project_id_foreign` (`project_id`),
  KEY `questions_user_id_foreign` (`user_id`),
  KEY `questions_converted_to_issue_id_foreign` (`converted_to_issue_id`),
  CONSTRAINT `questions_converted_to_issue_id_foreign` FOREIGN KEY (`converted_to_issue_id`) REFERENCES `issues` (`id`) ON DELETE SET NULL,
  CONSTRAINT `questions_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `questions_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `quick_prompts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `quick_prompts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `original_input` text NOT NULL,
  `refined_prompt` longtext DEFAULT NULL,
  `base_refined_prompt` longtext DEFAULT NULL,
  `append_confirmation` tinyint(1) NOT NULL DEFAULT 0,
  `applied_suffix_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`applied_suffix_ids`)),
  `provider_used` varchar(30) DEFAULT NULL,
  `model_used` varchar(60) DEFAULT NULL,
  `fallback_reason` varchar(200) DEFAULT NULL,
  `elapsed_ms` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `quick_prompts_user_id_created_at_index` (`user_id`,`created_at`),
  CONSTRAINT `quick_prompts_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `requirements`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `requirements` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` enum('draft','analyzing','confirmed','changed','deferred','cancelled') NOT NULL DEFAULT 'draft',
  `priority` enum('critical','high','medium','low') NOT NULL DEFAULT 'medium',
  `category` enum('functional','non_functional','ui','data','security') NOT NULL DEFAULT 'functional',
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `reporter_id` bigint(20) unsigned NOT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `requirement_type` enum('initial','additional','change') NOT NULL DEFAULT 'initial',
  `source_ref` varchar(255) DEFAULT NULL,
  `approval_status` enum('reviewing','approved','rejected','returned') NOT NULL DEFAULT 'reviewing',
  `out_of_scope` tinyint(1) NOT NULL DEFAULT 0,
  `scope_reason` text DEFAULT NULL,
  `duplicate_of_id` bigint(20) unsigned DEFAULT NULL,
  `duplicate_reason` text DEFAULT NULL,
  `approved_by_id` bigint(20) unsigned DEFAULT NULL,
  `approved_at` timestamp NULL DEFAULT NULL,
  `source_type` enum('manual','ai_analyzed') NOT NULL DEFAULT 'manual',
  `source_session_id` bigint(20) unsigned DEFAULT NULL,
  `ai_confidence` decimal(3,2) DEFAULT NULL,
  `user_modified` tinyint(1) NOT NULL DEFAULT 0,
  `applied_to_plan` tinyint(1) NOT NULL DEFAULT 0,
  `applied_to_plan_at` timestamp NULL DEFAULT NULL,
  `applied_to_plan_id` bigint(20) unsigned DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `requirements_assignee_id_foreign` (`assignee_id`),
  KEY `requirements_reporter_id_foreign` (`reporter_id`),
  KEY `requirements_approved_by_id_foreign` (`approved_by_id`),
  KEY `requirements_project_id_status_index` (`project_id`,`status`),
  KEY `requirements_project_id_priority_index` (`project_id`,`priority`),
  KEY `requirements_applied_to_plan_id_foreign` (`applied_to_plan_id`),
  KEY `requirements_duplicate_of_id_foreign` (`duplicate_of_id`),
  KEY `requirements_project_id_out_of_scope_index` (`project_id`,`out_of_scope`),
  KEY `requirements_project_id_duplicate_of_id_index` (`project_id`,`duplicate_of_id`),
  CONSTRAINT `requirements_applied_to_plan_id_foreign` FOREIGN KEY (`applied_to_plan_id`) REFERENCES `planning_docs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requirements_approved_by_id_foreign` FOREIGN KEY (`approved_by_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requirements_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requirements_duplicate_of_id_foreign` FOREIGN KEY (`duplicate_of_id`) REFERENCES `requirements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `requirements_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `requirements_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sessions` (
  `id` varchar(255) NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `payload` longtext NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shared_file_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shared_file_categories` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_group_id` bigint(20) unsigned NOT NULL,
  `parent_id` bigint(20) unsigned DEFAULT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `name` varchar(80) NOT NULL,
  `color` varchar(7) NOT NULL DEFAULT '#6366f1',
  `sort_order` smallint(5) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_file_categories_company_group_id_index` (`company_group_id`),
  KEY `shared_file_categories_parent_id_foreign` (`parent_id`),
  KEY `shared_file_categories_company_group_id_parent_id_index` (`company_group_id`,`parent_id`),
  KEY `shared_file_categories_user_id_foreign` (`user_id`),
  KEY `shared_file_categories_company_group_id_user_id_index` (`company_group_id`,`user_id`),
  CONSTRAINT `shared_file_categories_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shared_file_categories_parent_id_foreign` FOREIGN KEY (`parent_id`) REFERENCES `shared_file_categories` (`id`),
  CONSTRAINT `shared_file_categories_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `shared_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `shared_files` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `company_group_id` bigint(20) unsigned NOT NULL,
  `category_id` bigint(20) unsigned DEFAULT NULL,
  `uploaded_by` bigint(20) unsigned NOT NULL,
  `original_name` varchar(255) NOT NULL,
  `stored_name` varchar(255) NOT NULL,
  `path` varchar(255) NOT NULL,
  `mime_type` varchar(150) DEFAULT NULL,
  `size` bigint(20) unsigned NOT NULL DEFAULT 0,
  `description` text DEFAULT NULL,
  `is_personal` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `shared_files_category_id_foreign` (`category_id`),
  KEY `shared_files_uploaded_by_foreign` (`uploaded_by`),
  KEY `shared_files_company_group_id_category_id_index` (`company_group_id`,`category_id`),
  KEY `shared_files_cg_personal_idx` (`company_group_id`,`is_personal`),
  CONSTRAINT `shared_files_category_id_foreign` FOREIGN KEY (`category_id`) REFERENCES `shared_file_categories` (`id`) ON DELETE SET NULL,
  CONSTRAINT `shared_files_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE CASCADE,
  CONSTRAINT `shared_files_uploaded_by_foreign` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sr_difficulty_mappings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sr_difficulty_mappings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sr_id` bigint(20) unsigned NOT NULL,
  `difficulty_unit_no` smallint(5) unsigned NOT NULL,
  `score` tinyint(3) unsigned NOT NULL,
  `mapped_by` bigint(20) unsigned DEFAULT NULL,
  `mapped_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_srdm_sr_unit` (`sr_id`,`difficulty_unit_no`),
  KEY `idx_srdm_sr` (`sr_id`),
  KEY `idx_srdm_unit` (`difficulty_unit_no`),
  CONSTRAINT `sr_difficulty_mappings_sr_id_foreign` FOREIGN KEY (`sr_id`) REFERENCES `maint_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sr_targets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sr_targets` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sr_targets_project_id_foreign` (`project_id`),
  KEY `sr_targets_created_by_foreign` (`created_by`),
  CONSTRAINT `sr_targets_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sr_targets_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sub_task_assignees`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_task_assignees` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sub_task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `sub_task_assignees_sub_task_id_user_id_unique` (`sub_task_id`,`user_id`),
  KEY `sub_task_assignees_user_id_index` (`user_id`),
  CONSTRAINT `sub_task_assignees_sub_task_id_foreign` FOREIGN KEY (`sub_task_id`) REFERENCES `sub_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sub_task_assignees_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sub_task_status_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_task_status_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `sub_task_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `old_status` varchar(20) DEFAULT NULL,
  `new_status` varchar(20) NOT NULL,
  `reason` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_task_status_logs_user_id_foreign` (`user_id`),
  KEY `sub_task_status_logs_sub_task_id_created_at_index` (`sub_task_id`,`created_at`),
  CONSTRAINT `sub_task_status_logs_sub_task_id_foreign` FOREIGN KEY (`sub_task_id`) REFERENCES `sub_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sub_task_status_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `sub_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `sub_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `task_group_id` bigint(20) unsigned NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `start_date` date NOT NULL,
  `end_date` date NOT NULL,
  `assignee_id` bigint(20) unsigned DEFAULT NULL,
  `status` enum('not_started','in_progress','completed','blocked','on_hold') NOT NULL DEFAULT 'not_started',
  `progress` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `source_type` enum('manual','ai_generated','migrated') NOT NULL DEFAULT 'manual',
  `source_plan_id` bigint(20) unsigned DEFAULT NULL,
  `requirement_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `sub_tasks_assignee_id_foreign` (`assignee_id`),
  KEY `sub_tasks_source_plan_id_foreign` (`source_plan_id`),
  KEY `sub_tasks_project_id_display_order_index` (`project_id`,`display_order`),
  KEY `sub_tasks_task_group_id_display_order_index` (`task_group_id`,`display_order`),
  KEY `sub_tasks_project_id_status_index` (`project_id`,`status`),
  KEY `sub_tasks_requirement_id_foreign` (`requirement_id`),
  CONSTRAINT `sub_tasks_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sub_tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sub_tasks_requirement_id_foreign` FOREIGN KEY (`requirement_id`) REFERENCES `requirements` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sub_tasks_source_plan_id_foreign` FOREIGN KEY (`source_plan_id`) REFERENCES `planning_docs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `sub_tasks_task_group_id_foreign` FOREIGN KEY (`task_group_id`) REFERENCES `task_groups` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_error_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_error_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `level` varchar(20) NOT NULL DEFAULT 'error',
  `source` varchar(32) DEFAULT NULL,
  `origin` varchar(16) DEFAULT NULL,
  `exception` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `file` varchar(500) DEFAULT NULL,
  `line` int(10) unsigned DEFAULT NULL,
  `trace` longtext DEFAULT NULL,
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `is_resolved` tinyint(1) NOT NULL DEFAULT 0,
  `resolved_by` bigint(20) unsigned DEFAULT NULL,
  `resolved_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `system_error_logs_level_index` (`level`),
  KEY `system_error_logs_is_resolved_index` (`is_resolved`),
  KEY `system_error_logs_source_index` (`source`),
  KEY `system_error_logs_origin_index` (`origin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `system_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `system_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `maintenance_mode` tinyint(1) NOT NULL DEFAULT 0,
  `maintenance_message` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `task_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `task_groups` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `milestone_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `display_order` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `task_groups_project_id_display_order_index` (`project_id`,`display_order`),
  KEY `task_groups_milestone_id_display_order_index` (`milestone_id`,`display_order`),
  CONSTRAINT `task_groups_milestone_id_foreign` FOREIGN KEY (`milestone_id`) REFERENCES `milestones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `task_groups_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'todo',
  `priority` varchar(20) NOT NULL DEFAULT 'medium',
  `due_date` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tasks_user_id_foreign` (`user_id`),
  KEY `tasks_project_id_foreign` (`project_id`),
  CONSTRAINT `tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE SET NULL,
  CONSTRAINT `tasks_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_login_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_login_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `result` enum('success','fail') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_login_logs_user_id_foreign` (`user_id`),
  CONSTRAINT `user_login_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_page_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_page_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned DEFAULT NULL,
  `route_name` varchar(100) DEFAULT NULL,
  `screen_name` varchar(80) DEFAULT NULL,
  `url` varchar(500) NOT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_page_logs_user_id_created_at_index` (`user_id`,`created_at`),
  KEY `user_page_logs_screen_name_created_at_index` (`screen_name`,`created_at`),
  KEY `user_page_logs_created_at_index` (`created_at`),
  CONSTRAINT `user_page_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `user_tour_visits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_tour_visits` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `tour_key` varchar(50) NOT NULL,
  `visited_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_tour_visits_user_id_tour_key_unique` (`user_id`,`tour_key`),
  KEY `user_tour_visits_tour_key_index` (`tour_key`),
  CONSTRAINT `user_tour_visits_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` enum('admin','manager','member','client') NOT NULL DEFAULT 'client',
  `is_sr_agent` tinyint(1) NOT NULL DEFAULT 0,
  `is_guest` tinyint(1) NOT NULL DEFAULT 0,
  `agent_status` varchar(20) NOT NULL DEFAULT 'offline',
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `company` varchar(255) DEFAULT NULL,
  `phone` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) NOT NULL,
  `remember_token` varchar(100) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_company_group_id_foreign` (`company_group_id`),
  CONSTRAINT `users_company_group_id_foreign` FOREIGN KEY (`company_group_id`) REFERENCES `company_groups` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_ai_call_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_ai_call_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `stage` enum('spec_generation','html_generation','regeneration') NOT NULL,
  `review_round` int(10) unsigned DEFAULT NULL,
  `internal_prompt_id` bigint(20) unsigned DEFAULT NULL,
  `primary_provider` enum('claude') NOT NULL DEFAULT 'claude',
  `fallback_used` tinyint(1) NOT NULL DEFAULT 0,
  `final_provider` enum('claude','openai','none') NOT NULL DEFAULT 'none',
  `status` enum('success','failed','cancelled') NOT NULL DEFAULT 'success',
  `primary_attempt_status` enum('success','timeout','rate_limit','content_filter','http_5xx','http_4xx','parse_error','cancelled','other') NOT NULL DEFAULT 'success',
  `primary_error_message` text DEFAULT NULL,
  `fallback_attempt_status` enum('success','timeout','rate_limit','content_filter','http_5xx','http_4xx','parse_error','cancelled','other') DEFAULT NULL,
  `fallback_error_message` text DEFAULT NULL,
  `prompt_tokens` int(10) unsigned DEFAULT NULL,
  `completion_tokens` int(10) unsigned DEFAULT NULL,
  `total_tokens` int(10) unsigned DEFAULT NULL,
  `estimated_cost_usd` decimal(10,4) DEFAULT NULL,
  `response_time_ms` int(10) unsigned DEFAULT NULL,
  `generated_html_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_ai_call_logs_internal_prompt_id_foreign` (`internal_prompt_id`),
  KEY `wb_ai_call_logs_task_id_created_at_index` (`task_id`,`created_at`),
  KEY `wb_ai_call_logs_final_provider_fallback_used_index` (`final_provider`,`fallback_used`),
  KEY `wb_ai_call_logs_status_index` (`status`),
  KEY `wb_ai_call_logs_generated_html_id_foreign` (`generated_html_id`),
  CONSTRAINT `wb_ai_call_logs_generated_html_id_foreign` FOREIGN KEY (`generated_html_id`) REFERENCES `wb_generated_html` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_ai_call_logs_internal_prompt_id_foreign` FOREIGN KEY (`internal_prompt_id`) REFERENCES `wb_internal_prompts` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_ai_call_logs_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_checklist_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_checklist_items` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `category` enum('html_structure','semantic','class_naming','design_tokens','typography','accessibility') NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `check_prompt_text` text NOT NULL,
  `added_from_task_id` bigint(20) unsigned DEFAULT NULL,
  `added_from_round` int(10) unsigned DEFAULT NULL,
  `added_at` timestamp NULL DEFAULT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_checklist_items_added_from_task_id_foreign` (`added_from_task_id`),
  KEY `wb_checklist_items_project_id_category_is_active_index` (`project_id`,`category`,`is_active`),
  CONSTRAINT `wb_checklist_items_added_from_task_id_foreign` FOREIGN KEY (`added_from_task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_checklist_items_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_generated_html`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_generated_html` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `review_round` int(10) unsigned NOT NULL DEFAULT 0,
  `generated_by` enum('claude','openai') NOT NULL DEFAULT 'claude',
  `ai_call_log_id` bigint(20) unsigned DEFAULT NULL,
  `html_content` longtext NOT NULL,
  `html_hash` char(64) NOT NULL,
  `source_html_id` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_generated_html_ai_call_log_id_foreign` (`ai_call_log_id`),
  KEY `wb_generated_html_source_html_id_foreign` (`source_html_id`),
  KEY `wb_generated_html_task_id_review_round_version_index` (`task_id`,`review_round`,`version`),
  KEY `wb_generated_html_html_hash_index` (`html_hash`),
  CONSTRAINT `wb_generated_html_ai_call_log_id_foreign` FOREIGN KEY (`ai_call_log_id`) REFERENCES `wb_ai_call_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_generated_html_source_html_id_foreign` FOREIGN KEY (`source_html_id`) REFERENCES `wb_generated_html` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_generated_html_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_html_integrity_logs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_html_integrity_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_session_id` bigint(20) unsigned NOT NULL,
  `generated_html_id` bigint(20) unsigned NOT NULL,
  `start_hash` char(64) NOT NULL,
  `end_hash` char(64) NOT NULL,
  `passed` tinyint(1) NOT NULL,
  `failure_reason` text DEFAULT NULL,
  `checked_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_html_integrity_logs_generated_html_id_foreign` (`generated_html_id`),
  KEY `wb_html_integrity_logs_review_session_id_index` (`review_session_id`),
  CONSTRAINT `wb_html_integrity_logs_generated_html_id_foreign` FOREIGN KEY (`generated_html_id`) REFERENCES `wb_generated_html` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_html_integrity_logs_review_session_id_foreign` FOREIGN KEY (`review_session_id`) REFERENCES `wb_review_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_internal_prompts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_internal_prompts` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `purpose` enum('spec_generation','html_generation','regeneration','reopen') NOT NULL,
  `review_round` int(10) unsigned DEFAULT NULL,
  `system_prompt` longtext DEFAULT NULL,
  `user_prompt` longtext NOT NULL,
  `payload_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`payload_metadata`)),
  `created_by` bigint(20) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_internal_prompts_created_by_foreign` (`created_by`),
  KEY `wb_internal_prompts_task_id_purpose_index` (`task_id`,`purpose`),
  CONSTRAINT `wb_internal_prompts_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_internal_prompts_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_layout_previews`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_layout_previews` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `task_options_id` bigint(20) unsigned DEFAULT NULL,
  `options_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_snapshot`)),
  `preview_svg` text DEFAULT NULL,
  `preview_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`preview_metadata`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_layout_previews_task_options_id_foreign` (`task_options_id`),
  KEY `wb_layout_previews_task_id_index` (`task_id`),
  CONSTRAINT `wb_layout_previews_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_layout_previews_task_options_id_foreign` FOREIGN KEY (`task_options_id`) REFERENCES `wb_task_options` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_ng_inputs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_ng_inputs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `review_session_id` bigint(20) unsigned NOT NULL,
  `review_round` int(10) unsigned NOT NULL,
  `highlights_snapshot` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`highlights_snapshot`)),
  `command_box` text DEFAULT NULL,
  `miss_description` text DEFAULT NULL,
  `attachments` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`attachments`)),
  `reported_by` bigint(20) unsigned NOT NULL,
  `processed_for_learning` tinyint(1) NOT NULL DEFAULT 0,
  `processed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_ng_inputs_review_session_id_foreign` (`review_session_id`),
  KEY `wb_ng_inputs_reported_by_foreign` (`reported_by`),
  KEY `wb_ng_inputs_task_id_review_round_index` (`task_id`,`review_round`),
  CONSTRAINT `wb_ng_inputs_reported_by_foreign` FOREIGN KEY (`reported_by`) REFERENCES `users` (`id`),
  CONSTRAINT `wb_ng_inputs_review_session_id_foreign` FOREIGN KEY (`review_session_id`) REFERENCES `wb_review_sessions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_ng_inputs_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_notification_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_notification_settings` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `stage_code` varchar(32) NOT NULL,
  `channel` enum('web','mobile_push') NOT NULL,
  `enabled` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wb_notification_settings_user_id_stage_code_channel_unique` (`user_id`,`stage_code`,`channel`),
  CONSTRAINT `wb_notification_settings_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_notifications` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `recipient_id` bigint(20) unsigned NOT NULL,
  `task_id` bigint(20) unsigned DEFAULT NULL,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `stage_code` varchar(32) NOT NULL,
  `review_round` int(10) unsigned DEFAULT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `deep_link` varchar(512) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `read_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_notifications_task_id_foreign` (`task_id`),
  KEY `wb_notifications_project_id_foreign` (`project_id`),
  KEY `wb_notifications_recipient_id_is_read_index` (`recipient_id`,`is_read`),
  KEY `wb_notifications_stage_code_index` (`stage_code`),
  CONSTRAINT `wb_notifications_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_notifications_recipient_id_foreign` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_notifications_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_output_packages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_output_packages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `file_size_bytes` bigint(20) unsigned NOT NULL DEFAULT 0,
  `package_hash` char(64) DEFAULT NULL,
  `included_html_id` bigint(20) unsigned DEFAULT NULL,
  `build_metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`build_metadata`)),
  `built_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_output_packages_included_html_id_foreign` (`included_html_id`),
  KEY `wb_output_packages_task_id_built_at_index` (`task_id`,`built_at`),
  CONSTRAINT `wb_output_packages_included_html_id_foreign` FOREIGN KEY (`included_html_id`) REFERENCES `wb_generated_html` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_output_packages_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_result_confirmations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_result_confirmations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `generated_html_id` bigint(20) unsigned NOT NULL,
  `decision` enum('regenerate','proceed_to_review') NOT NULL,
  `note` text DEFAULT NULL,
  `confirmed_by` bigint(20) unsigned NOT NULL,
  `confirmed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_result_confirmations_generated_html_id_foreign` (`generated_html_id`),
  KEY `wb_result_confirmations_confirmed_by_foreign` (`confirmed_by`),
  KEY `wb_result_confirmations_task_id_confirmed_at_index` (`task_id`,`confirmed_at`),
  CONSTRAINT `wb_result_confirmations_confirmed_by_foreign` FOREIGN KEY (`confirmed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `wb_result_confirmations_generated_html_id_foreign` FOREIGN KEY (`generated_html_id`) REFERENCES `wb_generated_html` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_result_confirmations_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_review_highlights`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_review_highlights` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `review_session_id` bigint(20) unsigned NOT NULL,
  `selector_path` varchar(512) NOT NULL,
  `tag_name` varchar(32) NOT NULL,
  `classes` varchar(512) DEFAULT NULL,
  `text_snippet` text DEFAULT NULL,
  `bbox_x` int(11) DEFAULT NULL,
  `bbox_y` int(11) DEFAULT NULL,
  `bbox_w` int(11) DEFAULT NULL,
  `bbox_h` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_review_highlights_review_session_id_index` (`review_session_id`),
  CONSTRAINT `wb_review_highlights_review_session_id_foreign` FOREIGN KEY (`review_session_id`) REFERENCES `wb_review_sessions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_review_sessions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_review_sessions` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `review_round` int(10) unsigned NOT NULL,
  `generated_html_id` bigint(20) unsigned NOT NULL,
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `decision` enum('ok','ng','pending') NOT NULL DEFAULT 'pending',
  `start_hash` char(64) DEFAULT NULL,
  `end_hash` char(64) DEFAULT NULL,
  `integrity_passed` tinyint(1) DEFAULT NULL,
  `reviewer_id` bigint(20) unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_review_sessions_generated_html_id_foreign` (`generated_html_id`),
  KEY `wb_review_sessions_reviewer_id_foreign` (`reviewer_id`),
  KEY `wb_review_sessions_task_id_review_round_index` (`task_id`,`review_round`),
  CONSTRAINT `wb_review_sessions_generated_html_id_foreign` FOREIGN KEY (`generated_html_id`) REFERENCES `wb_generated_html` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_review_sessions_reviewer_id_foreign` FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`),
  CONSTRAINT `wb_review_sessions_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_task_options`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_task_options` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `options_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`options_data`)),
  `version` int(10) unsigned NOT NULL DEFAULT 1,
  `is_current` tinyint(1) NOT NULL DEFAULT 1,
  `changed_by` bigint(20) unsigned DEFAULT NULL,
  `changed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_task_options_changed_by_foreign` (`changed_by`),
  KEY `wb_task_options_task_id_is_current_index` (`task_id`,`is_current`),
  KEY `wb_task_options_task_id_version_index` (`task_id`,`version`),
  CONSTRAINT `wb_task_options_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_task_options_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_task_steps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_task_steps` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_id` bigint(20) unsigned NOT NULL,
  `ai_call_log_id` bigint(20) unsigned DEFAULT NULL,
  `sequence` int(10) unsigned NOT NULL,
  `code` varchar(64) NOT NULL,
  `label` varchar(255) NOT NULL,
  `status` enum('pending','running','success','failed','skipped') NOT NULL DEFAULT 'pending',
  `context` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`context`)),
  `started_at` timestamp NULL DEFAULT NULL,
  `ended_at` timestamp NULL DEFAULT NULL,
  `duration_ms` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `wb_task_steps_task_id_sequence_index` (`task_id`,`sequence`),
  KEY `wb_task_steps_task_id_created_at_index` (`task_id`,`created_at`),
  KEY `wb_task_steps_ai_call_log_id_foreign` (`ai_call_log_id`),
  CONSTRAINT `wb_task_steps_ai_call_log_id_foreign` FOREIGN KEY (`ai_call_log_id`) REFERENCES `wb_ai_call_logs` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_task_steps_task_id_foreign` FOREIGN KEY (`task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `wb_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `wb_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `task_uuid` char(36) NOT NULL,
  `project_id` bigint(20) unsigned NOT NULL,
  `spec_reference_type` varchar(64) DEFAULT NULL,
  `spec_reference_id` bigint(20) unsigned DEFAULT NULL,
  `mode` enum('new','enhance') NOT NULL,
  `parent_task_id` bigint(20) unsigned DEFAULT NULL,
  `reopen_reason` enum('reopen','clone') DEFAULT NULL,
  `assignee_id` bigint(20) unsigned NOT NULL,
  `current_stage` varchar(32) NOT NULL DEFAULT 'draft',
  `status` enum('draft','in_progress','ai_calling','review','completed','cancelled') NOT NULL DEFAULT 'draft',
  `current_review_round` int(10) unsigned NOT NULL DEFAULT 0,
  `output_type` varchar(16) NOT NULL DEFAULT 'html',
  `total_ai_calls` int(10) unsigned NOT NULL DEFAULT 0,
  `total_tokens_used` bigint(20) unsigned NOT NULL DEFAULT 0,
  `total_cost_usd` decimal(10,4) NOT NULL DEFAULT 0.0000,
  `started_at` timestamp NULL DEFAULT NULL,
  `completed_at` timestamp NULL DEFAULT NULL,
  `cancelled_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wb_tasks_task_uuid_unique` (`task_uuid`),
  KEY `wb_tasks_project_id_status_index` (`project_id`,`status`),
  KEY `wb_tasks_parent_task_id_index` (`parent_task_id`),
  KEY `wb_tasks_assignee_id_status_index` (`assignee_id`,`status`),
  KEY `wb_tasks_spec_reference_type_spec_reference_id_index` (`spec_reference_type`,`spec_reference_id`),
  CONSTRAINT `wb_tasks_assignee_id_foreign` FOREIGN KEY (`assignee_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `wb_tasks_parent_task_id_foreign` FOREIGN KEY (`parent_task_id`) REFERENCES `wb_tasks` (`id`) ON DELETE SET NULL,
  CONSTRAINT `wb_tasks_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `weekly_ai_summaries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weekly_ai_summaries` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned DEFAULT NULL,
  `sr_company_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`sr_company_ids`)),
  `scope_key` varchar(200) DEFAULT NULL,
  `generated_by` bigint(20) unsigned NOT NULL,
  `summary_type` varchar(20) NOT NULL DEFAULT 'full',
  `week_start_date` date DEFAULT NULL COMMENT 'weekly 타입일 때만 사용',
  `range_start` date DEFAULT NULL,
  `range_end` date DEFAULT NULL,
  `content` longtext NOT NULL,
  `metrics` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metrics`)),
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weekly_ai_summaries_generated_by_foreign` (`generated_by`),
  KEY `wais_project_type_week_idx` (`project_id`,`summary_type`,`week_start_date`),
  KEY `idx_summaries_scope` (`scope_key`,`summary_type`,`week_start_date`),
  CONSTRAINT `weekly_ai_summaries_generated_by_foreign` FOREIGN KEY (`generated_by`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `weekly_ai_summaries_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `weekly_kpi_snapshots`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weekly_kpi_snapshots` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint(20) unsigned NOT NULL,
  `period_type` varchar(16) NOT NULL,
  `iso_week` varchar(16) DEFAULT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `sr_assigned` int(10) unsigned NOT NULL DEFAULT 0,
  `sr_completed` int(10) unsigned NOT NULL DEFAULT 0,
  `sr_reopened` int(10) unsigned NOT NULL DEFAULT 0,
  `sr_carried_over` int(10) unsigned NOT NULL DEFAULT 0,
  `weighted_throughput` decimal(8,2) NOT NULL DEFAULT 0.00,
  `avg_difficulty` decimal(3,2) DEFAULT NULL,
  `completion_rate` decimal(5,4) DEFAULT NULL,
  `avg_handling_days` decimal(5,2) DEFAULT NULL,
  `git_commits` int(10) unsigned NOT NULL DEFAULT 0,
  `git_added_loc` bigint(20) unsigned NOT NULL DEFAULT 0,
  `git_deleted_loc` bigint(20) unsigned NOT NULL DEFAULT 0,
  `git_files` int(10) unsigned NOT NULL DEFAULT 0,
  `sr_linked_commits` int(10) unsigned NOT NULL DEFAULT 0,
  `weekly_score_raw` decimal(6,2) DEFAULT NULL,
  `weekly_score` decimal(6,2) DEFAULT NULL,
  `penalty_raw` decimal(6,2) NOT NULL DEFAULT 0.00,
  `penalty_final` decimal(6,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_kpi_user_period` (`user_id`,`period_type`,`period_start`),
  KEY `idx_kpi_period` (`period_type`,`period_start`),
  CONSTRAINT `weekly_kpi_snapshots_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `weekly_report_tasks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weekly_report_tasks` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `weekly_report_id` bigint(20) unsigned NOT NULL,
  `section` enum('current_week','next_week') NOT NULL,
  `task_name` varchar(255) NOT NULL,
  `start_date` date DEFAULT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('completed','in_progress','pending','planned') NOT NULL DEFAULT 'pending',
  `original_data` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`original_data`)),
  `sort_order` tinyint(3) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `weekly_report_tasks_weekly_report_id_section_index` (`weekly_report_id`,`section`),
  CONSTRAINT `weekly_report_tasks_weekly_report_id_foreign` FOREIGN KEY (`weekly_report_id`) REFERENCES `weekly_reports` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
DROP TABLE IF EXISTS `weekly_reports`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `weekly_reports` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `project_id` bigint(20) unsigned NOT NULL,
  `user_id` bigint(20) unsigned NOT NULL,
  `company_group_id` bigint(20) unsigned DEFAULT NULL,
  `team_name` varchar(100) DEFAULT NULL,
  `author_name` varchar(100) NOT NULL,
  `manager_name` varchar(100) DEFAULT NULL,
  `report_date` date NOT NULL,
  `year` smallint(5) unsigned NOT NULL,
  `week_number` tinyint(3) unsigned NOT NULL,
  `week_start_date` date NOT NULL,
  `status` enum('draft','submitted') NOT NULL DEFAULT 'draft',
  `summary` longtext DEFAULT NULL,
  `special_notes` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wr_unique_user_week` (`project_id`,`user_id`,`week_start_date`),
  KEY `weekly_reports_user_id_foreign` (`user_id`),
  KEY `weekly_reports_project_id_week_start_date_index` (`project_id`,`week_start_date`),
  KEY `weekly_reports_company_group_id_index` (`company_group_id`),
  CONSTRAINT `weekly_reports_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `weekly_reports_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (1,'0001_01_01_000000_create_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (2,'0001_01_01_000001_create_cache_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (3,'0001_01_01_000002_create_jobs_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (4,'2026_04_22_064347_add_role_to_users_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (5,'2026_04_22_064348_create_projects_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (6,'2026_04_22_064349_create_project_members_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (7,'2026_04_22_064349_create_schedules_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (8,'2026_04_22_064350_create_questions_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (9,'2026_04_22_064352_create_answers_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (10,'2026_04_22_064353_create_project_files_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (11,'2026_04_22_064354_create_comments_table',1);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (12,'2026_04_22_100000_add_group_name_to_schedules_table',2);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (13,'2026_04_22_200000_create_conversations_table',3);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (14,'2026_04_22_200001_add_timestamps_to_conversation_user_table',4);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (15,'2026_04_22_200002_add_file_to_messages_table',5);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (16,'2026_04_22_200003_make_message_body_nullable',6);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (17,'2026_04_22_200004_add_group_to_conversations_table',7);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (18,'2026_04_22_300000_create_invitations_table',8);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (19,'2026_04_22_400000_create_teams_settings_table',9);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (20,'2026_04_22_500000_create_ai_settings_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (21,'2026_04_22_500001_create_figma_files_table',10);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (22,'2026_04_22_500002_create_ai_sessions_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (23,'2026_04_22_500003_create_ai_messages_table',11);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (24,'2026_04_23_100000_create_community_posts_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (25,'2026_04_23_100001_create_community_comments_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (26,'2026_04_23_100002_create_community_votes_table',12);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (27,'2026_04_23_200000_create_community_reactions_table',13);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (28,'2026_04_23_200000_create_desktop_tokens_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (29,'2026_04_23_200001_add_agent_status_to_users_table',14);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (30,'2026_04_23_300000_add_inquiry_to_conversations_table',15);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (31,'2026_04_23_400000_create_company_groups_table',16);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (32,'2026_04_23_400001_create_admin_users_table',17);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (33,'2026_04_23_400002_create_admin_access_tokens_table',18);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (34,'2026_04_23_400003_create_admin_invitations_table',19);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (35,'2026_04_23_400004_create_admin_login_logs_table',20);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (36,'2026_04_23_400005_create_admin_company_group_access_table',21);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (37,'2026_04_23_500000_add_company_group_id_to_users_table',22);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (38,'2026_04_23_500001_add_group_and_agent_to_conversations_table',23);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (39,'2026_04_23_600000_add_assigned_admin_id_to_conversations_table',24);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (40,'2026_04_23_700000_create_memos_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (41,'2026_04_23_800000_create_action_items_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (42,'2026_04_23_900000_create_tasks_table',25);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (43,'2026_04_24_000001_create_app_versions_table',26);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (44,'2026_04_24_100001_create_prompt_categories_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (45,'2026_04_24_100002_create_prompts_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (46,'2026_04_24_100003_create_prompt_executions_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (47,'2026_04_24_100004_create_execution_files_table',27);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (48,'2026_04_25_000001_add_company_group_id_to_shared_tables',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (49,'2026_04_25_000002_add_company_group_id_to_invitations',28);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (50,'2026_04_25_000003_add_ai_provider_fields',29);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (51,'2026_04_25_000005_add_sharing_to_ai_sessions',30);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (52,'2026_04_25_023345_add_source_to_prompts_table',31);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (53,'2026_04_25_023722_add_manus_to_ai_settings_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (54,'2026_04_25_023723_add_doc_fields_to_ai_messages_table',32);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (55,'2026_04_25_100000_create_meeting_minutes_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (56,'2026_04_25_100001_create_meeting_attendees_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (57,'2026_04_25_100002_create_meeting_memos_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (58,'2026_04_25_100003_create_meeting_action_items_table',33);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (59,'2026_04_25_000001_create_system_error_logs_table',34);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (60,'2026_04_25_080007_add_project_category_to_ai_sessions_table',35);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (61,'2026_04_25_200000_add_code_lang_to_ai_messages_table',36);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (62,'2026_04_25_210000_add_sort_order_to_schedules_table',37);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (63,'2026_04_25_220000_create_planning_docs_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (64,'2026_04_25_220001_create_planning_doc_histories_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (65,'2026_04_25_220002_create_planning_doc_inputs_table',38);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (66,'2026_04_25_100000_add_message_to_invitations_table',39);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (67,'2026_04_25_110000_add_project_ids_to_invitations_table',40);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (68,'2026_04_25_150000_create_file_comments_table',41);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (69,'2026_04_25_190000_add_converted_pdf_path_to_project_files',42);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (70,'2026_04_26_100001_add_agent_type_to_ai_sessions_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (71,'2026_04_26_100002_add_prompt_draft_to_ai_messages_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (72,'2026_04_26_200001_create_maintenance_screens_table',43);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (73,'2026_04_27_000001_create_file_annotations_table',44);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (74,'2026_04_27_100000_create_collab_sessions_table',45);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (75,'2026_04_27_200000_create_project_maintenances_table',46);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (76,'2026_04_27_200001_create_project_maintenance_replies_table',47);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (77,'2026_04_27_210000_add_dates_to_project_maintenances_table',48);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (78,'2026_04_27_220000_add_admin_user_id_to_project_maintenance_replies_table',49);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (79,'2026_04_26_000001_create_mobile_tokens_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (80,'2026_04_26_210000_create_activity_logs_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (81,'2026_04_26_310000_create_ai_project_files_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (82,'2026_04_26_000001_create_mobile_tokens_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (83,'2026_04_26_210000_create_activity_logs_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (84,'2026_04_26_310000_create_ai_project_files_table',99);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (85,'2026_04_27_300000_make_invited_by_nullable_in_invitations',100);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (86,'2026_04_27_000000_add_review_statuses_to_schedules_table',101);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (87,'2026_04_28_000001_create_user_login_logs_table',102);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (88,'2026_04_28_012135_create_user_page_logs_table',103);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (89,'2026_04_28_100000_add_parent_id_to_file_comments_table',104);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (90,'2026_04_28_212727_create_message_analyses_table',105);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (91,'2026_04_28_214230_create_message_image_comments_table',106);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (92,'2026_04_28_214811_create_message_image_annotations_table',107);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (93,'2026_04_29_102246_add_admin_user_id_to_message_image_comments',108);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (94,'2026_04_29_153141_add_url_fields_to_project_files',109);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (95,'2026_04_29_130000_add_reply_to_id_to_messages_table',110);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (96,'2026_04_29_200000_create_admin_user_project_table',111);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (97,'2026_04_29_210000_create_project_file_categories_table',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (98,'2026_04_29_210001_add_category_id_to_project_files',112);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (99,'2026_04_29_220000_add_share_token_to_project_files',113);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (100,'2026_04_29_230000_add_guest_name_to_file_comments_and_annotations',114);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (101,'2026_04_29_240000_make_user_id_nullable_on_file_annotations_and_comments',115);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (102,'2026_04_30_000001_normalize_annotation_data_to_percentage',116);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (103,'2026_04_30_100000_add_maintenance_id_to_project_files',117);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (104,'2026_04_29_220339_create_maintenance_file_categories_table',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (105,'2026_04_29_220340_add_maintenance_category_id_to_project_files',118);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (106,'2026_04_29_222050_create_maintenance_files_table',119);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (107,'2026_04_30_092740_add_features_to_company_groups_table',120);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (108,'2026_04_30_100548_make_maintenance_id_nullable_in_maintenance_files',121);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (109,'2026_04_30_000001_drop_project_file_fk_from_comments_and_annotations',122);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (110,'2026_04_30_500000_create_project_leaves_table',123);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (111,'2026_04_30_510000_add_approver_id_to_project_leaves_table',124);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (112,'2026_05_01_100000_create_project_file_review_requests_table',125);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (113,'2026_05_01_200000_add_translation_fields_to_messages_table',126);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (114,'2026_05_02_100000_add_schedule_id_to_project_files',127);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (115,'2026_05_02_130000_create_project_feature_suggestions_table',128);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (116,'2026_05_03_100000_create_weekly_reports_table',129);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (117,'2026_05_03_100001_create_weekly_report_tasks_table',130);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (118,'2026_05_03_120000_create_file_action_logs_table',131);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (119,'2026_05_03_100002_add_manager_name_to_weekly_reports',132);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (130,'2026_05_03_000001_create_ai_agent_project_stages_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (131,'2026_05_03_000002_create_ai_agent_artifacts_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (132,'2026_05_03_000003_create_ai_agent_artifact_versions_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (133,'2026_05_03_000004_create_ai_agent_requirements_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (134,'2026_05_03_000005_create_ai_agent_screens_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (135,'2026_05_03_000006_create_ai_agent_traceability_links_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (136,'2026_05_03_000007_create_ai_agent_prompts_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (137,'2026_05_03_000008_create_ai_agent_usage_logs_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (138,'2026_05_03_000009_create_ai_agent_approval_gates_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (139,'2026_05_03_000010_create_ai_agent_stack_standards_table',133);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (141,'2026_05_03_000011_create_ai_agent_project_configs_table',134);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (142,'2026_05_03_110000_add_gantt_fields_to_ai_agent_screens_table',135);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (144,'2026_05_03_120000_add_scope_to_ai_agent_artifacts_table',136);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (145,'2026_05_03_130000_create_ai_agent_artifact_files_table',137);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (146,'2026_05_03_140000_seed_as_is_analysis_prompt',138);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (147,'2026_05_03_160002_add_rationale_source_files_to_ai_agent_requirements',139);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (148,'2026_05_03_160003_seed_to_be_analysis_prompt',140);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (149,'2026_05_03_170000_expand_traceability_link_types',141);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (150,'2026_05_03_170001_create_ai_agent_gaps_table',142);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (151,'2026_05_03_170002_seed_gap_analysis_prompt',143);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (152,'2026_05_03_180000_create_ai_agent_planning_templates_table',144);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (153,'2026_05_03_180001_seed_planning_template_standard_v1',145);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (154,'2026_05_03_190000_seed_planning_document_prompts',146);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (155,'2026_05_03_200000_seed_ia_diagram_prompts',147);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (156,'2026_05_04_100000_seed_screen_prompt_generator_prompt',148);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (157,'2026_05_04_300000_seed_mockup_prompts',149);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (158,'2026_05_04_100000_create_requirements_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (159,'2026_05_04_100001_create_item_comments_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (160,'2026_05_04_100002_create_item_attachments_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (161,'2026_05_04_100003_create_item_watchers_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (162,'2026_05_04_100004_create_item_change_histories_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (163,'2026_05_04_100005_add_si_mode_to_projects_table',150);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (164,'2026_05_04_400000_create_ai_agent_user_credentials_table',151);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (165,'2026_05_04_200000_create_analysis_sessions_table',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (166,'2026_05_04_200001_create_analysis_session_files_table',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (167,'2026_05_04_200002_add_ai_fields_to_requirements_table',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (168,'2026_05_04_200003_add_llm_fields_to_projects_table',152);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (169,'2026_05_04_200004_create_plan_applications_table',153);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (170,'2026_05_04_200005_add_applied_to_plan_id_to_requirements_table',154);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (171,'2026_05_04_500000_create_issues_table',155);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (172,'2026_05_04_500001_add_sm_mode_to_projects_table',156);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (173,'2026_05_04_500002_add_converted_to_issue_id_to_questions_table',157);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (174,'2026_05_04_500000_add_figma_mapping_fields_to_ai_agent_screens_table',158);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (175,'2026_05_04_600001_create_milestones_table',159);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (176,'2026_05_04_600002_create_task_groups_table',160);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (177,'2026_05_04_600003_create_sub_tasks_table',161);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (178,'2026_05_04_600004_migrate_schedules_to_sub_tasks',162);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (179,'2026_05_04_600005_rename_schedules_to_legacy_schedules',163);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (180,'2026_05_04_600000_create_prompt_sessions_table',164);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (181,'2026_05_04_600001_create_prompt_histories_table',164);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (182,'2026_05_05_000001_add_provider_to_prompt_histories',164);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (183,'2026_05_04_000001_rename_task_id_to_schedule_id_in_prompt_tables',165);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (184,'2026_05_04_000002_fix_schedule_id_foreign_keys',166);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (185,'2026_05_04_153059_add_is_applied_to_project_feature_suggestions_table',167);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (186,'2026_05_04_161523_add_is_completed_to_plan_applications_table',168);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (187,'2026_05_04_700000_add_requirement_id_to_sub_tasks_table',169);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (188,'2026_05_04_800000_add_tracking_to_project_feature_suggestions_table',170);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (189,'2026_05_04_174947_add_sub_task_id_to_project_files',171);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (190,'2026_05_04_900000_add_soft_delete_to_project_feature_suggestions_table',172);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (191,'2026_05_04_000001_add_share_token_to_planning_docs',173);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (192,'2026_05_05_100000_create_memo_shares_table',174);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (193,'2026_05_05_100001_add_is_pinned_to_memo_shares_table',175);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (194,'2026_05_05_115745_fix_plan_applications_is_completed',176);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (195,'2026_05_05_200000_create_weekly_ai_summaries_table',177);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (196,'2026_05_05_300000_create_announcements_table',178);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (197,'2026_05_06_100000_create_project_urs_table',179);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (198,'2026_05_06_200000_create_deliverables_tables',180);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (199,'2026_05_06_300000_add_deliverable_approval_and_share',181);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (200,'2026_05_06_400000_create_deliverable_comments_table',182);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (201,'2026_05_07_100000_add_en_translation_to_deliverable_step_data',183);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (202,'2026_05_07_100000_add_reviewed_at_to_project_file_review_requests_table',184);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (203,'2026_05_08_100000_add_pb_columns_to_projects_table',185);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (204,'2026_05_08_100001_create_pb_workspaces_table',186);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (205,'2026_05_08_100002_create_pb_standard_assets_table',187);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (206,'2026_05_08_100003_create_pb_standard_candidates_table',188);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (207,'2026_05_08_100004_create_pb_builders_table',189);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (208,'2026_05_08_100005_create_pb_builder_versions_table',190);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (209,'2026_05_08_100006_create_pb_sequences_table',191);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (210,'2026_05_08_100007_create_pb_dependencies_table',192);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (211,'2026_05_08_100008_create_pb_templates_table',193);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (212,'2026_05_08_100009_create_pb_external_feedbacks_table',194);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (213,'2026_05_08_100010_create_pb_learning_patterns_table',195);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (214,'2026_05_08_100011_create_pb_change_audit_logs_table',196);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (215,'2026_05_08_100012_create_pb_user_preferences_table',197);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (216,'2026_05_08_100013_create_pb_wizard_sessions_table',198);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (217,'2026_05_08_000001_add_content_en_to_project_urs',199);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (218,'2026_05_12_100000_add_phone_to_invitations_table',200);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (219,'2026_05_13_100000_add_phone_to_admin_users_table',201);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (220,'2026_05_13_200000_add_video_time_to_file_comments',202);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (221,'2026_05_13_300000_create_discussions_tables',203);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (222,'2026_05_13_400000_add_share_token_to_discussion_comments',204);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (223,'2026_05_13_500000_add_discussion_date_to_discussions',205);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (224,'2026_05_13_600000_add_conclusion_to_discussions',206);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (225,'2026_05_13_700000_add_comments_summary_to_discussions',207);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (226,'2026_05_13_800000_create_companies_table',208);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (227,'2026_05_13_900000_unify_companies_into_company_groups',209);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (228,'2026_05_13_181642_create_system_settings_table',210);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (229,'2026_05_14_000000_create_file_comment_notifications_table',211);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (230,'2026_05_14_100000_add_source_file_comment_id_to_discussions',212);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (231,'2026_05_14_200000_add_discussion_reflection_to_planning_doc_inputs',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (232,'2026_05_14_210000_add_reflection_to_discussions',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (233,'2026_05_15_000001_add_blade_to_frontend_stack_enums',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (234,'2026_05_15_000010_create_ai_agent_sessions_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (235,'2026_05_15_000011_create_ai_figma_sources_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (236,'2026_05_15_000012_create_ai_figma_snapshots_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (237,'2026_05_15_000013_create_ai_analysis_steps_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (238,'2026_05_15_000014_create_ai_outputs_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (239,'2026_05_15_000015_create_ai_feedbacks_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (240,'2026_05_15_000016_create_ai_conflicts_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (241,'2026_05_15_000017_create_ai_confirmed_outputs_table',213);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (242,'2026_05_14_220000_create_file_versions_table',214);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (243,'2026_05_14_220100_add_resolved_to_file_comments',214);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (244,'2026_05_14_230000_add_converted_pdf_path_to_file_versions',215);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (245,'2026_05_14_210000_add_source_message_to_action_items_table',216);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (246,'2026_05_15_100000_add_guest_chat_columns',217);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (247,'2026_05_15_220000_create_quick_prompts_table',218);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (248,'2026_05_15_223000_create_prompt_suffixes_table',219);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (249,'2026_05_15_223100_add_applied_suffix_ids_to_quick_prompts_table',219);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (250,'2026_05_15_230000_create_deliverable_step_versions_table',220);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (251,'2026_05_15_230000_add_base_refined_prompt_to_quick_prompts_table',221);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (252,'2026_05_15_131750_create_meeting_recordings_table',222);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (253,'2026_05_15_300000_add_status_to_meeting_minutes_table',223);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (254,'2026_05_16_000000_create_deliverable_file_registrations_table',224);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (255,'2026_05_16_010000_add_frozen_at_version_to_file_comments',225);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (256,'2026_05_18_194734_create_device_tokens_table',226);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (258,'2026_05_19_100000_create_plan_do_acts_table',227);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (259,'2026_05_19_120000_add_version_to_file_annotations',228);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (260,'2026_05_19_130000_add_reflected_to_file_comments',229);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (261,'2026_05_19_140000_create_shared_files_tables',230);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (262,'2026_05_19_100000_create_sr_targets_and_decouple_maintenances',231);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (263,'2026_05_19_110000_add_is_sr_agent_to_users',232);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (264,'2026_05_20_080000_add_is_personal_to_shared_files',233);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (265,'2026_05_20_100000_create_ai_fix_jobs_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (266,'2026_05_20_120001_create_wb_tasks_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (267,'2026_05_20_120002_create_wb_task_options_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (268,'2026_05_20_120003_create_wb_layout_previews_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (269,'2026_05_20_120004_create_wb_generated_prompts_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (270,'2026_05_20_120005_create_wb_generated_html_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (271,'2026_05_20_120006_create_wb_result_confirmations_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (272,'2026_05_20_120007_create_wb_review_sessions_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (273,'2026_05_20_120008_create_wb_checklist_items_table',234);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (274,'2026_05_20_130001_create_wb_review_highlights_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (275,'2026_05_20_130002_create_wb_ng_inputs_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (276,'2026_05_20_130003_create_wb_html_integrity_logs_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (277,'2026_05_20_130004_create_wb_notifications_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (278,'2026_05_20_130005_create_wb_notification_settings_table',235);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (279,'2026_05_20_140000_add_applied_step_to_file_comments',236);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (280,'2026_05_20_140000_drop_prompt_builder_tables',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (281,'2026_05_20_140001_drop_legacy_wb_tables',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (282,'2026_05_20_140100_create_wb_tasks_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (283,'2026_05_20_140101_create_wb_task_options_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (284,'2026_05_20_140102_create_wb_layout_previews_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (285,'2026_05_20_140103_create_wb_internal_prompts_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (286,'2026_05_20_140104_create_wb_ai_call_logs_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (287,'2026_05_20_140105_create_wb_generated_html_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (288,'2026_05_20_140106_create_wb_result_confirmations_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (289,'2026_05_20_140107_create_wb_review_sessions_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (290,'2026_05_20_140108_create_wb_review_highlights_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (291,'2026_05_20_140109_create_wb_ng_inputs_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (292,'2026_05_20_140110_create_wb_checklist_items_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (293,'2026_05_20_140111_create_wb_output_packages_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (294,'2026_05_20_140112_create_wb_notifications_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (295,'2026_05_20_140113_create_wb_notification_settings_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (296,'2026_05_20_140114_create_wb_html_integrity_logs_table',237);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (297,'2026_05_20_150000_create_project_shared_files_table',238);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (298,'2026_05_21_090000_drop_ai_fix_jobs_approved_by_admin_id_fk',239);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (299,'2026_05_21_100000_polymorphic_ai_fix_jobs_approved_by',240);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (300,'2026_05_21_120000_create_wb_task_steps_table',241);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (301,'2026_05_21_120000_create_maint_menus_table',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (302,'2026_05_21_120001_create_maint_users_table',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (303,'2026_05_21_120002_create_maint_requests_table',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (304,'2026_05_21_120003_create_maint_request_notes_table',242);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (305,'2026_05_21_150000_add_company_group_id_to_maint_users',243);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (306,'2026_05_21_160000_add_manager_role_to_users',244);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (307,'2026_05_21_170000_add_company_group_id_to_maint_requests',245);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (308,'2026_05_22_090000_add_ai_summary_to_maint_requests',246);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (309,'2026_05_22_100000_add_paid_dev_to_maint_requests',247);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (310,'2026_05_22_110000_create_maint_request_image_annotations',248);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (311,'2026_05_22_110001_create_maint_request_image_comments',248);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (312,'2026_05_22_120000_consolidate_maint_request_statuses',249);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (313,'2026_05_22_120100_change_maint_request_status_to_varchar',250);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (314,'2026_05_22_120200_merge_critical_priority_into_urgent',251);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (315,'2026_05_22_120300_link_colo_maint_users_to_company_users',252);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (316,'2026_05_22_120400_assign_null_colo_user_to_agency',253);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (317,'2026_05_24_120000_create_user_tour_visits',254);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (318,'2026_05_24_130000_add_on_hold_to_sub_tasks_status',255);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (319,'2026_05_24_140000_create_sub_task_status_logs',256);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (320,'2026_05_24_150000_create_sub_task_assignees',257);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (321,'2026_05_24_160000_create_mailbox_messages',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (322,'2026_05_24_160001_create_mailbox_recipients',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (323,'2026_05_24_160002_create_mailbox_attachments',258);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (324,'2026_05_24_160000_nullify_agency_colo_user_in_sr',259);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (325,'2026_05_24_180000_add_image_map_to_deliverable_step_data',260);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (326,'2026_05_24_170000_add_parent_to_shared_file_categories',261);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (327,'2026_05_24_180000_add_scope_duplicate_to_requirements',262);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (328,'2026_05_24_190000_add_user_to_shared_file_categories',263);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (329,'2026_05_24_200000_add_uses_withworks_to_company_groups',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (330,'2026_05_24_210000_add_targeting_to_announcements',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (331,'2026_05_24_220000_make_sender_id_nullable_in_mailbox_messages',264);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (332,'2026_05_24_230000_create_git_commits_table',265);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (333,'2026_05_24_240000_extend_weekly_ai_summaries',265);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (334,'2026_05_24_250000_add_withworks_token_to_ai_settings',266);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (335,'2026_05_24_260000_create_project_git_links',267);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (336,'2026_05_24_270000_add_difficulty_to_git_commits',268);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (337,'2026_05_24_280000_add_files_json_to_git_commits',269);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (338,'2026_05_24_290000_add_path_prefix_to_project_git_links',270);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (339,'2026_05_24_300000_add_git_and_sr_fields_to_company_groups',271);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (340,'2026_05_24_310000_allow_sr_only_weekly_ai_summary',272);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (341,'2026_05_24_320000_add_branches_tracking_to_git_commits',273);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (342,'2026_05_24_330000_strip_origin_prefix_from_git_commits',274);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (343,'2026_05_25_010000_add_patch_id_to_git_commits',275);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (344,'2026_05_25_020000_add_difficulty_score_to_maint_requests',276);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (345,'2026_05_25_030000_add_assigned_at_reopen_to_maint_requests',277);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (346,'2026_05_25_040000_add_ai_review_to_maint_requests',278);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (347,'2026_05_25_040000_create_sr_difficulty_mappings',279);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (348,'2026_05_25_050000_extend_git_commits_for_kpi',280);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (349,'2026_05_25_060000_create_weekly_kpi_snapshots',281);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (350,'2026_05_25_050000_add_ai_classification_to_maint_requests',282);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (351,'2026_05_25_060000_add_parent_id_to_maint_request_notes',283);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (352,'2026_05_27_010000_create_maint_request_attachments',284);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (353,'2026_05_27_020000_add_gantt_sort_order_to_maint_requests',285);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (354,'2026_05_28_010000_drop_chat_community_teams_tables',286);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (355,'2026_05_28_100000_add_source_to_system_error_logs',287);
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES (356,'2026_06_05_120000_add_edit_delete_to_messages_table',288);
