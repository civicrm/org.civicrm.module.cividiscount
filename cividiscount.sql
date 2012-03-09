CREATE TABLE `cividiscount_discount` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `code` varchar(255) NOT NULL DEFAULT '',
  `description` varchar(255) NOT NULL DEFAULT '',
  `amount` varchar(255) NOT NULL DEFAULT '',
  `amount_type` char(1) NOT NULL DEFAULT '',
  `count_max` int(10) unsigned NOT NULL,
  `count_use` int(10) unsigned NOT NULL,
  `events` longtext,
  `pricesets` longtext,
  `memberships` longtext,
  `organization` int(10) unsigned NOT NULL,
  `autodiscount` longtext,
  `expiration` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `code` (`code`)
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;

CREATE TABLE `cividiscount_track` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `discount_id` int unsigned NOT NULL COMMENT 'FK to cividiscount_discount entry'
  `contact_id` int unsigned NOT NULL COMMENT 'FK to civicrm_contact'
  `timestamp` int(11) NOT NULL DEFAULT '0',
  `track` longtext,
  `contribution_id` int unsigned DEFAULT NULL COMMENT 'Optional FK to civicrm_contribution'
  `object_id` int(10) unsigned NOT NULL,
  `object_type` longtext,
  PRIMARY KEY ( `id` ),
  CONSTRAINT FK_cividiscount_track_discount_id FOREIGN KEY (`discount_id`) REFERENCES `cividiscount_discount`(`id`) ON DELETE SET NULL,
  CONSTRAINT FK_cividiscount_track_contact_id  FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL,      
  CONSTRAINT FK_cividiscount_track_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE CASCADE  
)  ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
