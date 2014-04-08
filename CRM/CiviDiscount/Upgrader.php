<?php

/**
 * Collection of upgrade steps
 */
class CRM_CiviDiscount_Upgrader extends CRM_CiviDiscount_Upgrader_Base {
  /**
   * Example: Run a couple simple queries
   *
   * @return TRUE on success
   * @throws Exception
   *
   */
  public function upgrade_2201() {
    $this->ctx->log->info('Applying update 2201');
    CRM_Core_DAO::executeQuery('ALTER TABLE cividiscount_item ADD COLUMN discount_msg_enabled TINYINT(1) DEFAULT 0 AFTER is_active');
    CRM_Core_DAO::executeQuery('ALTER TABLE cividiscount_item ADD COLUMN discount_msg VARCHAR(255) AFTER discount_msg_enabled');
    return TRUE;
  }
  public function upgrade_2202() {
    $this->ctx->log->info('Applying update 2202');
    CRM_Core_DAO::executeQuery('ALTER TABLE cividiscount_item ADD COLUMN filters VARCHAR(255) AFTER discount_msg');
    return TRUE;
  }
}
