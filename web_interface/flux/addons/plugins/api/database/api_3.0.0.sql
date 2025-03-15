DROP TABLE IF EXISTS `api_endpoints`;
CREATE TABLE `api_endpoints` (
  `id` int NOT NULL AUTO_INCREMENT,
  `endpoint_name` varchar(255) DEFAULT NULL,
  `endpoint_url` varchar(255) DEFAULT NULL,
  `accountid` int NOT NULL DEFAULT '0',
  `reseller_id` int NOT NULL DEFAULT '0',
  `endpoint_auth` enum('basic','password','token','oauth') CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL DEFAULT 'basic',
  `partner_id` int NOT NULL DEFAULT '0',
  `endpoint_user` varchar(50) DEFAULT NULL,
  `endpoint_password` varchar(100) DEFAULT NULL,
  `endpoint_token` varchar(200) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `creation_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `last_modified_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `api_partners`;
CREATE TABLE `api_partners` (
  `id` int NOT NULL AUTO_INCREMENT,
  `partner_name` varchar(255) DEFAULT NULL,
  `partner_url` varchar(255) DEFAULT NULL,
  `accountid` int NOT NULL DEFAULT '0',
  `reseller_id` int NOT NULL DEFAULT '0',
  `partner_auth` enum('basic','password','token','oauth') DEFAULT 'basic',
  `partner_user` varchar(50) DEFAULT NULL,
  `partner_password` varchar(100) DEFAULT NULL,
  `partner_token` varchar(200) DEFAULT NULL,
  `status` tinyint(1) NOT NULL DEFAULT '0',
  `last_login_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `creation_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  `last_modified_date` datetime NOT NULL DEFAULT '1000-01-01 00:00:00',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO `api_partners` (`id`,`partner_name`,`partner_url`,`accountid`,`reseller_id`,`partner_auth`,`partner_user`,`partner_password`,`partner_token`,`status`,`last_login_date`,`creation_date`,`last_modified_date`) VALUES (1,'ixc-provider','https://ixc.com',0,0,'basic',NULL,NULL,NULL,0,'2025-03-02 00:55:35','2025-03-02 00:00:00','2025-03-02 00:00:00'); 
INSERT INTO `api_partners` (`id`,`partner_name`,`partner_url`,`accountid`,`reseller_id`,`partner_auth`,`partner_user`,`partner_password`,`partner_token`,`status`,`last_login_date`,`creation_date`,`last_modified_date`) VALUES (2,'piperun','https://api.pipe.run/v1',0,0,'basic',NULL,NULL,NULL,0,'2025-03-02 00:55:35','2025-03-02 00:00:00','2025-03-02 00:00:00'); 
INSERT INTO `api_partners` (`id`,`partner_name`,`partner_url`,`accountid`,`reseller_id`,`partner_auth`,`partner_user`,`partner_password`,`partner_token`,`status`,`last_login_date`,`creation_date`,`last_modified_date`) VALUES (3,'xpro','https://xpro.me',0,0,'basic',NULL,NULL,NULL,0,'2025-03-02 00:55:35','2025-03-02 00:00:00','2025-03-02 00:00:00'); 
INSERT INTO `api_partners` (`id`,`partner_name`,`partner_url`,`accountid`,`reseller_id`,`partner_auth`,`partner_user`,`partner_password`,`partner_token`,`status`,`last_login_date`,`creation_date`,`last_modified_date`) VALUES (4,'movidesk','https://movidesk.com',0,0,'basic',NULL,NULL,NULL,0,'2025-03-02 00:55:35','2025-03-02 00:00:00','2025-03-02 00:00:00'); 
INSERT INTO `api_partners` (`id`,`partner_name`,`partner_url`,`accountid`,`reseller_id`,`partner_auth`,`partner_user`,`partner_password`,`partner_token`,`status`,`last_login_date`,`creation_date`,`last_modified_date`) VALUES (5,'nectar','https://app.nectarcrm.com.br',0,0,'basic',NULL,NULL,NULL,0,'2025-03-02 00:55:35','2025-03-02 00:00:00','2025-03-02 00:00:00'); 
INSERT INTO `api_partners` (`id`,`partner_name`,`partner_url`,`accountid`,`reseller_id`,`partner_auth`,`partner_user`,`partner_password`,`partner_token`,`status`,`last_login_date`,`creation_date`,`last_modified_date`) VALUES (6,'zendesk','https://subdomain.zendesk.com',0,0,'basic',NULL,NULL,NULL,0,'2025-03-02 00:55:35','2025-03-02 00:00:00','2025-03-02 00:00:00');


INSERT INTO `menu_modules` (`id`, `menu_label`, `module_name`, `module_url`, `menu_title`,`menu_image`, `menu_subtitle`, `priority`) VALUES(NULL, 'API Endpoints', 'api', 'api_endpoints/api_endpoints_list/', 'Services','', '0', 59.1);

UPDATE userlevels SET module_permissions = concat( module_permissions, ',', (  SELECT max( id ) FROM menu_modules WHERE module_url = 'api_endpoints/api_endpoints_list/' ) ) WHERE userlevelid = -1;

INSERT INTO `cron_settings` (`name`, `command`, `exec_interval`, `creation_date`, `last_modified_date`, `last_execution_date`, `next_execution_date`, `status`, `file_path`) VALUES ( 'API Endpoints', 'days', '1', UTC_TIMESTAMP(), UTC_TIMESTAMP(),'0000-00-00 00:00:00','0000-00-00 00:00:00', '1', 'wget --no-check-certificate -q -O- {BASE_URL}ApiEndpoints/index');

