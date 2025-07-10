SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `api_endpoints` 
ADD COLUMN `run_cron` tinyint(1) NOT NULL DEFAULT 1 AFTER `apply_on_endpoints`;

SET FOREIGN_KEY_CHECKS=1;