<?php

/**
 * @package CiviDiscount
 */
class CRM_CiviDiscount_BAO_Item extends CRM_CiviDiscount_DAO_Item {

  /**
   * Takes an associative array and creates a discount item
   *
   * This function extracts all the params it needs to initialize the created
   * discount item. The params array could contain additional unused name/value
   * pairs
   *
   * @param array $params (reference ) an assoc array of name/value pairs
   *
   * @return object CRM_CiviDiscount_BAO_Item object
   * @access public
   * @static
   */
  public static function add(&$params) {
    $item = new CRM_CiviDiscount_DAO_Item();
    $item->code = $params['code'];
    $item->description = $params['description'];
    $item->amount = $params['amount'];
    $item->amount_type = $params['amount_type'];
    $item->count_max = $params['count_max'];
    $item->discount_msg = $params['discount_msg'];
    $item->filters = json_encode($params['filters']);
    $item->autodiscount = json_encode($params['autodiscount']);
    foreach ($params['multi_valued'] as $mv => $dontCare) {
      if (!empty($params[$mv])) {
        $item->$mv = CRM_Core_DAO::serializeField($params[$mv], CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND);
      }
      else {
        $item->$mv = 'null';
      }
    }

    if (!empty($params['id'])) {
      $item->id = $params['id'];
    }

    $item->is_active = CRM_Utils_Array::value('is_active', $params) ? 1 : 0;
    $item->discount_msg_enabled = CRM_Utils_Array::value('discount_msg_enabled', $params) ? 1 : 0;

    if (!empty($params['active_on'])) {
      $item->active_on = CRM_Utils_Date::processDate($params['active_on']);
    }
    else {
      $item->active_on = 'null';
    }

    $item->membership_new = CRM_Utils_Array::value('membership_new', $params) ? 1 : 0;
    $item->membership_renew = CRM_Utils_Array::value('membership_renew', $params) ? 1 : 0;

    if (!empty($params['expire_on'])) {
      $item->expire_on = CRM_Utils_Date::processDate($params['expire_on']);
    }
    else {
      $item->expire_on = 'null';
    }

    if (!empty($params['organization_id'])) {
      $item->organization_id = $params['organization_id'];
    }
    else {
      $item->organization_id = 'null';
    }

    $id = empty($params['id']) ? NULL : $params['id'];
    $op = $id ? 'edit' : 'create';
    CRM_Utils_Hook::pre($op, 'CiviDiscount', $id, $params);
    $item->save();
    CRM_Utils_Hook::post($op, 'CiviDiscount', $item->id, $item);

    return $item;
  }

  /**
   * Takes a bunch of params that are needed to match certain criteria and
   * retrieves the relevant objects. Typically the valid params are only
   * contact_id. We'll tweak this function to be more full featured over a period
   * of time. This is the inverse function of create. It also stores all the retrieved
   * values in the default array
   *
   * @param array $params (reference) an assoc array of name/value pairs
   * @param array $defaults (reference) an assoc array to hold the flattened values
   *
   * @return object CRM_CiviDiscount_BAO_Item object on success, null otherwise
   * @access public
   * @static
   */
  public static function retrieve(&$params, &$defaults) {
    $item = new CRM_CiviDiscount_DAO_Item();
    $item->copyValues($params);
    if ($item->find(TRUE)) {
      CRM_Core_DAO::storeValues($item, $defaults);
      return $item;
    }
    return NULL;
  }

  public static function getValidDiscounts() {
    static $discounts = [];
    static $hasRun = FALSE;
    if ($hasRun) {
      //not checking if empty discounts as could be legitimately empty
      return $discounts;
    }
    $hasRun = TRUE;

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
    discount_msg_enabled,
    discount_msg,
    count_use,
    count_max,
    filters, membership_new, membership_renew
  FROM cividiscount_item AS i
  WHERE is_active = 1
  AND (active_on IS NULL OR active_on <= NOW())
  AND (expire_on IS NULL OR expire_on > NOW())
  AND (count_max = 0 OR count_max > count_use)
";
    $dao = CRM_Core_DAO::executeQuery($sql, []);
    while ($dao->fetch()) {
      $a = (array) $dao;
      $discounts[$a['code']] = self::buildDiscountFilters($a);
    }
    return $discounts;
  }

