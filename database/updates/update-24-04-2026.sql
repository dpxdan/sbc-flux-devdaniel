SET FOREIGN_KEY_CHECKS=0;

ALTER TABLE `dids` ADD COLUMN `sip_profile_id` int NOT NULL DEFAULT 1 AFTER `rate_group`,ADD INDEX `idx_dids_sip_profile_id`(`sip_profile_id` ASC) USING BTREE;

CREATE OR REPLACE ALGORITHM = UNDEFINED VIEW `view_dids` AS SELECT
	`dids`.`id` AS `id`,
	`dids`.`number` AS `number`,
	`products`.`id` AS `reseller_product_id`,
	`dids`.`accountid` AS `account_id`,
	`products`.`reseller_id` AS `reseller_id`,
IF
	((
			`dids`.`parent_id` <> `products`.`created_by` 
			),(
		SELECT
			`dids`.`accountid` 
		FROM
			`products` `dids` 
		WHERE
			( `dids`.`id` > `products`.`id` ) 
		ORDER BY
			`dids`.`id` 
			LIMIT 1 
			),
		`dids`.`accountid` 
	) AS `buyer_accountid`,
	`dids`.`country_id` AS `country_id`,
	`dids`.`cost` AS `cost`,
	`dids`.`call_type` AS `call_type`,
	`dids`.`city` AS `city`,
	`dids`.`province` AS `province`,
	`dids`.`leg_timeout` AS `leg_timeout`,
	`dids`.`maxchannels` AS `maxchannels`,
	`dids`.`extensions` AS `extensions`,
	`dids`.`hg_type` AS `hg_type`,
	`dids`.`reverse_rate` AS `reverse_rate`,
	`dids`.`rate_group` AS `rate_group`,
	`dids`.`area_code` AS `area_code`,
	`dids`.`sip_profile_id`,
	(SELECT `name` FROM `sip_profiles` WHERE id=`dids`.`sip_profile_id` AND `sip_profiles`.`status`=0 LIMIT 1) AS sip_profile_name,
	`products`.`buy_cost` AS `buy_cost`,
	`products`.`setup_fee` AS `setup_fee`,
	`products`.`price` AS `price`,
	`products`.`billing_type` AS `billing_type`,
	`products`.`billing_days` AS `billing_days`,
	`products`.`id` AS `product_id`,
	`products`.`last_modified_date` AS `modified_date` 
FROM
	(
		`products`
		JOIN `dids` ON ((
				`dids`.`product_id` = `products`.`id` 
			))) 
WHERE
	( `products`.`status` = 0 ) 
ORDER BY
	`products`.`id`;
	
	
CREATE OR REPLACE ALGORITHM = UNDEFINED VIEW `view_dids_reseller` AS SELECT
		`d`.`id` AS `id`,
		`d`.`number` AS `number`,
		`rp`.`id` AS `reseller_product_id`,
		`rp`.`account_id` AS `account_id`,
		`rp`.`reseller_id` AS `reseller_id`,
	IF
		((
				`d`.`parent_id` <> `rp`.`account_id` 
				),(
			SELECT
				`subrpro`.`account_id` 
			FROM
				`reseller_products` `subrpro` 
			WHERE
				( `subrpro`.`id` > `rp`.`id` ) 
			ORDER BY
				`subrpro`.`id` 
				LIMIT 1 
				),
			`d`.`accountid` 
		) AS `buyer_accountid`,
		`d`.`country_id` AS `country_id`,
		`d`.`cost` AS `cost`,
		`d`.`call_type` AS `call_type`,
		`d`.`city` AS `city`,
		`d`.`province` AS `province`,
		`d`.`leg_timeout` AS `leg_timeout`,
		`d`.`maxchannels` AS `maxchannels`,
		`d`.`extensions` AS `extensions`,
		`d`.`reverse_rate` AS `reverse_rate`,
		`d`.`sip_profile_id`,
		(SELECT `name` FROM `sip_profiles` WHERE id=`d`.`sip_profile_id` AND `sip_profiles`.`status`=0 LIMIT 1) AS sip_profile_name,
		`rp`.`buy_cost` AS `buy_cost`,
		`rp`.`setup_fee` AS `setup_fee`,
		`rp`.`price` AS `price`,
		`rp`.`billing_type` AS `billing_type`,
		`rp`.`billing_days` AS `billing_days`,
		`rp`.`product_id` AS `product_id`,
		`rp`.`modified_date` AS `modified_date` 
	FROM
		(
			`reseller_products` `rp`
			JOIN `dids` `d` ON ((
					`d`.`product_id` = `rp`.`product_id` 
				))) 
	WHERE
		( `rp`.`is_optin` = 0 ) 
	ORDER BY
		`rp`.`account_id`;

SET FOREIGN_KEY_CHECKS=1;