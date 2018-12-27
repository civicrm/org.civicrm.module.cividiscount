<?php

use CRM_CiviDiscount_ExtensionUtil as E;

/**
 * Page for displaying discount code details
 * @package CiviDiscount
 */
class CRM_CiviDiscount_Page_Report extends CRM_Core_Page {
  /**
   * The id of the discount code
   *
   * @var int
   */
  protected $_id;

  protected $_multiValued = NULL;

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_CiviDiscount_BAO_Item';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => E::ts('Edit'),
          'url' => 'civicrm/cividiscount/discount/edit',
          'qs' => '&id=%%id%%&reset=1',
          'title' => E::ts('Edit Discount')
        ],
        CRM_Core_Action::DISABLE => [
          'name' => E::ts('Disable'),
          'class' => 'crm-enable-disable',
          'title' => E::ts('Disable Discount')
        ],
        CRM_Core_Action::ENABLE => [
          'name' => E::ts('Enable'),
          'class' => 'crm-enable-disable',
          'title' => E::ts('Enable Discount')
        ],
        CRM_Core_Action::DELETE => [
          'name' => E::ts('Delete'),
          'url' => 'civicrm/cividiscount/discount/delete',
          'qs' => '&id=%%id%%',
          'title' => E::ts('Delete Discount')
        ]
      ];
    }
    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  public function editForm() {
    return 'CRM_CiviDiscount_Form_Item';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  public function editName() {
    return 'Discount Code';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/cividiscount/discount';
  }

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if (!CRM_Utils_Rule::positiveInteger($this->_id)) {
      CRM_Core_Error::fatal('We need a valid discount ID for view');
    }

    $this->assign('id', $this->_id);
    $defaults = [];
    $params = ['id' => $this->_id];

    CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);

    $rows = CRM_CiviDiscount_BAO_Track::getUsageByCode($this->_id);

    $this->assign('rows', $rows);
    $this->assign('code_details', $defaults);

    CRM_Utils_System::setTitle($defaults['code']);
  }

  public function run() {
    $this->preProcess();
    return parent::run();
  }

}
