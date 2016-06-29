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
   * Applicable automatic discounts.
   *
   * @var array
   */
  public $autoDiscounts = array();

  /**
   * Constructor.
   *
   * @param string $entity
   * @param int $entity_id
   * @param int $contact_id
   * @param string $code
   * @param bool $is_display_field_mode - ie are we trying to calculate whether it would be possible to find a discount cod
   */
  public function __construct($entity, $entity_id, $contact_id, $code, $is_display_field_mode) {
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
   * Get discounts that apply in this instance.
   */
  public function getDiscounts() {
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
   * Filter this discounts according to entity.
   */
  protected function filterDiscountByEntity() {
    $this->setEntityDiscounts();
  }

  /**
   * Filter discounts by auto-discount criteria.
   *
   * If any one of the criteria is not met for this contact then the discount
   * does not apply
   *
   * We can assume that the no-contact id situation is dealt with in that
   * our scenarios are
   * - no contact id but code - in which case we will already be filtered down to code
   * - no contact id, no code & 'is_display_field_mode' - ie. anonymous mode so we don't need to filter by contact
   * - no contact id, no code & is not is_display_field_mode' - ie we won't have populated discounts in construct
   * (saves a query)
   */
  protected function filterDiscountsByContact() {
    if (empty($this->contact_id)) {
      return;
    }
    $this->autoDiscounts = $this->entity_discounts;
    foreach ($this->entity_discounts as $discount_id => $discount) {
      if (empty($discount['autodiscount'])) {
        unset($this->autoDiscounts[$discount_id]);
      }
      else {
        foreach (array_keys($discount['autodiscount']) as $entity) {
          $additionalParams = array('contact_id' => $this->contact_id);
          $id = ($entity == 'contact') ? $this->contact_id : NULL;
          if (!$this->checkDiscountsByEntity($discount, $entity, $id, 'autodiscount', $additionalParams)) {
            unset($this->autoDiscounts[$discount_id]);
            continue;
          }
        }
      }
    }
  }

  /**
   * Check if the entity has applicable discounts.
   */
  protected function getEntityHasDiscounts() {
    $this->getDiscounts();
    if (!empty($this->entity_discounts)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Check if the discount field should be shown.
   */
  public function isShowDiscountCodeField() {
    if (!$this->getEntityHasDiscounts()) {
      return FALSE;
    }
    if (!empty($this->entity_discounts)) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Getter for autodiscount.
   */
  public function isAutoDiscount() {
    return $this->auto_discount_applies;
  }

  /**
   * Set available entity_discounts for the event or membership.
   */
  protected function setEntityDiscounts() {
    $this->entity_discounts = array();
    foreach ($this->discounts as $discount_id => $discount) {
      // WARNING! The previous attempted to improve performance in deciding when
      // the autoDiscount field should be displayed resulted in breakage.
      // See https://github.com/dlobo/org.civicrm.module.cividiscount/issues/145 before
      // attempting.
      if ($this->checkDiscountsByEntity($discount, $this->entity, $this->entity_id, 'filters')) {
        $this->entity_discounts[$discount_id] = $discount;
      }
    }
  }

  /**
   * Check if discount is applicable.
   *
   * We check the 'filters' to see if
   * 1) there are any filters for this entity type - no filter means NO
   * 2) there is an empty filter for this entity type - means 'any'
   * 3) the only filter is on id (in which case we will do a direct comparison
   * 4) there is an api filter
   *
   * @param array $discount
   * @param string $entity
   * @param int $id entity id
   * @param string $type 'filters' or autodiscount
   * @param array $additionalFilter e.g array('contact_id' => x) when looking at memberships
   *
   * @return bool
   */
  protected function checkDiscountsByEntity($discount, $entity, $id, $type, $additionalFilter = array()) {
    try {
      if (!isset($discount[$type][$entity])) {
        return FALSE;
      }
      if (empty($discount[$type][$entity])) {
        return TRUE;
      }

      if (count($discount[$type][$entity]) == 1 && CRM_Utils_Array::value('id', $discount[$type][$entity])) {
        // If this discount is only limited by specific entity (say, a specific
        // event and not an event type), we have the IDs already and don't need
        // to make an API call. Store the IDs in a structure like they would
        // have as the result of an API call.
        $ids = array('values' => array_flip($discount[$type][$entity]['id']['IN']));
      }
      else {
        $params = $discount[$type][$entity] + array_merge(array(
            'options' => array('limit' => 999999999),
            'return' => 'id',
          ), $additionalFilter);
        $ids = civicrm_api3($entity, 'get', $params);
      }

      if ($id) {
        return in_array($id, array_keys($ids['values']));
      }
      else {
        return !empty($ids['values']);
      }
    }
    catch (Exception $e) {
      return FALSE;
    }
  }

  /**
   * If a code is passed in we are going to unset any filters that don't match the code.
   *
   * @todo cividiscount ignore case is always true - it's obviously preparatory to allowing
   * case sensitive.
   *
   * @return array
   */
  protected function filterDiscountByCode() {
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
