<?php

use CRM_CiviDiscount_ExtensionUtil as E;

/**
 * Page for displaying list of discount codes
 * @package CiviDiscount
 */
class CRM_CiviDiscount_Page_List extends CRM_Core_Page_Basic {
  public $useLivePageJS = TRUE;

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
        CRM_Core_Action::VIEW => [
          'name' => E::ts('View'),
          'url' => 'civicrm/cividiscount/discount/view',
          'qs' => 'id=%%id%%&reset=1',
          'title' => E::ts('View Discount Code')
        ],
        CRM_Core_Action::UPDATE => [
          'name' => E::ts('Edit'),
          'url' => 'civicrm/cividiscount/discount/edit',
          'qs' => '&id=%%id%%&reset=1',
          'title' => E::ts('Edit Discount Code')
        ],
        CRM_Core_Action::COPY => [
          'name' => E::ts('Copy'),
          'url' => 'civicrm/cividiscount/discount/copy',
          'qs' => '&cloneID=%%id%%&reset=1',
          'title' => E::ts('Clone Discount Code')
        ],
        CRM_Core_Action::DISABLE => [
          'name' => E::ts('Disable'),
          'class' => 'crm-enable-disable',
          'title' => E::ts('Disable Discount Code')
        ],
        CRM_Core_Action::ENABLE => [
          'name' => E::ts('Enable'),
          'class' => 'crm-enable-disable',
          'title' => E::ts('Enable Discount Code')
        ],
        CRM_Core_Action::DELETE => [
          'name' => E::ts('Delete'),
          'url' => 'civicrm/cividiscount/discount/delete',
          'qs' => '&id=%%id%%',
          'title' => E::ts('Delete Discount Code')
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
    return E::ts('Discount Code');
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/cividiscount/discount';
  }

}
