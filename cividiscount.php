<?php

require_once 'cividiscount.civix.php';

/**
 * Implementation of hook_civicrm_install()
 */
function cividiscount_civicrm_install() {
  return _cividiscount_civix_civicrm_install();
}

/**
 * Implementation of hook_civicrm_uninstall()
 */
function cividiscount_civicrm_uninstall() {
  return _cividiscount_civix_civicrm_uninstall();
}

/**
 * Implementation of hook_civicrm_config()
 */
function cividiscount_civicrm_config(&$config) {
  _cividiscount_civix_civicrm_config($config);
}

/**
 * Implementation of hook_civicrm_perm()
 *
 */
function cividiscount_civicrm_perm() {
  return array('view CiviDiscount', 'administer CiviDiscount');
}

/**
 * Implementation of hook_civicrm_xmlMenu
 */
function cividiscount_civicrm_xmlMenu(&$files) {
  _cividiscount_civix_civicrm_xmlMenu($files);
}

/**
 * Implementation of hook_civicrm_enable
 */
function cividiscount_civicrm_enable() {
  return _cividiscount_civix_civicrm_enable();
}

/**
 * Implementation of hook_civicrm_disable
 */
function cividiscount_civicrm_disable() {
  return _cividiscount_civix_civicrm_disable();
}

/**
 * Implementation of hook_civicrm_upgrade
 *
 * @param $op string, the type of operation being performed; 'check' or 'enqueue'
 * @param CRM_Queue_Queue $queue  (for 'enqueue') the modifiable list of pending up upgrade tasks
 *
 * @return mixed  based on op. for 'check', returns array(boolean) (TRUE if upgrades are pending)
 *                for 'enqueue', returns void
 */
function cividiscount_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _cividiscount_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implementation of hook_civicrm_managed
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 * @param array $entities
 */
function cividiscount_civicrm_managed(&$entities) {
  return _cividiscount_civix_civicrm_managed($entities);
}

/**
 * Implementation of hook_civicrm_tabs()
 *
 * Display a discounts tab listing discount code usage for that contact.
 */
function cividiscount_civicrm_tabs(&$tabs, $cid) {
  if (_cividiscount_is_org($cid)) {
    $count = _cividiscount_get_tracking_count_by_org($cid);
    $a = array(
      'id' => 'discounts',
      'count' => $count,
      'title' => 'Codes Assigned',
      'weight' => '998',
    );
    if ($count > 0) {
      $a['url'] = CRM_Utils_System::url('civicrm/cividiscount/usage', "reset=1&oid={$cid}&snippet=1", false, null, false);
    }
    $tabs[] = $a;
  }

  $count = _cividiscount_get_tracking_count($cid);
  $a = array(
    'id' => 'discounts',
    'count' => $count,
    'title' => 'Codes Redeemed',
    'weight' => '999',
  );
  if ($count > 0) {
    $a['url'] = CRM_Utils_System::url('civicrm/cividiscount/usage', "reset=1&cid={$cid}&snippet=1", false, null, false);
  }
  $tabs[] = $a;
}

/**
 * Implementation of hook_civicrm_buildForm()
 *
 * If the event id of the form being loaded has a discount code, modify
 * the form to include the textfield. Only display the textfield on the
 * initial registration screen.
 *
 * Works for events and membership.
 *
 * @param string $fname
 * @param CRM_Core_Form $form
 */
