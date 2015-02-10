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
class CRM_CiviDiscount_Utils {

  static function getEvents() {
    $events = array();
    //whether we only want this date range is arguable but it is broader than the one in the core function
    // which excluded events with no end date & events in progress
    // and did a lot of extra 'work' for no benefit
    // the one thing we've lost is a permission check - which potentially we could
    // add back - but preferably only on the admin flow
    // I didn't use the api as I'm working to support 4.4 at the moment & the api would need
    // to support sql based queries (& would be the right place to add back the permission check
    $query = "SELECT id, title
      FROM civicrm_event
      WHERE (is_template = 0 OR is_template IS NULL)
      AND (start_date > NOW() OR end_date > NOW() OR end_date IS NULL)
    ";
    $eventsResult = CRM_Core_DAO::executeQuery($query);
    while ($eventsResult->fetch()) {
      $events[$eventsResult->id] = $eventsResult->title;
    }
    return $events;
  }

  static function getPriceSets() {
    $values = self::getPriceSetsInfo();

    $priceSets = array();
    if (!empty($values)) {
      foreach ($values as $set) {
        $priceSets[$set['item_id']] = "{$set['ps_label']} :: {$set['pf_label']} :: {$set['item_label']}";
      }
    }
    return $priceSets;
  }

  static function getNestedPriceSets() {
    $values = self::getPriceSetsInfo();

    $priceSets = array();
    if (!empty($values)) {
      $currentLabel = NULL;
      $optGroup = 0;
      foreach ($values as $set) {
        // Quickform doesn't support optgroups so this uses a hack. @see js/Common.js in core
        if ($currentLabel !== $set['ps_label']) {
          $priceSets['crm_optgroup_' . $optGroup++] = $set['ps_label'];
        }
        $priceSets[$set['item_id']] = "{$set['pf_label']} :: {$set['item_label']}";
        $currentLabel = $set['ps_label'];
      }
    }
    return $priceSets;
  }

  static function getPriceSetsInfo($priceSetId = NULL) {
    $params = array();
    $psTableName = 'civicrm_price_set_entity';
    if ($priceSetId) {
      $additionalWhere = 'ps.id = %1';
      $params = array(1 => array($priceSetId, 'Positive'));
      if (CRM_Core_DAO::getFieldValue('CRM_Core_DAO_Discount', $priceSetId, 'id', 'price_set_id')) {
        $psTableName = 'civicrm_discount';
      }
    }
    else {
      $additionalWhere = 'ps.is_quick_config = 0';
    }

    $sql = "
SELECT    pfv.id as item_id,
          pfv.label as item_label,
          pf.label as pf_label,
          pfv.membership_type_id as membership_type_id,
          ps.title as ps_label
FROM      civicrm_price_field_value as pfv
LEFT JOIN civicrm_price_field as pf on (pf.id = pfv.price_field_id)
LEFT JOIN civicrm_price_set as ps on (ps.id = pf.price_set_id AND ps.is_active = 1)
INNER JOIN {$psTableName} as pse on (ps.id = pse.price_set_id)
WHERE  {$additionalWhere}
ORDER BY  pf_label, pfv.price_field_id, pfv.weight
";

    $dao = CRM_Core_DAO::executeQuery($sql, $params);
    $priceSets = array();
    while ($dao->fetch()) {
      $priceSets[$dao->item_id] = array(
        'item_id' => $dao->item_id,
        'item_label' => $dao->item_label,
        'pf_label' => $dao->pf_label,
        'ps_label' => $dao->ps_label,
        'membership_type_id' => $dao->membership_type_id
      );
    }

    return $priceSets;
  }

  /**
   * Sort of acts like array_intersect(). We want to match value of one array
   * with key of another to return the id and title for things like events, membership, etc.
   */
  static function getIdsTitles($ids = array(), $titles = array()) {
    $a = array();
    foreach ($ids as $k => $v) {
      if (!empty($titles[$v])) {
        $a[$v] = $titles[$v];
      }
    }

    return $a;
  }

  /**
   * check if price set is quick config price set, i.e for eg, if event is configured with default fee or
   * usiing price sets
   *
   * @param int $priceSetId price set id
   *
   * @return boolean true is it is quickconfig else false
   */
  static function checkForQuickConfigPriceSet($priceSetId) {
    if (
      version_compare(
        CRM_Utils_System::version(),
        '4.4'
      ) >= 0
    ) {
      if (CRM_Core_DAO::getFieldValue('CRM_Price_DAO_PriceSet', $priceSetId, 'is_quick_config')) {
        return TRUE;
      }
    }
    else {
      if (CRM_Core_DAO::getFieldValue('CRM_Price_DAO_Set', $priceSetId, 'is_quick_config')) {
        return TRUE;
      }
    }

    return FALSE;
  }

  /**
   * Function to generate a random discount code.
   *
   * @param string $chars Collection of characters to be used for the code.
   * @param int $len Length of the code
   *
   * @access public
   * @static
   * @return string New random discount code.
   */
  static function randomString($chars, $len) {
    $str = '';
    for ($i = 0; $i <= $len; $i++) {
      $max = strlen($chars) - 1;
      $num = floor(mt_rand() / mt_getrandmax() * $max);
      $temp = substr($chars, $num, 1);
      $str .= $temp;
    }

    return $str;
  }
}
