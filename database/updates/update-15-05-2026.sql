SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS `event_guard_logs` (
  `id`         int unsigned NOT NULL AUTO_INCREMENT,
  `log_uuid`   char(36) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hostname`   varchar(128) COLLATE utf8mb4_unicode_ci NOT NULL,
  `log_date`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `filter`     varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
  `ip_address` varchar(45) COLLATE utf8mb4_unicode_ci NOT NULL,
  `extension`  varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` varchar(512) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `country`    varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `failures`   int unsigned NOT NULL DEFAULT 1,
  `log_status` enum('blocked','unblocked','pending','tracking') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tracking',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_log_uuid`    (`log_uuid`),
         KEY `idx_ip_status`  (`ip_address`, `log_status`),
         KEY `idx_host_status`(`hostname`, `log_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_guard_whitelist` (
  `id`          int unsigned NOT NULL AUTO_INCREMENT,
  `cidr`        varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at`  datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cidr` (`cidr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `system` (`name`, `display_name`, `value`, `field_type`, `comment`, `is_display`, `group_title`, `sub_group`) VALUES
    ('block_threshold', 'Block Threshold', '5',  'default_system_input', 'Tentativas antes de bloquear',  0, 'calls', 'Event Guard'),
    ('whitelist_ttl',   'Whitelist TTL',   '30', 'default_system_input', 'TTL do cache da whitelist (s)', 0, 'calls', 'Event Guard'),
    ('reg_ttl',         'Reg TTL',         '60', 'default_system_input', 'TTL do cache de registros (s)', 0, 'calls', 'Event Guard');