function cividiscount_civicrm_buildForm($fname, &$form) {
  // skip for delete action
  // also skip when content is loaded via ajax, like payment processor, custom data etc
  $snippet = CRM_Utils_Request::retrieve('snippet', 'String', CRM_Core_DAO::$_nullObject, false, null, 'REQUEST');

  if ( $snippet || ( $form->getVar('_action') && ($form->getVar('_action') & CRM_Core_Action::DELETE ) ) ) {
    return false;
  }

  // Display discount textfield for offline membership/events
  if (in_array($fname, array(
        'CRM_Contribute_Form_Contribution',
        'CRM_Event_Form_Participant',
        'CRM_Member_Form_Membership',
        'CRM_Member_BAO_Membership',
        'CRM_Member_Form_MembershipRenewal'
      ))) {

    if ($form->getVar('_single') == 1 || in_array($form->getVar('_context'), array('membership', 'standalone'))) {
      _cividiscount_add_discount_textfield($form);
      $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));
      if ($code) {
        $defaults = array('discountcode' => $code);
        $form->setDefaults($defaults);
      }
    }
  }
  else if (in_array($fname, array(
            'CRM_Event_Form_Registration_Register',
            //'CRM_Event_Form_Registration_AdditionalParticipant',
            'CRM_Contribute_Form_Contribution_Main',
          ))) {

    // Display the discount textfield for online events (including
    // pricesets) and memberships.
    $ids = $memtypes = array();
    $addDiscountField = FALSE;

    if ( in_array($fname, array(
      'CRM_Event_Form_Registration_Register',
      //'CRM_Event_Form_Registration_AdditionalParticipant'
    ))) {
      $discountCalculator = new CRM_CiviDiscount_DiscountCalculator('event', $form->getVar('_eventId'), $form->getContactID(), NULL, TRUE);
      $addDiscountField = $discountCalculator->isShowDiscountCodeField();
    }
    elseif ($fname == 'CRM_Contribute_Form_Contribution_Main') {
      $ids = _cividiscount_get_discounted_membership_ids();
      if(!empty($form->_membershipBlock['membership_types'])){
        $memtypes = explode(',', $form->_membershipBlock['membership_types']);
      }
      elseif(isset($form->_membershipTypeValues)){
        $memtypes = array_keys($form->_membershipTypeValues);
      }
      if(count(array_intersect($ids, $memtypes)) > 0){
        $addDiscountField = TRUE;
      }
    }

    if (empty($ids)) {
      $ids = _cividiscount_get_discounted_priceset_ids();

      if (!empty($ids)) {
        if(in_array($form->getVar('_eventId'), $ids)){
          $addDiscountField = TRUE;
        }
      }
    }

    // Try to add the textfield. If in a multi-step form, hide the textfield
    // but preserve the value for later processing.
    if ($addDiscountField) {
        _cividiscount_add_discount_textfield($form);
        $code = CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST');
        if ($code) {
          $defaults = array('discountcode' => $code);
          $form->setDefaults($defaults);
        }
      }
  }
}

/**
 * Implementation of hook_civicrm_validateForm()
 *
 * Used in the initial event registration screen.
 *
 * @param string $name
 * @param array $fields reference
 * @param array $files
 * @param CRM_Core_Form $form
 * @param $errors
 */
function cividiscount_civicrm_validateForm($name, &$fields, &$files, &$form, &$errors) {
  if (!in_array($name, array(
    'CRM_Contribute_Form_Contribution_Main',
    'CRM_Event_Form_Participant',
    'CRM_Event_Form_Registration_Register',
    //'CRM_Event_Form_Registration_AdditionalParticipant',
    'CRM_Member_Form_Membership',
    'CRM_Member_Form_MembershipRenewal'
  ))) {
    return;
  }

  // _discountInfo is assigned in cividiscount_civicrm_buildAmount() or
  // cividiscount_civicrm_membershipTypeValues() when a discount is used.
  $discounts = $form->get('_discountInfo');

  $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));
  $ok = FALSE;
  $newerrors = array();
  foreach ($discounts['discount'] as $discountInfo) {
    if ((!$discountInfo || empty($discounts['autodiscount'])) && $code != '') {
      if (!$discountInfo) {
        $newerrors['discountcode'] = ts('The discount code you entered is invalid.');
        continue;
      }

      $discount = $discountInfo['discount'];

      if ($discount['count_max'] > 0) {
        // Initially 1 for person registering.
        $additionalParticipantCount = 1;
        $sv = $form->getVar('_submitValues');
        if (array_key_exists('additional_participants', $sv)) {
          $additionalParticipantCount += $sv['additional_participants'];
        }
        if (($discount['count_use'] + $additionalParticipantCount) > $discount['count_max']) {
          $newerrors['discountcode'] = ts('There are not enough uses remaining for this code.');
        }
      }
      // it's all OK - there is a code that is fine - even if there are some that aren't
      $ok = TRUE;
    }
  }
  if(!$ok) {
    $errors = array_merge($errors, $newerrors);
  }
}

