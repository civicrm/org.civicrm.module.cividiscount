<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*/

/**
 * @package CiviDiscount
 */

require_once 'CRM/CiviDiscount/DAO/Item.php';

/**
 * Page for displaying discount code details
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
  function getBAOName() {
    return 'CRM_CiviDiscount_BAO_Item';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        CRM_Core_Action::UPDATE => array(
          'name' => ts('Edit'),
          'url' => 'civicrm/cividiscount/discount/edit',
          'qs' => '&id=%%id%%&reset=1',
          'title' => ts('Edit Discount')
        ),
        CRM_Core_Action::DISABLE => array(
          'name' => ts('Disable'),
          'class' => 'crm-enable-disable',
          'title' => ts('Disable Discount')
        ),
        CRM_Core_Action::ENABLE => array(
          'name' => ts('Enable'),
          'class' => 'crm-enable-disable',
          'title' => ts('Enable Discount')
        ),
        CRM_Core_Action::DELETE => array(
          'name' => ts('Delete'),
          'url' => 'civicrm/cividiscount/discount/delete',
          'qs' => '&id=%%id%%',
          'title' => ts('Delete Discount')
        )
      );
    }
    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  function editForm() {
    return 'CRM_CiviDiscount_Form_Item';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Discount Code';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = NULL) {
    return 'civicrm/cividiscount/discount';
  }

  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    require_once 'CRM/Utils/Rule.php';
    if (!CRM_Utils_Rule::positiveInteger($this->_id)) {
      CRM_Core_Error::fatal(ts('We need a valid discount ID for view'));
    }

    $this->assign('id', $this->_id);
    $defaults = array();
    $params = array('id' => $this->_id);

    require_once 'CRM/CiviDiscount/BAO/Item.php';
    CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);

    require_once 'CRM/CiviDiscount/BAO/Track.php';
    $rows = CRM_CiviDiscount_BAO_Track::getUsageByCode($this->_id);

    $this->assign('rows', $rows);
    $this->assign('code_details', $defaults);

    CRM_Utils_System::setTitle($defaults['code']);
  }

  function run() {
    $this->preProcess();
    return parent::run();
  }
}
