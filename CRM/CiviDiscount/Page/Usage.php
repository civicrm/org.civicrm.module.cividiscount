<?php

/**
 * @package CiviDiscount
 * Page for displaying discount code details
 */
class CRM_CiviDiscount_Page_Usage extends CRM_Core_Page {
  /**
   * The id of the discount code
   *
   * @var int
   */
  protected $_id;

  public function preProcess() {

    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE);
    $oid = CRM_Utils_Request::retrieve('oid', 'Positive', $this, FALSE);

    if ($oid) {
      $this->_id = CRM_Utils_Request::retrieve('oid', 'Positive', $this, FALSE);
    }
    else {
      $this->assign('hide_contact', TRUE);
      $this->_id = $cid;
    }

    if (!CRM_Utils_Rule::positiveInteger($this->_id)) {
      CRM_Core_Error::fatal('We need a valid discount ID for view');
    }

    $this->assign('id', $this->_id);
    $defaults = [];
    $params = ['id' => $this->_id];

    CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);

    if ($cid) {
      $rows = CRM_CiviDiscount_BAO_Track::getUsageByContact($this->_id);
    }
    else {
      $rows = CRM_CiviDiscount_BAO_Track::getUsageByOrg($this->_id);
    }

    $this->assign('rows', $rows);
    $this->assign('code_details', $defaults);

    $this->ajaxResponse['tabCount'] = count($rows);

    if (!empty($defaults['code'])) {
      CRM_Utils_System::setTitle($defaults['code']);
    }
  }

  public function run() {
    $this->preProcess();
    return parent::run();
  }

}
