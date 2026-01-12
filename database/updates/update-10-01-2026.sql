SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `trunks` ADD COLUMN `check_carrier` enum('caller_id','destination_number','both','none') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'none' AFTER `sip_cid_type`;

INSERT INTO `cron_settings` (`id`, `name`, `command`, `exec_interval`, `creation_date`, `last_modified_date`, `last_execution_date`, `next_execution_date`, `status`, `file_path`) VALUES (NULL, 'Sync Portabilidade', 'hours', 1, '2025-08-20 14:50:48', '2025-08-20 14:54:55', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1, 'wget --no-check-certificate -O - -q {BASE_URL}Portabilidade_script/portabilidade');

INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'prefix_ported_number', 'Prefix Ported Numbers', '060', 'default_system_input', 'Enter prefix for ported numbers.', '2019-05-24 19:03:37', 0, 0, 'calls', 'General', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'check_ported_number', 'Check Ported Numbers', '1', 'enable_disable_option', 'Set enable to check ported number', '2019-05-24 19:03:37', 0, 0, 'calls', 'General', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'portabilidade_dbpass', 'Portabilidade DB Pass', '', 'default_system_input', 'Set portabilidade database password', '2019-05-24 19:03:37', 0, 0, 'database', 'Portabilidade', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'portabilidade_dbhost', 'Portabilidade DB Host', '', 'default_system_input', 'Set portabilidade database host', '2019-05-24 19:03:37', 0, 0, 'database', 'Portabilidade', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'portabilidade_dbuser', 'Portabilidade DB User', '', 'default_system_input', 'Set portabilidade database user', '2019-05-24 19:03:37', 0, 0, 'database', 'Portabilidade', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'portabilidade_dbname', 'Portabilidade DB Name', '', 'default_system_input', 'Set portabilidade database name', '2019-05-24 19:03:37', 0, 0, 'database', 'Portabilidade', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'portabilidade', 'Portabilidade', '1', 'enable_disable_option', 'Set enable to add portabilidade support', '2019-05-24 19:03:37', 0, 0, 'database', 'Portabilidade', '');
INSERT INTO `system` (`id`, `name`, `display_name`, `value`, `field_type`, `comment`, `timestamp`, `reseller_id`, `is_display`, `group_title`, `sub_group`, `field_rules`) VALUES (NULL, 'portabilidade_dbengine', 'Portabilidade DB Engine', 'MySQL', 'default_system_input', 'For now this must be MySQL', '2019-05-24 19:03:37', 0, 1, 'database', 'Portabilidade', '');


ALTER TABLE `cdrs` ADD COLUMN `caller_carrier_id` int NOT NULL DEFAULT 0 AFTER `call_id_cadup`;

ALTER TABLE `cdrs` ADD COLUMN `caller_call_id_cadup` bigint NOT NULL DEFAULT 0 AFTER `caller_carrier_id`;

ALTER TABLE `cdrs` ADD COLUMN `carrier_type` enum('caller_id','destination_number','both','none') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'none' AFTER `caller_call_id_cadup`;

ALTER TABLE `cdrs_staging` ADD COLUMN `caller_carrier_id` int NOT NULL DEFAULT 0 AFTER `call_id_cadup`;

ALTER TABLE `cdrs_staging` ADD COLUMN `caller_call_id_cadup` bigint NOT NULL DEFAULT 0 AFTER `caller_carrier_id`;

ALTER TABLE `cdrs_staging` ADD COLUMN `carrier_type` enum('caller_id','destination_number','both','none') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'none' AFTER `caller_call_id_cadup`;


DROP TRIGGER `cdr_records`;

DELIMITER //

CREATE DEFINER = `fluxuser`@`127.0.0.1` TRIGGER `cdr_records` AFTER INSERT ON `cdrs`
FOR EACH ROW
BEGIN
   INSERT INTO `cdrs_staging` (`uniqueid`, `accountid`, `type`, `sip_user`, `callerid`, `callednum`, `translated_dst`, `ct`, `billseconds`, `trunk_id`, `trunkip`, `callerip`, `disposition`, `callstart`, `debit`, `cost`, `provider_id`, `pricelist_id`, `package_id`, `pattern`, `notes`, `invoiceid`, `rate_cost`, `reseller_id`, `reseller_code`, `reseller_code_destination`, `reseller_cost`, `provider_code`, `provider_code_destination`, `provider_cost`, `provider_call_cost`, `call_direction`, `calltype`, `billmsec`, `answermsec`, `waitmsec`, `progress_mediamsec`, `flow_billmsec`, `is_recording`, `call_request`,  `carrier_id`, `call_id_cadup`, `caller_carrier_id`, `caller_call_id_cadup`, `carrier_type`, `country_id`, `end_stamp`)
   VALUES (NEW.uniqueid, NEW.accountid, NEW.type, NEW.sip_user, NEW.callerid, NEW.callednum, NEW.translated_dst, NEW.ct, NEW.billseconds, NEW.trunk_id, NEW.trunkip, NEW.callerip, NEW.disposition, NEW.callstart, NEW.debit, NEW.cost, NEW.provider_id, NEW.pricelist_id, NEW.package_id, NEW.pattern, NEW.notes, NEW.invoiceid, NEW.rate_cost, NEW.reseller_id, NEW.reseller_code, NEW.reseller_code_destination, NEW.reseller_cost, NEW.provider_code, NEW.provider_code_destination, NEW.provider_cost, NEW.provider_call_cost, NEW.call_direction, NEW.calltype, NEW.billmsec, NEW.answermsec, NEW.waitmsec, NEW.progress_mediamsec, NEW.flow_billmsec, NEW.is_recording, NEW.call_request, NEW.call_id_cadup, NEW.caller_carrier_id, NEW.caller_call_id_cadup, NEW.carrier_type,NEW.country_id,NEW.end_stamp);
END //

DELIMITER ;

SET FOREIGN_KEY_CHECKS=1;