  /**
   * interpret filter values for return array
   * We are building one array out of 2 storage mechanisms - the json array in the filters field & the
   * hex(01) separated fields event, price_set & membership. Arguably these second type of fields should be dumped
   * & moved to filters as they are not easily searchable anyway
   *
   * We convert 'memberships' to membership_type_id as that is what the filter applies to
   *
   * We build an array that is effectively $entity => $params for api
   * Note that if the filter is 'any' (e.g any event) then we return $entity=> array() to achieve an
   * unfiltered api call
   * @param array $discount
   */
  public static function buildDiscountFilters($discount) {
    $filters = json_decode($discount['filters'], TRUE);
    // Expand set-valued fields.
    $fields = [
      'events' => 'event',
      'pricesets' => 'price_set',
      'memberships' => 'membership_type'
    ];
    foreach ($fields as $field => $entity) {
      if (!isset($discount[$field]) || is_null($discount[$field])) {
        $items = [];
      }
      else {
        $items = explode(CRM_Core_DAO::VALUE_SEPARATOR, trim($discount[$field], CRM_Core_DAO::VALUE_SEPARATOR));
        if (!empty($items)) {
          if (!isset($filters[$entity])) {
            $filters[$entity] = [];
          }
          //0 indicates 'any' so for 0 we construct an empty filter - otherwise we add a limit by id clause
          //note that this may be combined with stored filters e.g. 'event_type_id'
          if (!in_array(0, $items)) {
            $filters[$entity]['id'] = ['IN' => $items];
          }
        }
      }
      $discount[$field] = !empty($items) ? array_combine($items, $items) : [];
    }
    $discount['filters'] = empty($filters) ? [] : $filters;
    $discount['autodiscount'] = json_decode($discount['autodiscount'], TRUE);
    return $discount;
  }

  /**
   * Update the is_active flag in the db
   *
   * @param int $id
   * @param bool $is_active
   *
   * @return bool
   */
  public static function setIsActive($id, $is_active) {
    return CRM_Core_DAO::setFieldValue('CRM_CiviDiscount_DAO_Item', $id, 'is_active', $is_active);
  }


  public static function incrementUsage($id) {
    $sql = "UPDATE cividiscount_item SET count_use = count_use+1 WHERE id = {$id}";
    return CRM_Core_DAO::executeQuery($sql);
  }

  public static function decrementUsage($id) {
    $sql = "UPDATE cividiscount_item SET count_use = count_use-1 WHERE id = {$id}";
    return CRM_Core_DAO::executeQuery($sql);
  }

  /**
   * Function to delete discount codes
   *
   * @param  int $itemID ID of the discount code to be deleted.
   *
   * @access public
   * @static
   * @return true on success else false
   */
  public static function del($itemID) {
    $item = new CRM_CiviDiscount_DAO_Item();
    $item->id = $itemID;

    if ($item->find(TRUE)) {
      $params = ['id' => $itemID];
      CRM_Utils_Hook::pre('delete', 'CiviDiscount', $item->id, $params);
      $item->delete();
      CRM_Utils_Hook::post('delete', 'CiviDiscount', $itemID, $item);
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Function to copy discount codes
   *
   * @param  int $itemID ID of the discount code to be copied.
   *
   * @access public
   * @static
   * @return true on success else false
   */
  public static function copy($itemID, $params, $newCode) {
    $item = new CRM_CiviDiscount_DAO_Item();
    $item->id = $itemID;

    if ($item->find(TRUE)) {
      unset($item->id);
      $item->count_use = 0;
      $item->code = $newCode;
      if (isset($item->description) && $item->description != '') {
        $item->description = 'Copy of ' . $item->description;
      }

      CRM_Utils_Hook::pre('create', 'CiviDiscount', NULL, $params);
      $item->save();
      CRM_Utils_Hook::post('create', 'CiviDiscount', $item->id, $item);
      return TRUE;
    }

    return FALSE;
  }

}