/**
 * Implementation of hook_civicrm_buildAmount()
 *
 * If the event id of the form being loaded has a discount code, calculate the
 * the discount and update the price and label. Apply the initial autodiscount
 * based on a users membership.
 *
 * Check all priceset items and only apply the discount to the discounted items.
 *
 * @param string $pageType
 * @param CRM_Core_Form $form
 * @param $amounts
 */
function cividiscount_civicrm_buildAmount($pageType, &$form, &$amounts) {
  if (( !$form->getVar('_action')
        || ($form->getVar('_action') & CRM_Core_Action::PREVIEW)
        || ($form->getVar('_action') & CRM_Core_Action::ADD)
      )
    && !empty($amounts) && is_array($amounts) &&
      ($pageType == 'event' || $pageType == 'membership')) {

    if (!$pageType == 'membership' && in_array(get_class($form), array(
      'CRM_Contribute_Form_Contribution',
      'CRM_Contribute_Form_Contribution_Main',
    ))) {
      return;
    }

    $contact_id = _cividiscount_get_form_contact_id($form);
    $autodiscount = $applyToAllLineItems = FALSE;
    $eid = $form->getVar('_eventId');
    $priceSetID = $form->get('priceSetId');
    $v = $form->getVar('_values');
    $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));

    if (!empty($v['currency'])) {
      $currency = $v['currency'];
    } elseif (!empty($v['event']['currency'])) {
      $currency = $v['event']['currency'];
    }
    else {
      $currency = CRM_Core_Config::singleton()->defaultCurrency;
    }

    // If additional participants are not allowed to receive a discount we need
    // to interrupt the form processing on build and POST.
    // This is a potential landmine if the form processing ever changes in Civi.
    if (!_cividiscount_allow_multiple()) {
      // POST from participant form to confirm page
      if ($form->getVar('_lastParticipant') == 1) {
        return;
      }
      // On build participant form
      $keys = array_keys($_GET);
      foreach ($keys as $key) {
        if (substr($key, 0, 16) == "_qf_Participant_") {
          // We can somewhat safely assume we're in the additional participant
          // registration form.
          // @todo what is the effect of this?
          if ($_GET[$key] == 'true') {
            return;
          }
        }
      }
    }

    $form->set('_discountInfo', NULL);
    $discountCalculator = new CRM_CiviDiscount_DiscountCalculator($pageType, $eid, $contact_id, $code, FALSE);
    $discounts = $discountCalculator->getDiscounts();
     if(!empty($code) && empty($discounts)) {
      $form->set('discountCodeErrorMsg', ts('The discount code you entered is invalid.'));
    }

    if (empty($discounts)) {
      $statusMessage = $discountCalculator->getDiscountUnavailableMessage();
      if($statusMessage) {
        CRM_Core_Session::setStatus(html_entity_decode($statusMessage), '', 'no-popup');
      }
      return;
    }

    /* @todo refactor into separate function fo clarity (along with the long comment block as a function
     * level comment block
     * here we apply discount to line items
    // There are three scenarios:
    // 1. Discount is configured membership type so we apply based on the type
    // 2. Discount is configure at price field level, in this case discount should be applied only for
    //    that particular price set field.
     * 3. Discount is applied to an event but line items have not been specified - in that case apply to all
    //retrieve price set field associated with this priceset
     *
     */
    $priceSetInfo = CRM_CiviDiscount_Utils::getPriceSetsInfo($priceSetID);
    $originalAmounts = $amounts;
    foreach ($discounts as $discount) {
      $autodiscount = CRM_Utils_Array::value('is_auto_discount', $discount);
      $priceFields = isset($discount['pricesets']) ? $discount['pricesets'] : array();
      if(empty($priceFields)) {
        if($pageType == 'event') {
          $applyToAllLineItems = TRUE;;
        }
        else {
          // filter only valid membership types that have discount
          foreach($priceSetInfo as $pfID => $priceFieldValues) {
            if (!empty($priceFieldValues['membership_type_id']) &&
            in_array($priceFieldValues['membership_type_id'], CRM_Utils_Array::value('memberships', $discount, array()))) {
              $priceFields[$pfID] = $pfID;
            }
          }
        }
      }

      $additionalParticipantCount = _cividiscount_checkEventDiscountMultipleParticipants($pageType, $form, $discount);
      if(empty($additionalParticipantCount)) {
        continue;
      }

      foreach ($amounts as $fee_id => &$fee) {
        if (!is_array($fee['options'])) {
          continue;
        }

        foreach ($fee['options'] as $option_id => &$option) {
          if ($applyToAllLineItems || CRM_Utils_Array::value($option['id'], $priceFields)) {
            $originalLabel = $originalAmounts[$fee_id]['options'][$option_id]['label'];
            $originalAmount = (integer) $originalAmounts[$fee_id]['options'][$option_id]['amount'];
            list($amount, $label) =
              _cividiscount_calc_discount($originalAmount, $originalLabel, $discount, $autodiscount, $currency);
            $discountAmount = $originalAmounts[$fee_id]['options'][$option_id]['amount'] - $amount;
            if($discountAmount > CRM_Utils_Array::value('discount_applied', $option)) {
              $option['amount'] = $amount;
              $option['label'] = $label;
              $option['discount_applied'] = $discountAmount;
            }
          }
        }
        $discountApplied = TRUE;
      }
    }
    // this seems to incorrectly set to only the last discount but it seems not to matter in the way it is used
    if ($discountApplied) {
      $form->set('_discountInfo', array(
        'discount' => $discounts,
        'autodiscount' => $autodiscount,
        'contact_id' => $contact_id,
      ));
    }
  }
}


