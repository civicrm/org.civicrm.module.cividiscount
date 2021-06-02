<?php

use CRM_CiviDiscount_ExtensionUtil as E;

/**
 * This class generates form components for cividiscount administration.
 * @package CiviDiscount
 */
class CRM_CiviDiscount_Form_Admin extends CRM_Admin_Form {
  protected $_multiValued = NULL;
  protected $_orgID = NULL;
  protected $_cloneID = NULL;

  protected $select2style = [];

  /**
   * @throws \CRM_Core_Exception
   * @throws \Exception
   */
  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    $this->_cloneID = CRM_Utils_Request::retrieve('cloneID', 'Positive', $this, FALSE, 0);
    $this->set('BAOName', 'CRM_CiviDiscount_BAO_Item');

    parent::preProcess();

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/cividiscount', 'reset=1');
    $session->pushUserContext($url);

    // check and ensure that update / delete have a valid id
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      if (!CRM_Utils_Rule::positiveInteger($this->_id)) {
        CRM_Core_Error::fatal('We need a valid discount ID for update and/or delete');
      }
    }

    if ($this->_action & CRM_Core_Action::COPY) {
      if (!CRM_Utils_Rule::positiveInteger($this->_cloneID)) {
        CRM_Core_Error::fatal('We need a valid discount ID for update and/or delete');
      }
    }

    CRM_Utils_System::setTitle(E::ts('Discounts'));

    $this->_multiValued = [
      'memberships' => NULL,
      'events' => NULL,
      'pricesets' => NULL,
    ];

    $this->select2style = [
      'placeholder' => E::ts('- none -'),
      'multiple' => TRUE,
      'class' => 'crm-select2 huge',
    ];
  }

  /**
   * @return array
   */
  public function setDefaultValues() {
    $origID = NULL;
    $defaults = [];

    if ($this->_action & CRM_Core_Action::COPY) {
      $origID = $this->_cloneID;
    }
    else {
      if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
        $origID = $this->_id;
      }
    }

    if ($origID) {
      $params = ['id' => $origID];
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

        $defaults[$mv] = [];
        if (!empty($values)) {
          foreach ($values as $val) {
            $defaults[$mv][] = $val;
          }
        }
      }
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
   */
  public function buildQuickForm() {
    parent::buildQuickForm();

    if ($this->_action & (CRM_Core_Action::DELETE | CRM_Core_Action::COPY)) {
      return;
    }

    $this->applyFilter('__ALL__', 'trim');
    $element = $this->add('text',
      'code',
      E::ts('Discount Code'),
      CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'code'),
      TRUE
    );
    $this->addRule('code',
      E::ts('Code already exists in Database.'),
      'objectExists',
      ['CRM_CiviDiscount_DAO_Item', $this->_id, 'code']);
    $this->addRule('code',
      E::ts('Code can only consist of alpha-numeric characters'),
      'variable');
    if ($this->_action & CRM_Core_Action::UPDATE) {
      $element->freeze();
    }

    $this->add('text', 'description', E::ts('Description'), CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'description'), TRUE);

    $this->addMoney('amount', E::ts('Discount Amount'), TRUE, CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'amount'), FALSE);

    $this->add('select', 'amount_type', NULL,
      [
        1 => E::ts('Percent'),
        2 => E::ts('Fixed Amount'),
      ],
      TRUE);

    $this->add('text', 'count_max', E::ts('Usage Limit'), CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'count_max') + ['min' => 1]);
    $this->addRule('count_max', E::ts('Must be an integer'), 'integer');

    $this->add('datepicker', 'active_on', E::ts('Activation Date'), [], FALSE, ['time' => FALSE]);
    $this->add('datepicker', 'expire_on', E::ts('Expiration Date'), [], FALSE, ['time' => FALSE]);

    $this->addEntityRef('organization_id', E::ts('Organization'), ['api' => ['params' => ['contact_type' => 'Organization']]]);

    // is this discount active ?
    $this->addElement('checkbox', 'is_active', E::ts('Is this discount active?'));

    $this->addElement('checkbox', 'discount_msg_enabled', E::ts('Display a message to users not eligible for this discount?'));
    $this->add('textarea', 'discount_msg', E::ts('Message to non-eligible users'), ['class' => 'big']);

    // add memberships, events, pricesets
    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes(FALSE);
    if (!empty($membershipTypes)) {
      $this->add('select',
        'memberships',
        E::ts('Memberships'),
        $membershipTypes,
        FALSE,
        $this->select2style
      );
    }

    $this->addElement('checkbox', 'membership_new', E::ts('New members?'));
    $this->addElement('checkbox', 'membership_renew', E::ts('Renewing members?'));

    $this->assignAutoDiscountFields();
    $this->addElement('text', 'advanced_autodiscount_filter_entity', E::ts('Specify entity for advanced autodiscount'));
    $this->addElement('text', 'advanced_autodiscount_filter_string', E::ts('Specify api string for advanced filter'), ['class' => 'huge']);

    $events = CRM_CiviDiscount_Utils::getEvents();
    if (!empty($events)) {
      $events = [E::ts('--any event--')] + $events;
      $this->_multiValued['events'] = $events;
      $this->add('select',
        'events',
        E::ts('Events'),
        $events,
        FALSE,
        $this->select2style
      );

      $eventTypes = $this->getOptions('event', 'event_type_id');
      $this->_multiValued['eventtypes'] = $eventTypes;
      $this->add('select',
        'event_type_id',
        E::ts('Event Types'),
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
        E::ts('Price Field Options'),
        $pricesets,
        FALSE,
        ['placeholder' => E::ts('- any -')] + $this->select2style
      );
    }
    $this->addFormRule(['CRM_CiviDiscount_Form_Admin', 'formRule'], $this);
  }

  /**
   * @param $values
   * @param $files
   * @param $self
   * @return array|bool
   */
  public static function formRule($values, $files, $self) {
    $errors = [];
    if (!empty($values['memberships'])) {
      if (empty($values['membership_new']) && empty($values['membership_renew'])) {
        $errors['membership_new'] = ts('For membership discount, select one of the checkbox "New members" or "Renewing members"');
      }
    }

    return empty($errors) ? TRUE : $errors;
  }

  /**
   * Add autodiscount fields to the form based on the definition in getSupportedAutoDiscountFilters
   */
  private function assignAutoDiscountFields() {
    $assignedAutoFilters = [];
    foreach ($this->getSupportedAutoDiscountFilters() as $entity => $autoFilters) {
      foreach ($autoFilters as $filterName => $autoFilter) {
        $optionFieldTypes = ['advmultiselect'];
        if (in_array($autoFilter['field_type'], $optionFieldTypes) && empty($autoFilter['options'])) {
          continue;
        }
        $this->addElement(
          $autoFilter['field_type'],
          $autoFilter['form_field_name'],
          $autoFilter['title'],
          isset($autoFilter['options']) ? $autoFilter['options'] : [],
          $this->select2style
        );
        $assignedAutoFilters[] = $autoFilter['form_field_name'];
        if (!empty($autoFilter['rule_data_type'])) {
          $this->addRule($autoFilter['form_field_name'], E::ts('Please re-enter %1, a %2 is required.', [1 => $autoFilter['title'], 2 => $autoFilter['rule_data_type']]), $autoFilter['rule_data_type']);
        }
      }
    }
    $this->assign('autodiscounts', $assignedAutoFilters);
  }

  /**
   * Function to process the form
   */
  public function postProcess() {
    if ($this->_action & CRM_Core_Action::DELETE) {
      CRM_CiviDiscount_BAO_Item::del($this->_id);
      CRM_Core_Session::setStatus(E::ts('Selected Discount has been deleted.'), E::ts('Deleted'), 'success');
      return;
    }

    if ($this->_action & CRM_Core_Action::COPY) {
      $params = $this->exportValues();
      $newCode = CRM_CiviDiscount_Utils::randomString('abcdefghjklmnpqrstwxyz23456789', 8);
      CRM_CiviDiscount_BAO_Item::copy($this->_cloneID, $params, $newCode);
      CRM_Core_Session::setStatus(E::ts('Selected Discount has been duplicated.'), E::ts('Saved'), 'success');
      return;
    }

    $params = $this->exportValues();

    $params['count_max'] = (int) $params['count_max'];

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }
    $params['multi_valued'] = $this->_multiValued;

    if (isset($params['events']) && in_array(0, $params['events']) && count($params['events']) > 1) {
      CRM_Core_Session::setStatus(E::ts('The events you selected will be ignored because you also chose "any event."'));
      $params['events'] = [0];
    }
    if (!empty($params['autodiscount_membership_type_id']) && count($params['autodiscount_membership_status_id']) == 0) {
      $params['autodiscount_membership_status_id'] = [''];
    }
    $params['filters'] = $this->getFiltersFromParams($params);
    $params['autodiscount'] = $this->getAutoDiscountFromParams($params);
    if (!empty($params['advanced_autodiscount_filter_entity'])) {
      $this->addAdvancedFilterToAutodiscount($params, $params['advanced_autodiscount_filter_entity'], CRM_Utils_Array::value('advanced_autodiscount_filter_string', $params));
    }
    $item = CRM_CiviDiscount_BAO_Item::add($params);

    CRM_Core_Session::setStatus(E::ts('The discount "%1" has been saved.',
      [1 => $item->description ?: $item->code]), E::ts('Saved'), 'success');
  }

  /**
   * Add advanced filters from UI. Note that setting an entity but not a filter string basically means
   * that only the contact id will be passed in as a parameter (as id for contact or contact_id for all others)
   *
   * @param array $params
   * @param string $discountEntity
   * @param string $discountString
   * @throws \CRM_Core_Exception
   */
  private function addAdvancedFilterToAutodiscount(&$params, $discountEntity, $discountString) {
    $discounts = [];
    if ($discountString) {
      if (stristr($discountString, 'api.') || stristr($discountString, 'api_')) {
        throw new CRM_Core_Exception(E::ts('You cannot nest apis in the advanced filter'));
      }
      if (stristr($discountString, '{')) {
        $discounts = json_decode($discountString, TRUE);
      }
      else {
        $discounts = explode(',', $discountString);
        foreach ($discounts as $id => $discount) {
          if (!stristr($discount, '=')) {
            throw new CRM_Core_Exception(E::ts('You have a criteria without an = sign'));
          }
          $parts = explode('=', $discount);
          $discounts[$parts[0]] = $parts[1];
          unset($discounts[$id]);
        }
      }
    }
    if (!isset($params['autodiscount'][$discountEntity])) {
      $params['autodiscount'][$discountEntity] = [];
    }

    foreach ($discounts as $key => $filter) {
      $params['autodiscount'][$discountEntity][$key] = $filter;
    }
  }


  /**
   * Convert from params to values to be stored in the filter
   *
   * @param array $params
   *   parameters submitted to form
   * @param string $fn
   *   public function to call
   *
   * @return array
   *   filters to be stored in DB
   */
  public function getJsonFieldFromParams($params, $fn) {
    $filters = [];
    foreach ($this->$fn() as $entity => $fields) {
      foreach ($fields as $field => $spec) {
        $fieldName = $spec['form_field_name'];
        if (!empty($params[$fieldName])) {
          if (empty($spec['operator'])) {
            $filters[$entity][$field] = $params[$fieldName];
          }
          else {
            $filters[$entity][$field] = [$spec['operator'] => $params[$fieldName]];
          }
        }
      }
    }
    return $filters;
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $params
   * @return array
   */
  public function getAutoDiscountFromParams($params) {
    $fields = $this->getJsonFieldFromParams($params, 'getSupportedAutoDiscountFilters');
    $this->adjustAgeFields($fields);
    $this->adjustMembershipStatusField($fields);
    return $fields;
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $params
   * @return array
   */
  public function getFiltersFromParams($params) {
    return $this->getJsonFieldFromParams($params, 'getSupportedFilters');
  }

  /**
   * Convert handling of age fields to api-acceptable 'birth_date_high' & birth_date_low
   *
   * @param array $fields
   */
  public function adjustAgeFields(&$fields) {
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
   * Convert handle 'any current'
   *
   * If it is set then we need to translate it to 'active_only' as
   * we want this to move over time if the membership statuses are changed so we should interpret it to
   * 'active_only'
   *
   * @param array $fields
   */
  public function adjustMembershipStatusField(&$fields) {
    if (!empty($fields['membership'])) {
      if (isset($fields['membership']['status_id'])) {
        foreach ($fields['membership']['status_id']['IN'] as $status) {
          if (empty($status)) {
            $fields['membership']['active_only'] = 1;
            if (count($fields['membership']['status_id']['IN']) > 1) {
              CRM_Core_Session::setStatus(E::ts('The statuses you selected will be ignored because you also chose "any current status."'));
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
   * @param array $defaults
   */
  public function applyFilterDefaults(&$defaults) {
    $this->applyJsonFieldDefaults($defaults, 'filters', 'getSupportedFilters');
  }

  /**
   * Convert from params to values to be stored in the filter
   * @param array $defaults
   */
  public function applyAutoDiscountDefaults(&$defaults) {
    $this->applyJsonFieldDefaults($defaults, 'autodiscount', 'getSupportedAutoDiscountFilters');
  }


  /**
   * Apply defaults to fields stored in json fields
   * @param array $defaults
   * @param string $type
   * @param string $fn
   *   public function to get definition from
   * @return array
   */
  private function applyJsonFieldDefaults(&$defaults, $type, $fn) {
    if (empty($defaults[$type])) {
      return [];
    }
    $fieldsDisplayedElsewhereOnForm = [
      'contact' => [
        'birth_date_high',
        'birth_date_low',
      ],
      'membership' => [
        'active_only',
      ],
      'address' => [
        'country_id',
      ],
    ];

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
   *
   * @param array $defaults
   * @param string $fieldName
   * @param array $values
   * @param null $spec
   * @return int
   */
  public function setAgeDefaults(&$defaults, $fieldName, $values, $spec) {
    $fields = [
      'autodiscount_age_low' => 'birth_date_high',
      'autodiscount_age_high' => 'birth_date_low',
    ];
    if (!empty($values['contact'][$fields[$fieldName]])) {
      return abs(filter_var($values['contact'][$fields[$fieldName]], FILTER_SANITIZE_NUMBER_INT));
    }
  }

  /**
   * Set default for membership status based on presence of 'active_only' param
   *
   * @param array $defaults
   * @param string $fieldName
   * @param array $values
   * @param null $spec
   * @return string
   */
  public function setMembershipStatusDefaults(&$defaults, $fieldName, $values, $spec) {
    if (!empty($values['membership']['active_only'])) {
      return '';
    }
    elseif (!empty($values['membership']['status_id']['IN'])) {
      return $values['membership']['status_id']['IN'];
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
   *   )
   * where both the entity & the field names should be valid for api calls.
   * The form field name is the name of the field on the form - we set it in case we get a conflict
   *  - eg. multiple entities have 'status_id'
   * @return array supported filters
   */
  public function getSupportedFilters() {
    return [
      'event' => [
        'event_type_id' => [
          'form_field_name' => 'event_type_id',
          'operator' => 'IN',
        ]
      ]
    ];
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
  public function getSupportedAutoDiscountFilters() {
    return [
      'membership' => [
        'membership_type_id' => [
          'title' => E::ts('Automatic discount for existing members of type'),
          'form_field_name' => 'autodiscount_membership_type_id',
          'operator' => 'IN',
          'field_type' => 'select',
          'options' => $this->getOptions('membership', 'membership_type_id'),
        ],
        'status_id' => [
          'title' => E::ts('Automatic discount for Membership Statuses'),
          'form_field_name' => 'autodiscount_membership_status_id',
          'operator' => 'IN',
          'field_type' => 'select',
          'options' => ['' => E::ts('--any current status--')] + $this->getOptions('membership', 'status_id'),
          'defaults_callback' => 'setMembershipStatusDefaults',
        ],
      ],
      'contact' => [
        'contact_type' => [
          'title' => E::ts('Contact Type'),
          'form_field_name' => 'autodiscount_contact_type',
          'operator' => 'IN',
          'options' => $this->getOptions('contact', 'contact_type'),
          'field_type' => 'select',
        ],
        'age_low' => [
          'title' => E::ts('Minimum Age'),
          'field_type' => 'Text',
          'form_field_name' => 'autodiscount_age_low',
          'rule_data_type' => 'integer',
          'operator' => '=',
          'defaults_callback' => 'setAgeDefaults',
        ],
        'age_high' => [
          'title' => E::ts('Maximum Age'),
          'field_type' => 'Text',
          'operator' => '=', // we could make this the adjustment fn name?
          'form_field_name' => 'autodiscount_age_high',
          'rule_data_type' => 'integer',
          'defaults_callback' => 'setAgeDefaults',
        ],
      ],
      'address' => [
        'country_id' => [
          'title' => E::ts('Country'),
          'form_field_name' => 'autodiscount_country_id',
          'operator' => 'IN',
          'field_type' => 'select',
          'options' => $this->getOptions('address', 'country_id'),
        ],
      ]
    ];
  }

  /**
   * We want to avoid calling the BAO function from an extension if we can avoid it as api is more consistent
   * across versions - but the api requires 2 lines of code which is annoying so a wrapper to bring back to one
   * @param string $entity
   * @param string $field
   * @return array Options for field
   */
  public function getOptions($entity, $field) {
    $result = civicrm_api3($entity, 'getoptions', ['field' => $field]);
    return $result['values'];
  }

}
