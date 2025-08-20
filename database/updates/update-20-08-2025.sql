SET FOREIGN_KEY_CHECKS=0;

DELETE FROM `cron_settings` WHERE `file_path` LIKE '%voip/%';

INSERT INTO `cron_settings` (`id`, `name`, `command`, `exec_interval`, `creation_date`, `last_modified_date`, `last_execution_date`, `next_execution_date`, `status`, `file_path`) VALUES (NULL, 'Get Sync', 'minutes', 10, '2025-08-20 14:50:48', '2025-08-20 14:54:55', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'wget --no-check-certificate -O - -q {BASE_URL}ApiSync/sync/');

INSERT INTO `cron_settings` (`id`, `name`, `command`, `exec_interval`, `creation_date`, `last_modified_date`, `last_execution_date`, `next_execution_date`, `status`, `file_path`) VALUES (NULL, 'Get Cities', 'days', 1, '2025-08-20 14:51:49', '2025-08-20 14:52:55', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'wget --no-check-certificate -O - -q {BASE_URL}ApiSync/sync_locations/');

INSERT INTO `cron_settings` (`id`, `name`, `command`, `exec_interval`, `creation_date`, `last_modified_date`, `last_execution_date`, `next_execution_date`, `status`, `file_path`) VALUES (NULL, 'Get CDRs', 'minutes', 5, '2025-08-20 14:52:19', '2025-08-20 16:21:55', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'wget --no-check-certificate -O - -q {BASE_URL}ApiSync/sync_cdrs/');

INSERT INTO `cron_settings` (`id`, `name`, `command`, `exec_interval`, `creation_date`, `last_modified_date`, `last_execution_date`, `next_execution_date`, `status`, `file_path`) VALUES (NULL, 'Get Plans', 'hours', 1, '2025-08-20 14:52:44', '2025-08-20 16:21:59', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'wget --no-check-certificate -O - -q {BASE_URL}ApiSync/sync_voip_plans/');

SET FOREIGN_KEY_CHECKS=1;