/**
 *  Retrieve the contact_id depending on submission context.
 *  Javascript buildFeeBlock() participantId is mapped to _pId.
 * @see templates/CRM/Event/Form/Participant.tpl
 * @see CRM/Event/Form/EventFees.php
 * @todo - this functionality should be (is??) in the form parent class
 *  - from 4.4 it probably is & takes into account cid=0
 * @param form
 * @return integer $contact_id
 */


/**
 * we need a extra check to make sure discount is valid for additional participants
 * check the max usage and existing usage of discount code
 *
 * @param string $pageType
 * @param CRM_Core_Form $form
 * @param array $discount
 *
 * @return bool|int
 */
function _cividiscount_checkEventDiscountMultipleParticipants($pageType, &$form, $discount) {
  $additionalParticipantCount = 1;
  if ($pageType == 'event' && _cividiscount_allow_multiple()) {
    if ($discount['count_max'] > 0) {
      // Initially 1 for person registering.
      $additionalParticipantCount = 1;

      $sv = $form->getVar('_submitValues');
      if (array_key_exists('additional_participants', $sv)) {
        $additionalParticipantCount += $sv['additional_participants'];
      }
      if (($discount['count_use'] + $additionalParticipantCount) > $discount['count_max']) {
        $form->set('discountCodeErrorMsg', ts('There are not enough uses remaining for this code.'));
        return FALSE;
      }
    }
  }
  return $additionalParticipantCount;
}

/**
 * @param CRM_Core_Form $form
 *
 * @return mixed
 */
function _cividiscount_get_form_contact_id($form) {
  if (!empty($form->_pId)) {
    $contact_id = $form->_pId;
  }
  // Look for contact_id in the form.
  else if ($form->getVar('_contactID')) {
    $contact_id = $form->getVar('_contactID');
  }
  // note that contact id variable is not consistent on some forms hence we need this double check :(
  // we need to clean up CiviCRM code sometime in future
  else if ($form->getVar('_contactId')) {
    $contact_id = $form->getVar('_contactId');
  }
  // Otherwise look for contact_id in submit values.
  else if (!empty($form->_submitValues['contact_select_id'][1])) {
    $contact_id = $form->_submitValues['contact_select_id'][1];
  }
  // Otherwise use the current logged-in user.
  else {
    $contact_id = CRM_Core_Session::singleton()->get('userID');
  }
  return $contact_id;
}

/**
 * Implementation of hook_civicrm_membershipTypeValues()
 *
 * Allow discounts to be applied to renewing memberships.
 *
 * @param CRM_Core_Form $form
 * @param array $membershipTypeValues
 */
