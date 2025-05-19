ALTER TABLE `flux`.`cdrs` 
ADD COLUMN `block_billseconds` INT NOT NULL DEFAULT '0' AFTER `billseconds`;

ALTER TABLE `flux`.`reseller_cdrs` 
ADD COLUMN `block_billseconds` INT NOT NULL DEFAULT '0' AFTER `billseconds`;
