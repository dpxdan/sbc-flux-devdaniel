ALTER TABLE `flux`.`trunks` 
ADD COLUMN `instant_ringback` tinyint(1) NOT NULL DEFAULT 1 AFTER `sip_cid_type`;