function cividiscount_civicrm_membershipTypeValues(&$form, &$membershipTypeValues) {
  // Ignore the thank you page.
  if ($form->getVar('_name') == 'ThankYou') {
    return;
  }

  // Only discount new or renewal memberships.
  if (!($form->getVar('_action') & (CRM_Core_Action::ADD | CRM_Core_Action::RENEW))) {
    return;
  }

  // Retrieve the contact_id depending on submission context.
  // Look for contact_id in the form.
  if ($form->getVar('_contactID')) {
    $contact_id = $form->getVar('_contactID');
  }
  // Otherwise look for contact_id in submit values.
  else if (!empty($form->_submitValues['contact_select_id'][1])) {
    $contact_id = $form->_submitValues['contact_select_id'][1];
  }
  // Otherwise use the current logged-in user.
  else {
    $contact_id = CRM_Core_Session::singleton()->get('userID');
  }

  $form->set('_discountInfo', NULL);
  $code = CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST');
  $discountCalculator = new CRM_CiviDiscount_DiscountCalculator('membership', NULL, $contact_id, $code, FALSE);

  $discounts = $discountCalculator->getDiscounts();
  if(!empty($code) && empty($discounts)) {
    $form->set( 'discountCodeErrorMsg', ts('The discount code you entered is invalid.'));
  }
  if (empty($discounts)) {
    return;
  }
  $discount = array_shift($discounts);
  foreach ($membershipTypeValues as &$values) {
    if (!empty($discount['memberships']) && CRM_Utils_Array::value($values['id'], $discount['memberships'])) {
      list($value, $label) = _cividiscount_calc_discount($values['minimum_fee'], $values['name'], $discount, $discountCalculator->isAutoDiscount());
      $values['minimum_fee'] = $value;
      $values['name'] = $label;
    }
  }

  $form->set('_discountInfo', array(
    'discount' => $discount,
    'autodiscount' => $discountCalculator->isAutoDiscount(),
    'contact_id' => $contact_id,
  ));
}

/**
 * Implementation of hook_civicrm_postProcess()
 *
 * Record information about a discount use.
 *
 * @param string $class
 * @param CRM_Core_Form $form
 */
