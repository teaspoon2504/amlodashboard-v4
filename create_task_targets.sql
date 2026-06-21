CREATE TABLE IF NOT EXISTS `task_targets` (
  `id` int NOT NULL AUTO_INCREMENT,
  `task_template_id` int NOT NULL,
  `kanwil_id` int NOT NULL,
  `user_id` int DEFAULT NULL,
  `tahun` int NOT NULL,
  `bulan` int NOT NULL,
  `target_value` int NOT NULL DEFAULT 0,
  `created_by` int DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_target_kanwil` (`task_template_id`, `kanwil_id`, `tahun`, `bulan`, `user_id`),
  FOREIGN KEY (`task_template_id`) REFERENCES `task_templates` (`id`),
  FOREIGN KEY (`kanwil_id`) REFERENCES `kantor_wilayah` (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`created_by`) REFERENCES `users` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
