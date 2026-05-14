SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `dids` ADD COLUMN `bypass_media` tinyint(1) NOT NULL DEFAULT 1 COMMENT '0 enable 1 for disable' AFTER `last_modified_date`;

SET FOREIGN_KEY_CHECKS=1;