function cividiscount_civicrm_postProcess($class, &$form) {
  if (!in_array($class, array(
    'CRM_Contribute_Form_Contribution_Confirm',
    'CRM_Event_Form_Participant',
    'CRM_Event_Form_Registration_Confirm',
    'CRM_Member_Form_Membership',
    'CRM_Member_Form_MembershipRenewal'
  ))) {
    return;
  }

  $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));
  $discountInfo = $form->get('_discountInfo');
  $discounts = $discountInfo['discount'];
  if($code) {
    if(empty($discounts[$code])) {
      return;
    }
    $discount = $discounts[$code];
  }
  elseif (empty($discountInfo['autodiscount'])) {
    return;
  }
  else {
    $discount = reset($discounts);
  }

  $params = $form->getVar('_params');

  $trackingItem = array(
    'item_id' => $discount['id'],
    'description' => CRM_Utils_Array::value('amount_level', $params),
  );

  // Online event registration.
  // Note that CRM_Event_Form_Registration_Register is an intermediate form.
  // CRM_Event_Form_Registration_Confirm completes the transaction.
  if ($class == 'CRM_Event_Form_Registration_Confirm') {
    $pids = $form->getVar('_participantIDS');

    // if multiple participant discount is not enabled then only use primary participant info for discount
    // and ignore additional participants
    if (!_cividiscount_allow_multiple()) {
      $pids = array($pids[0]);
    }

    foreach ($pids as $pid) {
      $participant = _cividiscount_get_participant($pid);
      $contact_id = $participant['contact_id'];
      $participant_payment = _cividiscount_get_participant_payment($pid);
      $$trackingItem['contribution_id'] = $participant_payment['contribution_id'];

      $trackingItem['entity_id'] = $pid;
      $trackingItem['entity_table'] = 'civicrm_participant';
      $trackingItem['contact_id'] = $participant['contact_id'];
      CRM_CiviDiscount_BAO_Item::incrementUsage($discount['id'], $trackingItem);
    }

  // Online membership.
  // Note that CRM_Contribute_Form_Contribution_Main is an intermediate
  // form - CRM_Contribute_Form_Contribution_Confirm completes the
  // transaction.
  } else if ($class == 'CRM_Contribute_Form_Contribution_Confirm') {
    $membership_type = $params['selectMembership'];
    $membershipId = $params['membershipID'];

    // check to make sure the discount actually applied to this membership.
    if (!CRM_Utils_Array::value($membership_type, $discount['memberships']) || !$membershipId) {
      return;
    }

    $membership = _cividiscount_get_membership($membershipId);
    $trackingItem['contact_id'] = $membership['contact_id'];
    $membership_payment = _cividiscount_get_membership_payment($membershipId);
    $trackingItem['contribution_id'] = $membership_payment['contribution_id'];
    $trackingItem['entity_id'] = $membershipId;
    $trackingItem['entity_table'] = 'civicrm_membership';

    CRM_CiviDiscount_BAO_Item::incrementUsage($discount['id'], $trackingItem);
  }
  else {
    $contribution_id = NULL;
    // Offline event registration.
    if ($class =='CRM_Event_Form_Participant') {
      $entity_id = $form->getVar('_id');
      $participant_payment = _cividiscount_get_participant_payment($entity_id);
      $contribution_id = $participant_payment['contribution_id'];
      $entity_table = 'civicrm_participant';

      $participant = _cividiscount_get_participant($entity_id);
      $contact_id = $participant['contact_id'];
    }
    // Offline membership.
    elseif ( in_array($class, array('CRM_Member_Form_Membership','CRM_Member_Form_MembershipRenewal') ) ) {
      $membership_types = $form->getVar('_memTypeSelected');
      $membership_type = isset($membership_types[0]) ? $membership_types[0] : NULL;

      if (!$membership_type) {
        $membership_type = $form->getVar('_memType');
      }

      // Check to make sure the discount actually applied to this membership.
      if (!CRM_Utils_Array::value($membership_type, $discount['memberships'])) {
        return;
      }

      $trackingItem['entity_table'] = 'civicrm_membership';
      $trackingItem['entity_id'] = $entity_id = $form->getVar('_id');

      $membership_payment = _cividiscount_get_membership_payment($entity_id);
      $trackingItem['contribution_id'] = $membership_payment['contribution_id'];
      $trackingItem['description'] = CRM_Utils_Array::value('description', $params);

      $membership = _cividiscount_get_membership($entity_id);
      $trackingItem['contact_id'] = $membership['contact_id'];
    }
    else {
      $trackingItem['entity_table'] = 'civicrm_contribution';
      $trackingItem['entity_id'] = $contribution_id;
    }

    CRM_CiviDiscount_BAO_Item::incrementUsage($discount['id'], $trackingItem);
  }
}

/**
 * For participant and member delete, decrement the code usage value since
 * they are no longer using the code.
 * When a contact is deleted, we should also delete their tracking info/usage.
 * When removing participant (and additional) from events, also delete their tracking info/usage.
 *
 * @param $op
 * @param $name
 * @param $id
 * @param $obj
 */
function cividiscount_civicrm_pre($op, $name, $id, &$obj) {
  if ($op == 'delete') {
    if ( in_array($name, array('Individual','Household','Organization')) ) {
      $result = _cividiscount_get_item_id_by_track(null, null, $id);
    }
    elseif ($name == 'Participant') {
      if (($result = _cividiscount_get_participant($id)) && ($contactid = $result['contact_id'])) {
        $result = _cividiscount_get_item_id_by_track('civicrm_participant', $id, $contactid);
      }
    }
    else if ($name == 'Membership') {
      if (($result = _cividiscount_get_membership($id)) && ($contactid = $result['contact_id'])) {
        $result = _cividiscount_get_item_id_by_track('civicrm_membership', $id, $contactid);
      }
    }
    else {
      return;
    }

    if (!empty($result)) {
      foreach ( $result as $value ) {
        if (!empty($value['item_id'])) {
          CRM_CiviDiscount_BAO_Item::decrementUsage($value['item_id']);
        }

        if (!empty($value['id'])) {
          CRM_CiviDiscount_BAO_Track::del($value['id']);
        }
      }
    }
  }
}

