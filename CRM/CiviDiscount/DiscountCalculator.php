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
class CRM_CiviDiscount_DiscountCalculator {
  protected $entity;
  protected $entity_id;
  protected $discounts = array();
  protected $contact_id;
  protected $code;
  protected $entity_discounts;
  protected $is_display_field_mode;
  protected $auto_discount_applies;

  /**
   * Constructor
   * @param string $entity
   * @param integer $entity_id
   * @param integer $contact_id
   * @param string $code
   * @param boolean $is_anonymous - ie are we trying to calculate whether it would be possible to find a discount cod
   */
  function __construct($entity, $entity_id, $contact_id, $code, $is_display_field_mode) {
    if (empty($code) && empty($contact_id) && !$is_display_field_mode) {
      $this->discounts = array();
    }
    else {
      $this->discounts = CRM_CiviDiscount_BAO_Item::getValidDiscounts();
    }
    $this->entity = $entity;
    $this->contact_id = $contact_id;
    $this->entity_id = $entity_id;
    $this->code = trim($code);
    $this->is_display_field_mode = $is_display_field_mode;
  }

  /**
   * Get discounts that apply in this instance
   */
  function getDiscounts() {
    if (!empty($this->code)) {
      $this->filterDiscountByCode();
    }
    $this->filterDiscountByEntity();
    if (!$this->is_display_field_mode) {
      $this->filterDiscountsByContact();
    }
    return $this->entity_discounts;
  }

  /**
   * filter this discounts according to entity
   */
  function filterDiscountByEntity() {
    $this->setEntityDiscounts();
  }

  /**
   * Filter discounts by autodiscount criteria. If any one of the criteria is not met for this contact then the discount
   * does not apply
   *
   * We can assume that the no-contact id situation is dealt with in that
   * our scenarios are
   * - no contact id but code - in which case we will already be filtered down to code
   * - no contact id, no code & 'is_display_field_mode' - ie. anonymous mode so we don't need to filter by contact
   * - no contact id, no code & is not is_display_field_mode' - ie we won't have populated discounts in construct
   * (saves a query)
   */
  function filterDiscountsByContact() {
    if (empty($this->contact_id)) {
      return;
    }
    $entityDiscounts = $this->entity_discounts;
    foreach ($this->entity_discounts as $discount_id => $discount) {
      if (empty($discount['autodiscount'])) {
        if (!empty($discount['memberships'])) {
          $applyForMembershipOnly = TRUE;
          continue;
        }
        unset($entityDiscounts[$discount_id]);
      }
      else {
        foreach (array_keys($discount['autodiscount']) as $entity) {
          $additionalParams = array('contact_id' => $this->contact_id);
          $id = ($entity == 'contact') ? $this->contact_id : NULL;
          if (!$this->checkDiscountsByEntity($discount, $entity, $id, 'autodiscount', $additionalParams)) {
            unset($entityDiscounts[$discount_id]);
            continue;
          }
        }
      }
    }
    if (empty($applyForMembershipOnly) && $this->entity != 'membership') {
      $this->autoDiscounts = $entityDiscounts;
    }
  }

  /**
   * get discounts relative to the entity
   */
  function getEntityDiscounts() {
    if (is_array($this->entity_discounts)) {
      return $this->entity_discounts;
    }
    $this->setEntityDiscounts();
    return $this->entity_discounts;
  }

  /**
   * get discounts relative to the entity
   */
  function getEntityHasDiscounts() {
    $this->getDiscounts();
    if (!empty($this->entity_discounts)) {
      return TRUE;
    }
  }

  /**
   * get discounts relative to the entity
   */
  function isShowDiscountCodeField() {
    if (!$this->getEntityHasDiscounts()) {
      return FALSE;
    }
    if (!empty($this->entity_discounts)) {
      return TRUE;
    }
  }

  /**
   * getter for autodiscount
   */
  function isAutoDiscount() {
    return $this->auto_discount_applies;
  }

  /**
   * Filter out discounts that are not applicable based on id or other filters
   * @param array $discounts discount array from db
   * @param string $entity - this should match the api entity
   * @param integer $id entity id
   * @param string $type 'filters' or autodiscount
   * @param array $additionalFilter e.g array('contact_id' => x) when looking at memberships
   */
  function setEntityDiscounts() {
    $this->entity_discounts = array();
    //since we cannot choose online contribution page as criteria for creating discount code so
    //we need to bypass the check for entity=membership
    if ($this->entity == 'membership') {
      $this->entity_discounts = $this->discounts;
    }
    foreach ($this->discounts as $discount_id => $discount) {
      if ($this->entity == 'membership' && !empty($discount['autodiscount'])) {
        unset($this->entity_discounts[$discount_id]);
      }
      if ($this->checkDiscountsByEntity($discount, $this->entity, $this->entity_id, 'filters')) {
        $this->entity_discounts[$discount_id] = $discount;
      }
    }
  }

  /**
   * Check if discount is applicable - we check the 'filters' to see if
   * 1) there are any filters for this entity type - no filter means NO
   * 2) there is an empty filter for this entity type - means 'any'
   * 3) the only filter is on id (in which case we will do a direct comparison
   * 4) there is an api filter
   *
   * @param array $discounts discount array from db
   * @param string $field - this should match the api entity
   * @param integer $id entity id
   * @param string $type 'filters' or autodiscount
   * @param array $additionalFilter e.g array('contact_id' => x) when looking at memberships
   */
  function checkDiscountsByEntity($discount, $entity, $id, $type, $additionalFilter = array()) {
    try {
      if (!isset($discount[$type][$entity])) {
        return FALSE;
      }
      if (empty($discount[$type][$entity])) {
        return TRUE;
      }

      $params = $discount[$type][$entity] + array_merge(array(
          'options' => array('limit' => 999999999),
          'return' => 'id'
        ), $additionalFilter);
      $ids = civicrm_api3($entity, 'get', $params);
      if ($id) {
        return in_array($id, array_keys($ids['values']));
      }
      else {
        return !empty($ids['values']);
      }
    } catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * If a code is passed in we are going to unset any filters that don't match the code
   * @todo cividiscount ignore case is always true - it's obviously preparatory to allowing
   * case sensitive
   * @return unknown|boolean|Ambigous <mixed, array>
   */
  function filterDiscountByCode() {
    if (_cividiscount_ignore_case()) {
      foreach ($this->discounts as $id => $discount) {
        if (strcasecmp($this->code, $discount['code']) != 0) {
          unset($this->discounts[$id]);
        }
      }
    }
    return $this->discounts;
  }
}
