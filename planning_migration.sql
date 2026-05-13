-- 기획서 기능 테이블 생성 SQL
-- 서버에서 실행: php artisan migrate 또는 아래 SQL 직접 실행

CREATE TABLE IF NOT EXISTS `planning_docs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `project_id` BIGINT UNSIGNED NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `description` TEXT NULL,
  `content` LONGTEXT NULL,
  `pending_content` LONGTEXT NULL,
  `ai_summary` TEXT NULL,
  `ai_conflicts` TEXT NULL,
  `ai_suggestions` TEXT NULL,
  `version` INT UNSIGNED NOT NULL DEFAULT 1,
  `status` ENUM('draft','ai_processed','pending_review','approved','rejected') NOT NULL DEFAULT 'draft',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `planning_docs_project_id_index` (`project_id`),
  CONSTRAINT `planning_docs_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE,
  CONSTRAINT `planning_docs_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`),
  CONSTRAINT `planning_docs_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `planning_doc_histories` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `planning_doc_id` BIGINT UNSIGNED NOT NULL,
  `version` INT UNSIGNED NOT NULL,
  `change_type` ENUM('user_add','user_edit','ai_integrate','ai_suggest','approved','rejected') NOT NULL,
  `before_content` LONGTEXT NULL,
  `after_content` LONGTEXT NULL,
  `summary` TEXT NULL,
  `changed_by` BIGINT UNSIGNED NOT NULL,
  `approval_status` ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `approved_by` BIGINT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `planning_doc_histories_doc_id_index` (`planning_doc_id`),
  CONSTRAINT `pdh_doc_id_foreign` FOREIGN KEY (`planning_doc_id`) REFERENCES `planning_docs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pdh_changed_by_foreign` FOREIGN KEY (`changed_by`) REFERENCES `users` (`id`),
  CONSTRAINT `pdh_approved_by_foreign` FOREIGN KEY (`approved_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `planning_doc_inputs` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `planning_doc_id` BIGINT UNSIGNED NOT NULL,
  `input_type` ENUM('text','memo','requirement','file') NOT NULL,
  `content` TEXT NULL,
  `file_path` VARCHAR(255) NULL,
  `file_name` VARCHAR(255) NULL,
  `status` ENUM('pending','processed') NOT NULL DEFAULT 'pending',
  `created_by` BIGINT UNSIGNED NOT NULL,
  `processed_at` TIMESTAMP NULL,
  `created_at` TIMESTAMP NULL,
  `updated_at` TIMESTAMP NULL,
  PRIMARY KEY (`id`),
  KEY `planning_doc_inputs_doc_id_index` (`planning_doc_id`),
  CONSTRAINT `pdi_doc_id_foreign` FOREIGN KEY (`planning_doc_id`) REFERENCES `planning_docs` (`id`) ON DELETE CASCADE,
  CONSTRAINT `pdi_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- migrations 테이블에 기록 (선택사항)
INSERT IGNORE INTO `migrations` (`migration`, `batch`) VALUES
  ('2026_04_25_220000_create_planning_docs_table', 99),
  ('2026_04_25_220001_create_planning_doc_histories_table', 99),
  ('2026_04_25_220002_create_planning_doc_inputs_table', 99);