/**
 * @return array of all discount codes.
 */
function _cividiscount_get_discounts() {
  return CRM_CiviDiscount_BAO_Item::getValidDiscounts();
}

/**
 * @param $discounts
 * @param $key
 * @param bool $include_autodiscount
 *
 * @return array all items within the field specified by 'key' for all discounts.
 */
function _cividiscount_get_items_from_discounts($discounts, $key, $include_autodiscount = FALSE) {
  $items = array();
  foreach ($discounts as $discount) {
    if ($include_autodiscount || empty($discount['autodiscount'])) {
      foreach ($discount[$key] as $v) {
        $items[$v] = $v;
      }
    }
  }

  return $items;
}

/**
 * @return array of all discountable priceset ids.
 */
function _cividiscount_get_discounted_priceset_ids() {
  return _cividiscount_get_items_from_discounts(_cividiscount_get_discounts(), 'pricesets');
}

/**
 * @return array of all discountable membership ids.
 */
function _cividiscount_get_discounted_membership_ids() {
  return _cividiscount_get_items_from_discounts(_cividiscount_get_discounts(), 'memberships');
}

/**
 * Get discounts that apply to at least one of the specified memberships.
 * @param array $discounts
 * @param array $membershipTypeValues
 *
 * @return array
 */
function _cividiscount_filter_membership_discounts($discounts, $membershipTypeValues) {
  $mids = array_map(function($elt) { return $elt['id']; }, $membershipTypeValues);

  $tempDiscounts = array();
  foreach ($discounts as $code => $discount) {
    if (count(array_intersect($discount['memberships'], $mids)) > 0) {
      $tempDiscounts[$code] = $discount;
    }
  }

  return $tempDiscounts;
}

/**
 * Calculate either a monetary or percentage discount.
 * @param float $amount
 * @param string $label
 * @param array $discount
 * @param bool $autodiscount
 * @param string $currency
 *
 * @return array
 */
function _cividiscount_calc_discount($amount, $label, $discount, $autodiscount, $currency = 'USD') {
  $title = $autodiscount ? 'Member Discount' : "Discount {$discount['code']}";
  if ($discount['amount_type'] == '2') {
    $newAmount = CRM_Utils_Rule::cleanMoney($amount) - CRM_Utils_Rule::cleanMoney($discount['amount']);
    $fmt_discount = CRM_Utils_Money::format($discount['amount'], $currency);
    $newLabel = $label . " ({$title}: {$fmt_discount} {$discount['description']})";

  }
  else {
    $newAmount = $amount - ($amount * ($discount['amount'] / 100));
    $newLabel = $label ." ({$title}: {$discount['amount']}% {$discount['description']})";
  }

  $newAmount = round($newAmount, 2);
  // Return a formatted string for zero amount.
  // @see http://issues.civicrm.org/jira/browse/CRM-12278
  if ($newAmount <= 0) {
    $newAmount = '0.00';
  }

  return array($newAmount, $newLabel);
}

/**
 * Returns TRUE if the code should allow multiple participants.
 *
 * TODO: Add settings for admin to set this.
 */
function _cividiscount_allow_multiple() {
  return TRUE;
}

/**
 * @param integer $cid
 *
 * @return string
 */
function _cividiscount_get_tracking_count($cid) {
  $sql = "SELECT count(id) as count FROM cividiscount_track WHERE contact_id = $cid";
  $count = CRM_Core_DAO::singleValueQuery($sql, array());

  return $count;
}

/**
 * @param $cid
 *
 * @return string
 */
function _cividiscount_get_tracking_count_by_org($cid) {
  $sql = "SELECT count(id) as count FROM cividiscount_item WHERE organization_id = $cid";
  $count = CRM_Core_DAO::singleValueQuery($sql, array());

  return $count;
}

/**
 * @param $table
 * @param $eid
 * @param $cid
 *
 * @return array
 */
