<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CDM/DAO/Item.php';

class CDM_BAO_Item extends CDM_DAO_Item {

  /**
   * class constructor
   */
  function __construct() {
    parent::__construct();
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params   (reference) an assoc array of name/value pairs
   * @param array $defaults (reference) an assoc array to hold the flattened values
   *
   * @return object CDM_BAO_Item object on success, null otherwise
   * @access public
   * @static
   */
  static function retrieve(&$params, &$defaults) {
    $item = new CDM_DAO_Item();
    $item->copyValues($params);
    if ($item->find(true)) {
      CRM_Core_DAO::storeValues($item, $defaults);
      return $item;
    }
    return null;
  }

  static function getValidDiscounts() {
    $codes = array();

    $sql = "
SELECT  id,
    code,
    description,
    amount,
    amount_type,
    events,
    pricesets,
    memberships,
    autodiscount,
    expire_on,
    active_on,
    is_active,
    count_use,
    count_max
FROM    cividiscount_item
";
    $dao =& CRM_Core_DAO::executeQuery($sql, array());
    while ($dao->fetch()) {
      $a = (array) $dao;
      if (CDM_BAO_Item::isValid($a)) {
        $codes[$a['code']] = $a;
      }
    }

    return $codes;
  }

  /**
   * update the is_active flag in the db
   *
   * @param int      $id        id of the database record
   * @param boolean  $is_active value we want to set the is_active field
   *
   * @return Object             DAO object on sucess, null otherwise
   *
   * @access public
   * @static
   */
  static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CDM_DAO_Item', $id, 'is_active', $is_active);
  }


  static function incrementUsage($id) {
    $sql = "UPDATE cividiscount_item SET count_use = count_use+1 WHERE id = {$id}";
    return CRM_Core_DAO::executeQuery($sql);
  }

  static function decrementUsage($id) {
    $sql = "UPDATE cividiscount_item SET count_use = count_use-1 WHERE id = {$id}";
    return CRM_Core_DAO::executeQuery($sql);
  }

  static function isValid($code) {
    if (!CDM_BAO_Item::isExpired($code) &&
      CDM_BAO_Item::isActive($code) &&
      CDM_BAO_Item::isEnabled($code) &&
      ($code['count_max'] == 0 || $code['count_max'] > $code['count_use'])
    ) {
      return TRUE;
    }

    return FALSE;
  }

  static function isExpired($code) {
    if (empty($code['expire_on'])) {
      return FALSE;
    }

    $time = CRM_Utils_Date::getToday(null, 'Y-m-d H:i:s');

    if (strtotime($time) > abs(strtotime($code['expire_on']))) {
      return TRUE;
    }

    return FALSE;
  }


  static function isActive($code) {
    if (empty($code['active_on'])) {
      return TRUE;
    }

    $time = CRM_Utils_Date::getToday(null, 'Y-m-d H:i:s');

    if (strtotime($time) > abs(strtotime($code['active_on']))) {
      return TRUE;
    }

    return FALSE;
  }


  static function isEnabled($code) {
    if ($code['is_active'] == 1) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Function to delete discount codes
   *
   * @param  int  $itemID     ID of the discount code to be deleted.
   *
   * @access public
   * @static
   * @return true on success else false
   */
  static function del($itemID) {
    require_once 'CRM/Utils/Rule.php';
    if (! CRM_Utils_Rule::positiveInteger($itemID)) {
      return false;
    }

    require_once 'CDM/DAO/Item.php';
    $item = new CDM_DAO_Item();
    $item->id = $itemID;
    $item->delete();

    return true;
  }
}
