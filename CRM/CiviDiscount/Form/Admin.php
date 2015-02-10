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

/**
 * This class generates form components for cividiscount administration.
 *
 */
class CRM_CiviDiscount_Form_Admin extends CRM_Admin_Form {
  protected $_multiValued = NULL;
  protected $_orgID = NULL;
  protected $_cloneID = NULL;

  protected $select2style = array();

  function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $this->_cloneID = CRM_Utils_Request::retrieve('cloneID', 'Positive', $this, FALSE, 0);
    $this->set('BAOName', 'CRM_CiviDiscount_BAO_Item');

    parent::preProcess();

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/cividiscount/discount/list', 'reset=1');
    $session->pushUserContext($url);

    // check and ensure that update / delete have a valid id
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      if (!CRM_Utils_Rule::positiveInteger($this->_id)) {
        CRM_Core_Error::fatal(ts('We need a valid discount ID for update and/or delete'));
      }
    }

    if ($this->_action & CRM_Core_Action::COPY) {
      if (!CRM_Utils_Rule::positiveInteger($this->_cloneID)) {
        CRM_Core_Error::fatal(ts('We need a valid discount ID for update and/or delete'));
      }
    }

    CRM_Utils_System::setTitle(ts('Discounts'));

    $this->_multiValued = array(
      'memberships' => NULL,
      'events' => NULL,
      'pricesets' => NULL
    );

    $this->select2style = array(
      'placeholder' => ts('- none -'),
      'multiple' => TRUE,
      'class' => 'crm-select2 huge',
    );
  }

  function setDefaultValues() {
    $origID = NULL;
    $defaults = array();

    if ($this->_action & CRM_Core_Action::COPY) {
      $origID = $this->_cloneID;
    }
    else {
      if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
        $origID = $this->_id;
      }
    }

    if ($origID) {
      $params = array('id' => $origID);
      CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);
    }
    $defaults['is_active'] = $origID ? CRM_Utils_Array::value('is_active', $defaults) : 1;
    $defaults['autodiscount_active_only'] = $origID ? CRM_Utils_Array::value('autodiscount_active_only', $defaults) : 1;
    $defaults['discount_msg_enabled'] = $origID ? CRM_Utils_Array::value('discount_msg_enabled', $defaults) : 0;
    $defaults['count_max'] = empty($defaults['count_max']) ? '' : $defaults['count_max'];

    // assign the defaults to smarty so delete can use it
    $this->assign('discountValue', $defaults);
    $this->applyFilterDefaults($defaults);
    $this->applyAutoDiscountDefaults($defaults);
    foreach ($this->_multiValued as $mv => $info) {
      if (!empty($defaults[$mv])) {
        $v = substr($defaults[$mv], 1, -1);
        $values = explode(CRM_Core_DAO::VALUE_SEPARATOR, $v);

        $defaults[$mv] = array();
        if (!empty($values)) {
          foreach ($values as $val) {
            $defaults[$mv][] = $val;
          }
        }
      }
    }

    if (!empty($defaults['active_on'])) {
      list($defaults['active_on']) = CRM_Utils_Date::setDateDefaults($defaults['active_on']);
    }
    if (!empty($defaults['expire_on'])) {
      list($defaults['expire_on']) = CRM_Utils_Date::setDateDefaults($defaults['expire_on']);
    }

    if (!empty($defaults['organization_id'])) {
      $this->_orgID = $defaults['organization_id'];
    }
    // Convert if using html
    if (!empty($defaults['discount_msg'])) {
      $defaults['discount_msg'] = html_entity_decode($defaults['discount_msg']);
    }
    return $defaults;
  }

  /**
   * Function to build the form
   *
   * @return None
   * @access public
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::COPY)) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $element = $this->add('text',
      'code',
      ts('Discount Code'),
      CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'code'),
      TRUE
    );
    $this->addRule('code',
      ts('Code already exists in Database.'),
      'objectExists',
      array('CRM_CiviDiscount_DAO_Item', $this->_id, 'code'));
    $this->addRule('code',
      ts('Code can only consist of alpha-numeric characters'),
      'variable');
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $element->freeze();
    }

    $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'description'));

    $this->addMoney('amount', ts('Discount Amount'), TRUE, CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'amount'), FALSE);

    $this->add('select', 'amount_type', NULL,
      array(
        1 => ts('Percent'),
        2 => ts('Fixed Amount')
      ),
      TRUE);

    $this->add('text', 'count_max', ts('Usage Limit'), CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'count_max') + array('min' => 1));
    $this->addRule('count_max', ts('Must be an integer'), 'integer');

    $this->addDate('active_on', ts('Activation Date'), FALSE);
    $this->addDate('expire_on', ts('Expiration Date'), FALSE);

    $this->addEntityRef('organization_id', ts('Organization'), array('api' => array('params' => array('contact_type' => 'Organization'))));

    // is this discount active ?
    $this->addElement('checkbox', 'is_active', ts('Is this discount active?'));

    $this->addElement('checkbox', 'discount_msg_enabled', ts('Display a message to users not eligible for this discount?'));
    $this->add('textarea', 'discount_msg', ts('Message to non-eligible users'), array('class' => 'big'));

    // add memberships, events, pricesets
    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes(FALSE);
    if (!empty($membershipTypes)) {
      $this->add('select',
        'memberships',
        ts('Memberships'),
        $membershipTypes,
        FALSE,
        $this->select2style
      );
    }
    $this->assignAutoDiscountFields();
    $this->addElement('text', 'advanced_autodiscount_filter_entity', ts('Specify entity for advanced autodiscount'));
    $this->addElement('text', 'advanced_autodiscount_filter_string', ts('Specify api string for advanced filter'), array('class' => 'huge'));

    $events = CRM_CiviDiscount_Utils::getEvents();
    if (!empty($events)) {
      $events = array(ts('--any event--')) + $events;
      $this->_multiValued['events'] = $events;
      $this->add('select',
        'events',
        ts('Events'),
        $events,
        FALSE,
        $this->select2style
      );

      $eventTypes = $this->getOptions('event', 'event_type_id');
      $this->_multiValued['eventtypes'] = $eventTypes;
      $this->add('select',
        'event_type_id',
        ts('Event Types'),
        $eventTypes,
        FALSE,
        $this->select2style
      );
    }

    $pricesets = CRM_CiviDiscount_Utils::getNestedPriceSets();
    if (!empty($pricesets)) {
      $this->_multiValued['pricesets'] = $pricesets;
      $this->add('select',
        'pricesets',
        ts('Price Field Options'),
        $pricesets,
        FALSE,
        array('placeholder' => ts('- any -')) + $this->select2style
      );
    }
  }

  /**
   * Add autodiscount fields to the form based on the definition in getSupportedAutoDiscountFilters
   */
  private function assignAutoDiscountFields() {
    $assignedAutoFilters = array();
    foreach ($this->getSupportedAutoDiscountFilters() as $entity => $autoFilters) {
      foreach ($autoFilters as $filterName => $autoFilter) {
        $optionFieldTypes = array('advmultiselect');
        if (in_array($autoFilter['field_type'], $optionFieldTypes) && empty($autoFilter['options'])) {
          continue;
        }
        $this->addElement(
          $autoFilter['field_type'],
          $autoFilter['form_field_name'],
          $autoFilter['title'],
          isset($autoFilter['options']) ? $autoFilter['options'] : array(),
          $this->select2style
        );
        $assignedAutoFilters[] = $autoFilter['form_field_name'];
        if (!empty($autoFilter['rule_data_type'])) {
          $this->addRule($autoFilter['form_field_name'], ts('Please re-enter ' . $autoFilter['title'] . ' you need to enter an ' . $autoFilter['rule_data_type']), $autoFilter['rule_data_type']);
        }
      }
    }
    $this->assign('autodiscounts', $assignedAutoFilters);
  }

  /**
   * Function to process the form
   *
   * @access public
   * @return None
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_CiviDiscount_BAO_Item::del($this->_id);
      CRM_Core_Session::setStatus(ts('Selected Discount has been deleted.'), ts('Deleted'), 'success');
      return;
    }

    if ($this->_action & CRM_Core_Action::COPY) {
      $params = $this->exportValues();
      $newCode = CRM_CiviDiscount_Utils::randomString('abcdefghjklmnpqrstwxyz23456789', 8);
      CRM_CiviDiscount_BAO_Item::copy($this->_cloneID, $params, $newCode);
      CRM_Core_Session::setStatus(ts('Selected Discount has been duplicated.'), ts('Copied'), 'success');
      return;
    }

    $params = $this->exportValues();

    $params['count_max'] = (int) $params['count_max'];

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }
    $params['multi_valued'] = $this->_multiValued;

    if (isset($params['events']) && in_array(0, $params['events']) && count($params['events']) > 1) {
      CRM_Core_Session::setStatus(ts('You selected `any event` and specific events, specific events have been unset'));
      $params['events'] = array(0);
    }
    if (!empty($params['autodiscount_membership_type_id']) && count($params['autodiscount_membership_status_id']) == 0) {
      $params['autodiscount_membership_status_id'] = array('');
    }
    $params['filters'] = $this->getFiltersFromParams($params);
    $params['autodiscount'] = $this->getAutoDiscountFromParams($params);
    if (!empty($params['advanced_autodiscount_filter_entity'])) {
      $this->addAdvancedFilterToAutodiscount($params, $params['advanced_autodiscount_filter_entity'], CRM_Utils_Array::value('advanced_autodiscount_filter_string', $params));
    }
    $item = CRM_CiviDiscount_BAO_Item::add($params);

    CRM_Core_Session::setStatus(ts('The discount \'%1\' has been saved.',
      array(1 => $item->description ? $item->description : $item->code)), ts('Saved'), 'success');
  }

  /**
   * Add advanced filters from UI. Note that setting an entity but not a filter string basically means
   * that only the contact id will be passed in as a parameter (as id for contact or contact_id for all others)
   *
   * @param params
   * @param discountString
   */
  private function addAdvancedFilterToAutodiscount(&$params, $discountEntity, $discountString) {
    if ($discountString) {
      if (stristr($discountString, 'api.') || stristr($discountString, 'api_')) {
        throw new CRM_Core_Exception(ts('You cannot nest apis in the advanced filter'));
      }
      if (stristr($discountString, '{')) {
        $discounts = json_decode($discountString, TRUE);
      }
      else {
        $discounts = explode(',', $discountString);
        foreach ($discounts as $id => $discount) {
          if (!stristr($discount, '=')) {
            throw new CRM_Core_Exception(ts('You have a criteria without an = sign'));
          }
          $parts = explode('=', $discount);
          $discounts[$parts[0]] = $parts[1];
          unset($discounts[$id]);
        }
      }
    }
    if (!isset($params['autodiscount'][$discountEntity])) {
      $params['autodiscount'][$discountEntity] = array();
    }

    foreach ($discounts as $key => $filter) {
      $params['autodiscount'][$discountEntity][$key] = $filter;
    }
  }


  /**
   * Convert from params to values to be stored in the filter
   * @param array $params parameters submitted to form
   * @return array filters to be stored in DB
   */
  function getJsonFieldFromParams($params, $fn) {
    $filters = array();
    foreach ($this->$fn() as $entity => $fields) {
      foreach ($fields as $field => $spec) {
        $fieldName = $spec['form_field_name'];
        if (!empty($params[$fieldName])) {
          if (empty($spec['operator'])) {
            $filters[$entity][$field] = $params[$fieldName];
          }
          else {
            $filters[$entity][$field] = array($spec['operator'] => $params[$fieldName]);
          }
        }
      }
    }
    return $filters;
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $params parameters submitted to form
   * @return array filters to be stored in DB
   */
  function getAutoDiscountFromParams($params) {
    $fields = $this->getJsonFieldFromParams($params, 'getSupportedAutoDiscountFilters');
    $this->adjustAgeFields($fields);
    $this->adjustMembershipStatusField($fields);
    return $fields;
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $params parameters submitted to form
   * @return array filters to be stored in DB
   */
  function getFiltersFromParams($params) {
    return $this->getJsonFieldFromParams($params, 'getSupportedFilters');
  }

  /**
   * Convert handling of age fields to api-acceptable 'birth_date_high' & birth_date_low
   * @param unknown $fields
   */
  function adjustAgeFields(&$fields) {
    if (!empty($fields['contact'])) {
      if (!empty($fields['contact']['age_low'])) {
        $fields['contact']['birth_date_high'] = '- ' . $fields['contact']['age_low']['='] . ' years';
        unset($fields['contact']['age_low']);
      }
      if (!empty($fields['contact']['age_high'])) {
        $fields['contact']['birth_date_low'] = '- ' . $fields['contact']['age_high']['='] . ' years';
        unset($fields['contact']['age_high']);
      }
    }
  }

  /**
   * Convert handle 'any current' -
   * If it is set then we need to translate it to 'active_only' as
   * we want this to move over time if the membership statuses are changed so we should interpret it to
   * 'active_only'
   * @param unknown $fields
   */
  function adjustMembershipStatusField(&$fields) {
    if (!empty($fields['membership'])) {
      if (isset($fields['membership']['status_id'])) {
        foreach ($fields['membership']['status_id']['IN'] as $status) {
          if (empty($status)) {
            $fields['membership']['active_only'] = 1;
            if (count($fields['membership']['status_id']['IN']) > 1) {
              CRM_Core_Session::setStatus(ts('You set "any current status" and specific statuses, specific statuses have been discarded'));
            }
            unset($fields['membership']['status_id']);
            continue;
          }
        }
      }
    }
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $params parameters submitted to form
   * @return array filters to be stored in DB
   */
  function applyFilterDefaults(&$defaults) {
    $this->applyJsonFieldDefaults($defaults, 'filters', 'getSupportedFilters');
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $params parameters submitted to form
   * @return array filters to be stored in DB
   */
  function applyAutoDiscountDefaults(&$defaults) {
    $this->applyJsonFieldDefaults($defaults, 'autodiscount', 'getSupportedAutoDiscountFilters');
  }


  /**
   * Apply defaults to fields stored in json fields
   * @param defaults
   * @param type
   * @param string $fn function to get definition from
   */
  private function applyJsonFieldDefaults(&$defaults, $type, $fn) {
    if (empty($defaults[$type])) {
      return array();
    }
    $fieldsDisplayedElsewhereOnForm = array(
      'contact' => array(
        'birth_date_high',
        'birth_date_low',
      ),
      'membership' => array(
        'active_only',
      ),
      'address' => array(
        'country_id',
      )
    );

    $filters = json_decode($defaults[$type], TRUE);
    $specs = $this->$fn();
    foreach ($filters as $entity => $entityFilters) {
      if (isset($fieldsDisplayedElsewhereOnForm[$entity])) {
        // probably could do next 3 lines with an array_diff
        foreach ($fieldsDisplayedElsewhereOnForm[$entity] as $fieldSetElsewhere) {
          if (isset($entityFilters[$fieldSetElsewhere])) {
            unset($entityFilters[$fieldSetElsewhere]);
          }
        }
      }
      if (!isset($specs[$entity])) {
        $defaults['advanced_' . $type . '_filter_entity'] = $entity;
        $defaults['advanced_' . $type . '_filter_string'] = $entityFilters;
      }
      else {
        foreach ($entityFilters as $key => $filter) {
          if (!isset($specs[$entity][$key])) {
            $defaults['advanced_' . $type . '_filter_entity'] = $entity;
            $defaults['advanced_' . $type . '_filter_string'][$key] = $filter;
          }
        }
      }
    }
    if (!empty($defaults['advanced_' . $type . '_filter_string'])) {
      $defaults['advanced_' . $type . '_filter_string'] = json_encode($defaults['advanced_' . $type . '_filter_string'], TRUE);
    }

    foreach ($specs as $entity => $fields) {
      foreach ($fields as $field => $spec) {
        if (!isset($filters[$entity])) {
          continue;
        }
        $fieldName = $spec['form_field_name'];
        if (!empty($spec['defaults_callback'])) {
          $callback = $spec['defaults_callback'];
          $defaults[$fieldName] = $this->$callback($defaults, $fieldName, $filters, $spec);
          continue;
        }
        if (!isset($filters[$entity][$field])) {
          continue;
        }
        if (empty($spec['operator'])) {
          $defaults[$fieldName] = $filters[$entity][$field];
        }
        else {
          $defaults[$fieldName] = $filters[$entity][$field][$spec['operator']];
        }
      }
    }
    return $filters;
  }

  /**
   * Set default for age fields as stored as birth_date_high & birth_date_low
   * @param unknown $defaults
   * @param unknown $fieldName
   * @param unknown $value
   * @param unknown $spec
   */
  function setAgeDefaults(&$defaults, $fieldName, $values, $spec) {
    $fields = array(
      'autodiscount_age_low' => 'birth_date_high',
      'autodiscount_age_high' => 'birth_date_low'
    );
    if (!empty($values['contact'][$fields[$fieldName]])) {
      return abs(filter_var($values['contact'][$fields[$fieldName]], FILTER_SANITIZE_NUMBER_INT));
    }
  }

  /**
   * Set default for membership status based on presence of 'active_only' param
   * @param unknown $defaults
   * @param unknown $fieldName
   * @param unknown $value
   * @param unknown $spec
   */
  function setMembershipStatusDefaults(&$defaults, $fieldName, $values, $spec) {
    if (!empty($values['membership']['active_only'])) {
      return '';
    }
  }


  /**
   * Here we define filter extensions to be stored in the filters field in the DB
   * Later we will figure out how to make this hookable so that discounts can be extended
   * The format is
   *   array(
   *     'entity' => array(
   *       'field1' => array('form_field_name' => field1),
   *       'field2' => array('form_field_name' => field2),
   * )
   * where both the entity & the field names should be valid for api calls.
   * The form field name is the name of the field on the form - we set it in case we get a conflict
   *  - eg. multiple entities have 'status_id'
   * @return array supported filters
   */
  function getSupportedFilters() {
    return array(
      'event' => array(
        'event_type_id' => array(
          'form_field_name' => 'event_type_id',
          'operator' => 'IN',
        )
      )
    );
  }

  /**
   * Here we define filter extensions to be stored in the filters field in the DB
   * Later we will figure out how to make this hookable so that discounts can be extended
   * The format is
   *   array(
   *     'entity' => array(
   *       'field1' => array('form_field_name' => field1),
   *       'field2' => array('form_field_name' => field2),
   * )
   * where both the entity & the field names should be valid for api calls.
   * The form field name is the name of the field on the form - we set it in case we get a conflict
   *  - eg. multiple entities have 'status_id'
   * @return array supported filters
   */
  function getSupportedAutoDiscountFilters() {
    return array(
      'membership' => array(
        'membership_type_id' => array(
          'title' => ts('Automatic discount for existing members of type'),
          'form_field_name' => 'autodiscount_membership_type_id',
          'operator' => 'IN',
          'field_type' => 'select',
          'options' => $this->getOptions('membership', 'membership_type_id'),
        ),
        'status_id' => array(
          'title' => ts('Automatic discount for Membership Statuses'),
          'form_field_name' => 'autodiscount_membership_status_id',
          'operator' => 'IN',
          'field_type' => 'select',
          'options' => array('' => ts('--any current status--')) + $this->getOptions('membership', 'status_id'),
          'defaults_callback' => 'setMembershipStatusDefaults',
        ),
      ),
      'contact' => array(
        'contact_type' => array(
          'title' => ts('Contact Type'),
          'form_field_name' => 'autodiscount_contact_type',
          'operator' => 'IN',
          'options' => $this->getOptions('contact', 'contact_type'),
          'field_type' => 'select',
        ),
        'age_low' => array(
          'title' => ts('Minimum Age'),
          'field_type' => 'Text',
          'form_field_name' => 'autodiscount_age_low',
          'rule_data_type' => 'integer',
          'operator' => '=',
          'defaults_callback' => 'setAgeDefaults',
        ),
        'age_high' => array(
          'title' => ts('Maximum Age'),
          'field_type' => 'Text',
          'operator' => '=',// we could make this the adjustment fn name?
          'form_field_name' => 'autodiscount_age_high',
          'rule_data_type' => 'integer',
          'defaults_callback' => 'setAgeDefaults',
        ),
      ),
      'address' => array(
        'country_id' => array(
          'title' => ts('Country'),
          'form_field_name' => 'autodiscount_country_id',
          'operator' => 'IN',
          'field_type' => 'select',
          'options' => $this->getOptions('address', 'country_id'),
        ),
      )
    );
  }

  /**
   * We want to avoid calling the BAO function from an extension if we can avoid it as api is more consistent
   * across versions - but the api requires 2 lines of code which is annoying so a wrapper to bring back to one
   * @param string $entity
   * @param string $field
   * @return array Options for field
   */
  function getOptions($entity, $field) {
    $result = civicrm_api3($entity, 'getoptions', array('field' => $field));
    return $result['values'];
  }

}
