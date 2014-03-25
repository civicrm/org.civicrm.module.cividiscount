<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'CRM/CiviDiscount/BAO/Item.php';

/**
 * This class generates form components for cividiscount administration.
 *
 */
class CRM_CiviDiscount_Form_Admin extends CRM_Admin_Form {
  protected $_multiValued = null;
  protected $_orgID = null;
  protected $_cloneID = null;

  function preProcess() {
    $this->_id      = CRM_Utils_Request::retrieve('id', 'Positive', $this, false, 0);
    $this->_cloneID = CRM_Utils_Request::retrieve('cloneID', 'Positive', $this, false, 0);
    require_once 'CRM/CiviDiscount/BAO/Item.php';
    $this->set('BAOName', 'CRM_CiviDiscount_BAO_Item');

    parent::preProcess();

    $session = CRM_Core_Session::singleton();
    $url = CRM_Utils_System::url('civicrm/cividiscount/discount/list', 'reset=1');
    $session->pushUserContext($url);

    // check and ensure that update / delete have a valid id
    require_once 'CRM/Utils/Rule.php';
    if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      if (! CRM_Utils_Rule::positiveInteger($this->_id)) {
        CRM_Core_Error::fatal(ts('We need a valid discount ID for update and/or delete'));
      }
    }

    if ($this->_action & CRM_Core_Action::COPY) {
      if (! CRM_Utils_Rule::positiveInteger($this->_cloneID)) {
        CRM_Core_Error::fatal(ts('We need a valid discount ID for update and/or delete'));
      }
    }

    CRM_Utils_System::setTitle(ts('Discounts'));

    $this->_multiValued = array(
      'autodiscount' => null,
      'memberships'  => null,
      'events'       => null,
      'pricesets'    => null
    );
  }

  function setDefaultValues() {
    $origID = null;
    $defaults = array();

    if ($this->_action & CRM_Core_Action::COPY) {
      $origID = $this->_cloneID;
    }
    else if ($this->_action & (CRM_Core_Action::UPDATE | CRM_Core_Action::DELETE)) {
      $origID = $this->_id;
    }

    if ($origID) {
      $params = array('id' => $origID);
      CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);
    }
    $defaults['is_active'] = $origID ? CRM_Utils_Array::value('is_active', $defaults) : 1;
    $defaults['discount_msg_enabled'] = $origID ? CRM_Utils_Array::value('discount_msg_enabled', $defaults) : 1;

    // assign the defaults to smarty so delete can use it
    $this->assign('discountValue', $defaults);

    foreach ($this->_multiValued as $mv => $info) {
      if (! empty($defaults[$mv])) {
        $v = substr($defaults[$mv], 1, -1);
        $values = explode(CRM_Core_DAO::VALUE_SEPARATOR, $v);

        $defaults[$mv] = array();
        if (! empty($values)) {
          foreach ($values as $val) {
            $defaults[$mv][] = $val;
          }
        }
      }
    }

    if (! empty($defaults['active_on'] )) {
      list($defaults['active_on']) = CRM_Utils_Date::setDateDefaults($defaults['active_on']);
    }
    if (! empty($defaults['expire_on'] )) {
      list($defaults['expire_on']) = CRM_Utils_Date::setDateDefaults($defaults['expire_on']);
    }

    if (! empty($defaults['organization_id'])) {
      $this->_orgID = $defaults['organization_id'];
      $this->assign('currentOrganization', $defaults['organization_id']);
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
    $element =& $this->add('text',
      'code',
      ts('Code'),
      CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'code'),
      true);
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

    $this->addMoney('amount', ts('Discount'), true, CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'amount'), false);

    $this->add('select', 'amount_type', ts('Amount Type'),
      array(
        1 => ts('Percentage'),
        2 => ts('Monetary')),
      true);

    $this->add('text', 'count_max', ts('Usage'), CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'count_max'), true);
    $this->addRule('count_max', ts('Must be an integer'), 'integer');

    $this->addDate('active_on', ts('Activation Date'), false);
    $this->addDate('expire_on', ts('Expiration Date'), false);

    $this->add('text', 'organization', ts('Organization'));
    $this->add('hidden', 'organization_id', '', array('id' => 'organization_id'));

    $organizationURL = CRM_Utils_System::url('civicrm/ajax/rest', 'className=CRM_Contact_Page_AJAX&fnName=getContactList&json=1&context=contact&org=1&employee_id='.$this->_orgID, false, null, false);
    $this->assign('organizationURL', $organizationURL);

    // is this discount active ?
    $this->addElement('checkbox', 'is_active', ts('Is this discount active?'));

    $this->addElement('checkbox', 'discount_msg_enabled', ts('Display a message to users not eligible for this discount?'));
    $this->add('text', 'discount_msg', ts('Message to users not eligible for discount'), CRM_Core_DAO::getAttribute('CRM_CiviDiscount_DAO_Item', 'discount_msg'));

    // add memberships, events, pricesets
    require_once 'CRM/Member/BAO/MembershipType.php';
    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes(false);
    $autodiscount = $mTypes = array();
    if (! empty($membershipTypes)) {
      $this->_multiValued['autodiscount'] =
      $this->_multiValued['memberships'] = $membershipTypes;

      $this->addElement('advmultiselect',
        'autodiscount',
        ts('Automatic Discount for Members'),
        $membershipTypes,
        array('size' => 5,
          'style' => 'width:auto; min-width:150px;',
          'class' => 'advmultiselect')
      );

      $this->addElement('advmultiselect',
        'memberships',
        ts('Memberships'),
        $membershipTypes,
        array('size' => 5,
          'style' => 'width:auto; min-width:150px;',
          'class' => 'advmultiselect')
      );
    }

    require_once 'CRM/CiviDiscount/Utils.php';
    $events = CRM_CiviDiscount_Utils::getEvents();
    if (! empty($events)) {
      $this->_multiValued['events'] = $events;
      $this->addElement('advmultiselect',
        'events',
        ts('Events'),
        $events,
        array('size' => 5,
          'style' => 'width:auto; min-width:150px;',
          'class' => 'advmultiselect')
      );
    }

    $pricesets = CRM_CiviDiscount_Utils::getPriceSets();
    if (! empty($pricesets)) {
      $this->_multiValued['pricesets'] = $pricesets;
      $this->addElement('advmultiselect',
        'pricesets',
        ts('PriceSets'),
        $pricesets,
        array('size' => 7,
          'style' => 'width:auto; min-width:150px;',
          'class' => 'advmultiselect')
      );
    }
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
      CRM_Core_Session::setStatus(ts('Selected Discount has been deleted.'));
      return;
    }

    if ($this->_action & CRM_Core_Action::COPY) {
      $params = $this->exportValues();
      require_once 'CRM/CiviDiscount/Utils.php';
      $newCode = CRM_CiviDiscount_Utils::randomString('abcdefghjklmnpqrstwxyz23456789', 8);
      CRM_CiviDiscount_BAO_Item::copy($this->_cloneID, $params, $newCode);
      CRM_Core_Session::setStatus(ts('Selected Discount has been duplicated.'));
      return;
    }

    $params = $this->exportValues();

    if ($this->_action & CRM_Core_Action::UPDATE) {
      $params['id'] = $this->_id;
    }
    $params['multi_valued'] = $this->_multiValued;

    $item = CRM_CiviDiscount_BAO_Item::add($params);

    CRM_Core_Session::setStatus(ts('The discount \'%1\' has been saved.',
      array(1 => $item->description ? $item->description : $item->code)));
  }

}