function _cividiscount_get_item_id_by_track($table, $eid, $cid) {
  if (!$table) {
    $entityTableClause = "entity_table IN ('civicrm_membership','civicrm_participant')";
  }
  else {
    $entityTableClause = "entity_table = '{$table}' AND entity_id = {$eid}";
  }
  $sql = "SELECT id, item_id FROM cividiscount_track WHERE {$entityTableClause} AND contact_id = $cid";
  $dao = CRM_Core_DAO::executeQuery($sql, array());
  $discountEntries = array();
  while ($dao->fetch()) {
    $discountEntries[] = array('id' => $dao->id, 'item_id' => $dao->item_id);
  }

  return $discountEntries;
}

/**
 * @param $cid
 *
 * @return bool TRUE if contact type is an organization
 */
function _cividiscount_is_org($cid) {
  $sql = "SELECT contact_type FROM civicrm_contact WHERE id = $cid";
  $dao =& CRM_Core_DAO::executeQuery($sql, array());
  while ($dao->fetch()) {
    if ($dao->contact_type == "Organization") {
      return TRUE;
    }
  }

  return FALSE;
}

/**
 * @param int $mid
 *
 * @return bool|mixed
 */
function _cividiscount_get_membership($mid = 0) {
  $result = civicrm_api('Membership', 'get', array('version' => '3', 'membership_id' => $mid));
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

/**
 * @param int $mid
 *
 * @return bool|mixed
 */
function _cividiscount_get_membership_payment($mid = 0) {
  $result = civicrm_api('MembershipPayment', 'get', array('version' => '3', 'membership_id' => $mid));
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

/**
 * @param int $pid
 *
 * @return bool|mixed
 */
function _cividiscount_get_participant($pid = 0) {
  // v3 participant API is broken at the moment.
  // @see http://issues.civicrm.org/jira/browse/CRM-11108
  $result = civicrm_api('Participant', 'get', array('version' => '3', 'participant_id' => $pid, 'participant_test' => 0));
  if ($result['is_error'] == 0 && $result['count'] == 0) {
    $result = civicrm_api('Participant', 'get', array('version' => '3', 'participant_id' => $pid, 'participant_test' => 1));
  }
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

/**
 * @param int $pid
 *
 * @return bool|mixed
 */
function _cividiscount_get_participant_payment($pid = 0) {
  $result = civicrm_api('ParticipantPayment', 'get', array('version' => '3', 'participant_id' => $pid));
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

/**
 * Add the discount textfield to a form
 *
 * @param CRM_Core_Form $form
 */
function _cividiscount_add_discount_textfield(&$form) {
  $form->addElement('text', 'discountcode', ts('If you have a discount code, enter it here'));
  $errorMessage = $form->get('discountCodeErrorMsg');
  if ($errorMessage) {
    $form->setElementError('discountcode', $errorMessage);
  }
  $form->set('discountCodeErrorMsg', null);
  $buttonName = $form->getButtonName('reload');
  $form->addElement('submit', $buttonName, ts('Apply'));
  $template = CRM_Core_Smarty::singleton();
  $beginHookFormElements = (array) $template->get_template_vars('beginHookFormElements');
  $beginHookFormElements[] = 'discountcode';
  $beginHookFormElements[] = $buttonName;
  $form->assign('beginHookFormElements', $beginHookFormElements);
}

/**
 * Add navigation for CiviDiscount under "Administer" menu
 *
 * @param array $params associated array of navigation menus
 */
function cividiscount_civicrm_navigationMenu( &$params ) {
  // get the id of Administer Menu
  $administerMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Navigation', 'Administer', 'id', 'name');

  // skip adding menu if there is no administer menu
  if ($administerMenuId) {
    // get the maximum key under administer menu
    $maxKey = max( array_keys($params[$administerMenuId]['child']));
    $params[$administerMenuId]['child'][$maxKey+1] =  array (
      'attributes' => array (
        'label'      => 'CiviDiscount',
        'name'       => 'CiviDiscount',
        'url'        => 'civicrm/cividiscount&reset=1',
        'permission' => 'administer CiviCRM',
        'operator'   => NULL,
        'separator'  => TRUE,
        'parentID'   => $administerMenuId,
        'navID'      => $maxKey+1,
        'active'     => 1
      )
    );
  }
}

