DROP TABLE IF EXISTS `cividiscount_track`;
DROP TABLE IF EXISTS `cividiscount_item`;

-- /*******************************************************
-- *
-- * cividiscount_item
-- *
-- * A discount entry.
-- *
-- *******************************************************/
CREATE TABLE `cividiscount_item` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Discount Item ID',
     `code` varchar(255) NOT NULL   COMMENT 'Discount Code.',
     `description` varchar(255) NOT NULL   COMMENT 'Discount Description.',
     `amount` varchar(255) NOT NULL   COMMENT 'Amount of discount either actual or percentage?',
     `amount_type` varchar(4) NOT NULL   COMMENT 'Type of discount, actual or percentage?',
     `count_max` int NOT NULL   COMMENT 'Max number of times this code can be used.',
     `count_use` int NOT NULL DEFAULT 0 COMMENT 'Number of times this code has been used.',
     `events` text    COMMENT 'Serialized list of events for which this code can be used',
     `pricesets` text    COMMENT 'Serialized list of pricesets for which this code can be used',
     `memberships` text    COMMENT 'Serialized list of memberships for which this code can be used',
     `autodiscount` text    COMMENT 'Some sort of autodiscounting mechanism?',
     `organization_id` int unsigned DEFAULT NULL COMMENT 'FK to Contact ID for the organization that originated this discount',
     `active_on` datetime DEFAULT NULL  COMMENT 'When is this discount activated?',
     `expire_on` datetime DEFAULT NULL  COMMENT 'When does this discount expire?',
     `is_active` tinyint    COMMENT 'Is this discount active?',
     `discount_msg_enabled` tinyint DEFAULT 0 COMMENT 'Is discount message is available for promotion?',
     `discount_msg` VARCHAR(255) COMMENT 'Discount message',
     `filters` varchar(255) COMMENT 'Discount Filters.',

    PRIMARY KEY ( `id` ),
     CONSTRAINT FK_cividiscount_item_organization_id FOREIGN KEY (`organization_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci;

-- /*******************************************************
-- *
-- * cividiscount_track
-- *
-- * Record how a discount was used.
-- *
-- *******************************************************/
CREATE TABLE `cividiscount_track` (
     `id` int unsigned NOT NULL AUTO_INCREMENT  COMMENT 'Discount Item ID',
     `item_id` int unsigned    COMMENT 'FK to Item ID of the discount code',
     `contact_id` int unsigned    COMMENT 'FK to Contact ID for the contact that used this discount',
     `used_date` datetime    COMMENT 'Date of use',
     `contribution_id` int unsigned    COMMENT 'FK to contribution table.',
     `entity_table` varchar(64) NOT NULL   COMMENT 'Name of table where item being referenced is stored',
     `entity_id` int unsigned NOT NULL   COMMENT 'Foreign key to the referenced item',
     `description` text    COMMENT 'Discount use description',

     PRIMARY KEY ( `id` ),

     CONSTRAINT FK_cividiscount_track_item_id FOREIGN KEY (`item_id`) REFERENCES `cividiscount_item`(`id`) ON DELETE SET NULL,
     CONSTRAINT FK_cividiscount_track_contact_id FOREIGN KEY (`contact_id`) REFERENCES `civicrm_contact`(`id`) ON DELETE SET NULL,
     CONSTRAINT FK_cividiscount_track_contribution_id FOREIGN KEY (`contribution_id`) REFERENCES `civicrm_contribution`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci  ;
