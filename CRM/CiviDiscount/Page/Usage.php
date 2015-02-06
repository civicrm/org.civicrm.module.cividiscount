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
class CRM_CiviDiscount_Page_Usage extends CRM_Core_Page {
  /**
   * The id of the discount code
   *
   * @var int
   */
  protected $_id;

  function preProcess() {

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
    $defaults = array();
    $params = array('id' => $this->_id);

    require_once 'CRM/CiviDiscount/BAO/Item.php';
    CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);

    require_once 'CRM/CiviDiscount/BAO/Track.php';
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

  function run() {
    $this->preProcess();
    return parent::run();
  }
}
