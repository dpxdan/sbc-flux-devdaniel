CREATE TABLE IF NOT EXISTS `event_guard_logs` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `log_uuid`    CHAR(36)      NOT NULL,
    `hostname`    VARCHAR(128)  NOT NULL,
    `log_date`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `filter`      VARCHAR(64)   NOT NULL,
    `ip_address`  VARCHAR(45)   NOT NULL,
    `extension`   VARCHAR(255)      NULL DEFAULT NULL,
    `user_agent`  VARCHAR(512)      NULL DEFAULT NULL,
    `log_status`  ENUM('blocked','unblocked','pending') NOT NULL DEFAULT 'blocked',
    PRIMARY KEY (`id`),
    UNIQUE  KEY `uq_log_uuid`     (`log_uuid`),
            KEY `idx_ip_status`   (`ip_address`, `log_status`),
            KEY `idx_host_status` (`hostname`, `log_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `event_guard_whitelist` (
    `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    `cidr`        VARCHAR(50)   NOT NULL,
    `description` VARCHAR(255)      NULL DEFAULT NULL,
    `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_cidr` (`cidr`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


INSERT INTO `menu_modules` (`id`, `menu_label`, `module_name`, `module_url`, `menu_title`, `menu_image`, `menu_subtitle`, `priority`) VALUES
    (NULL, 'Blocked IPs', 'event_guard', 'event_guard/event_guard_list/',           'Switch', 'Live-Report.png', 'Event Guard', 101.8);
UPDATE `userlevels` SET `module_permissions` = concat(`module_permissions`, ',', (SELECT max(`id`) FROM `menu_modules`)) WHERE `userlevelid` IN (-1, 1, 0);

INSERT INTO `menu_modules` (`id`, `menu_label`, `module_name`, `module_url`, `menu_title`, `menu_image`, `menu_subtitle`, `priority`) VALUES
    (NULL, 'Allowed IPs', 'event_guard', 'event_guard/event_guard_whitelist_list/', 'Switch', 'Live-Report.png', 'Event Guard', 101.9);
UPDATE `userlevels` SET `module_permissions` = concat(`module_permissions`, ',', (SELECT max(`id`) FROM `menu_modules`)) WHERE `userlevelid` IN (-1, 1, 0);


INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `is_display`, `group_title`, `sub_group`) VALUES
    (NULL, 'esl_host',     'ESL Host',     '127.0.0.1', 'default_system_input', 'FreeSWITCH Event Socket host',               0, 'calls', 'Event Guard'),
    (NULL, 'esl_port',     'ESL Port',     '8021',      'default_system_input', 'FreeSWITCH Event Socket port',               0, 'calls', 'Event Guard'),
    (NULL, 'esl_password', 'ESL Password', 'ClueCon',   'default_system_input', 'FreeSWITCH Event Socket password',           0, 'calls', 'Event Guard'),
    (NULL, 'hostname',     'Hostname',     '',          'default_system_input', 'Hostname', 0, 'calls', 'Event Guard');