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
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('cividiscount_item', 'discount_msg_enabled')) {
      $this->ctx->log->info('Skipped cividiscount update 2201.  Column discount_msg_enabled already present on cividiscount_item table.');
    }
    else {
      $this->ctx->log->info('Applying cividiscount update 2201.  Adding discount_msg_enabled to the cividiscount_item table.');
      CRM_Core_DAO::executeQuery('ALTER TABLE cividiscount_item ADD COLUMN discount_msg_enabled TINYINT(1) DEFAULT 0 AFTER is_active');
    }
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('cividiscount_item', 'discount_msg')) {
      $this->ctx->log->info('Skipped cividiscount update 2201.  Column discount_msg already present on cividiscount_item table.');
    }
    else {
      $this->ctx->log->info('Applying cividiscount update 2201.  Adding discount_msg to the cividiscount_item table.');
      CRM_Core_DAO::executeQuery('ALTER TABLE cividiscount_item ADD COLUMN discount_msg VARCHAR(255) AFTER discount_msg_enabled');
    }
    return TRUE;
  }

  public function upgrade_2202() {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists('cividiscount_item', 'filters')) {
      $this->ctx->log->info('Skipped cividiscount update 2202.  Column filters already present on cividiscount_item table.');
    }
    else {
      $this->ctx->log->info('Applying cividiscount update 2202.  Adding filters to the cividiscount_item table.');
      CRM_Core_DAO::executeQuery('ALTER TABLE cividiscount_item ADD COLUMN filters VARCHAR(255)');
    }
    return TRUE;
  }

  public function upgrade_2203() {
    $sql = "SELECT id, autodiscount FROM cividiscount_item WHERE autodiscount IS NOT NULL";
    $dao = CRM_Core_DAO::executeQuery($sql);
    while ($dao->fetch()) {
      $discount = json_encode(array('membership' => array('membership_type_id' => array('IN' => explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($dao->autodiscount, CRM_Core_DAO::VALUE_SEPARATOR))))));
      CRM_Core_DAO::executeQuery("UPDATE cividiscount_item SET autodiscount = %1 WHERE id = %2", array(
        1 => array($discount, 'String'),
        2 => array($dao->id, 'Integer')
      ));
    }
    return TRUE;
  }
}