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
 * Module extensions dont implement this hook as yet, will need to add for 4.2
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
 * @param $queue CRM_Queue_Queue, (for 'enqueue') the modifiable list of pending up upgrade tasks
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
    $tabs[] = array(
      'id' => 'discounts',
      'count' => $count,
      'title' => ts('Codes Assigned'),
      'weight' => '98',
      'url' => CRM_Utils_System::url('civicrm/cividiscount/usage', "reset=1&oid={$cid}", false, null, false),
    );
  }

  $count = _cividiscount_get_tracking_count($cid);
  $tabs[] = array(
    'id' => 'discounts',
    'count' => $count,
    'title' => ts('Codes Redeemed'),
    'weight' => '99',
    'url' => CRM_Utils_System::url('civicrm/cividiscount/usage', "reset=1&cid={$cid}", false, null, false),
  );
}

/**
 * Implementation of hook_civicrm_buildForm()
 *
 * If the event id of the form being loaded has a discount code, modify
 * the form to include the textfield. Only display the textfield on the
 * initial registration screen.
 *
 * Works for events and membership.
 */
function cividiscount_civicrm_buildForm($fname, &$form) {
  // skip for delete action
  // also skip when content is loaded via ajax, like payment processor, custom data etc
  $snippet = CRM_Utils_Request::retrieve('snippet', 'String', CRM_Core_DAO::$_nullObject, false, null, 'REQUEST');

  if ( $snippet == 4 || ( $form->getVar('_action') && ($form->getVar('_action') & CRM_Core_Action::DELETE ) ) ) {
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
      $code = CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST');
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
            'CRM_Event_Form_ParticipantFeeSelection',
          ))) {

    // Display the discount textfield for online events (including
    // pricesets) and memberships.
    $ids = $memtypes = array();
    $addDiscountField = FALSE;

    if ( in_array($fname, array(
      'CRM_Event_Form_Registration_Register',
      //'CRM_Event_Form_Registration_AdditionalParticipant'
      'CRM_Event_Form_ParticipantFeeSelection',
    ))) {
      $contact_id = _cividiscount_get_form_contact_id($form);
      $discountCalculator = new CRM_CiviDiscount_DiscountCalculator('event', $form->getVar('_eventId'), $contact_id, NULL, TRUE);
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
        $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));
        if (empty($code) && $fname == 'CRM_Event_Form_ParticipantFeeSelection') {
          $code = _cividiscount_get_item_by_track('civicrm_participant', $form->getVar('_participantId'), $contact_id, TRUE);
        }
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
  $discountInfo = $form->get('_discountInfo');

  $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));

  if ((!$discountInfo || !$discountInfo['autodiscount']) && trim($code) != '') {

    if (!$discountInfo) {
      $errors['discountcode'] = ts('The discount code you entered is invalid.');
      return;
    }

    $discount = $discountInfo['discount'];

    if ($discount['count_max'] > 0) {
      // Initially 1 for person registering.
      $apcount = 1;
      $sv = $form->getVar('_submitValues');
      if (array_key_exists('additional_participants', $sv)) {
        $apcount += $sv['additional_participants'];
      }
      if (($discount['count_use'] + $apcount) > $discount['count_max']) {
        $errors['discountcode'] = ts('There are not enough uses remaining for this code.');
      }
    }
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
 */
function cividiscount_civicrm_buildAmount($pagetype, &$form, &$amounts) {
  if (( !$form->getVar('_action')
        || ($form->getVar('_action') & CRM_Core_Action::PREVIEW)
        || ($form->getVar('_action') & CRM_Core_Action::ADD)
        || ($form->getVar('_action') & CRM_Core_Action::UPDATE)
      )
    && !empty($amounts) && is_array($amounts) &&
      ($pagetype == 'event' || $pagetype == 'membership')) {

    if (!$pagetype == 'membership' && in_array(get_class($form), array(
      'CRM_Contribute_Form_Contribution',
      'CRM_Contribute_Form_Contribution_Main',
    ))) {
      return;
    }

    $contact_id = _cividiscount_get_form_contact_id($form);
    $autodiscount = FALSE;
    $eid = $form->getVar('_eventId');
    $psid = $form->get('priceSetId');
    $ps = $form->get('priceSet');
    $v = $form->getVar('_values');

    $code = trim(CRM_Utils_Request::retrieve('discountcode', 'String', $form, false, null, 'REQUEST'));
    if (!array_key_exists('discountcode', $form->_submitValues)
      && ($pid = $form->getVar('_participantId'))
      && ($form->getVar('_action') & CRM_Core_Action::UPDATE)
    ) {
      $code = _cividiscount_get_item_by_track('civicrm_participant', $pid, $contact_id, TRUE);
    }

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
    $dicountCalculater = new CRM_CiviDiscount_DiscountCalculator($pagetype, $eid, $contact_id, $code, FALSE);
    $discounts = $dicountCalculater->getDiscounts();
     if (!empty($code) && empty($discounts)) {
       $form->set( 'discountCodeErrorMsg', ts('The discount code you entered is invalid.'));
    }

    if (empty($discounts)) {
      // Check if a discount is available
      if ($pagetype == 'event') {
        $discounts = _cividiscount_get_discounts();
        foreach ($discounts as $code => $discount) {
          if (isset($discount['events']) && array_key_exists($eid, $discount['events']) &&
                $discount['discount_msg_enabled']) {
            // Display discount available message
            CRM_Core_Session::setStatus(html_entity_decode($discount['discount_msg']), '', 'no-popup');
          }
        }
      }
      return;
    }

    // here we check if discount is configured for events or for membership types.
    // There are two scenarios:
    // 1. Discount is configure for the event or membership type, in that case we should apply discount only
    //    if default fee / membership type is configured. ( i.e price set with quick config true )
    // 2. Discount is configure at price field level, in this case discount should be applied only for
    //    that particular price set field.

    // here we need to check if selected price set is quick config
    $isQuickConfigPriceSet = CRM_CiviDiscount_Utils::checkForQuickConfigPriceSet($psid);

    $keys = array_keys($discounts);
    $key = array_shift($keys);

    // in this case discount is specified for event id or membership type id, so we need to get info of
    // associated price set fields. For events discount we already have the list, but for memberships we
    // need to filter at membership type level

    //retrieve price set field associated with this priceset
    $priceSetInfo = CRM_CiviDiscount_Utils::getPriceSetsInfo($psid);
    $originalAmounts = $amounts;
    //$discount = array_shift($discounts);
    foreach ($discounts as $done_care => $discount) {
      if (!empty($dicountCalculater->autoDiscounts) && array_key_exists($done_care, $dicountCalculater->autoDiscounts)) {
        $autodiscount = TRUE;
      }
      else {
        $autodiscount = FALSE;
      }
      $priceFields = isset($discount['pricesets']) ? $discount['pricesets'] : array();
      if (empty($priceFields) && (!empty($code) || $autodiscount)) {
        // apply discount to all the price fields for quickconfig pricesets
        if ($pagetype == 'event' && $isQuickConfigPriceSet) {
          $applyToAllLineItems = TRUE;
          if (!empty($key)) {
            $discounts[$key]['pricesets'] = array_keys($priceSetInfo);
          }
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
      $apcount = _cividiscount_checkEventDiscountMultipleParticipants($pagetype, $form, $discount);
      if (empty($apcount)) {
        //this was set to return but that doesn't make sense as there might be another discount
        continue;
      }

      $discountApplied = FALSE;
      if (!empty($autodiscount) || !empty($code)) {
        foreach ($amounts as $fee_id => &$fee) {
          if (!is_array($fee['options'])) {
            continue;
          }

          foreach ($fee['options'] as $option_id => &$option) {
            if (!empty($applyToAllLineItems) || CRM_Utils_Array::value($option['id'], $priceFields)) {
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
              $discountApplied = TRUE;
            }
          }
        }
      }
    }

    // this seems to incorrectly set to only the last discount but it seems not to matter in the way it is used
    if (isset($discountApplied) && $discountApplied) {
      if (!empty($ps['fields'])) {
        $ps['fields'] = $amounts;
        $form->setVar('_priceSet', $ps);
      }

      $form->set('_discountInfo', array(
        'discount' => $discount,
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
  check the max usage and existing usage of discount code
 * @param pagetype
 * @param form
 * @param array $discount
 */
function _cividiscount_checkEventDiscountMultipleParticipants($pagetype, &$form, $discount) {
  $apcount = 1;
  if ($pagetype == 'event' && _cividiscount_allow_multiple()) {
    if ($discount['count_max'] > 0) {
      // Initially 1 for person registering.
      $apcount = 1;

      $sv = $form->getVar('_submitValues');
      if (array_key_exists('additional_participants', $sv)) {
        $apcount += $sv['additional_participants'];
      }
      if (($discount['count_use'] + $apcount) > $discount['count_max']) {
        $form->set('discountCodeErrorMsg', ts('There are not enough uses remaining for this code.'));
        return FALSE;
      }
    }
  }
  return $apcount;
}

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

  //For anonymous user fetch contact ID on basis of checksum
  if (empty($contact_id)) {
    $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $form);

    if (!empty($cid)) {
      //check if this is a checksum authentication
      $userChecksum = CRM_Utils_Request::retrieve('cs', 'String', $form);
      if ($userChecksum) {
        //check for anonymous user.
        $validUser = CRM_Contact_BAO_Contact_Utils::validChecksum($cid, $userChecksum);
        if ($validUser) {
          return $cid;
        }
      }
    }
  }

  return $contact_id;
}

/**
 * Implementation of hook_civicrm_membershipTypeValues()
 *
 * Allow discounts to be applied to renewing memberships.
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
  if (!empty($code)) {
    $discounts = $discountCalculator->getDiscounts();
  }
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
 */
function cividiscount_civicrm_postProcess($class, &$form) {
  if (!in_array($class, array(
    'CRM_Contribute_Form_Contribution_Confirm',
    'CRM_Event_Form_Participant',
    'CRM_Event_Form_Registration_Confirm',
    'CRM_Member_Form_Membership',
    'CRM_Member_Form_MembershipRenewal',
    'CRM_Event_Form_ParticipantFeeSelection'
  ))) {
    return;
  }

  $discountInfo = $form->get('_discountInfo');
  if (!$discountInfo) {
    return;
  }

  $ts = CRM_Utils_Time::getTime();
  $discount = $discountInfo['discount'];
  $params = $form->getVar('_params');
  $description = CRM_Utils_Array::value('amount_level', $params);

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
      $contribution_id = $participant_payment['contribution_id'];

      CRM_CiviDiscount_BAO_Item::incrementUsage($discount['id']);
      $track = new CRM_CiviDiscount_DAO_Track();
      $track->item_id = $discount['id'];
      $track->contact_id = $contact_id;
      $track->contribution_id = $contribution_id;
      $track->entity_table = 'civicrm_participant';
      $track->entity_id = $pid;
      $track->used_date = $ts;
      $track->description = $description;
      $track->save();
    }

  // Online membership.
  // Note that CRM_Contribute_Form_Contribution_Main is an intermediate
  // form - CRM_Contribute_Form_Contribution_Confirm completes the
  // transaction.
  } else if ($class == 'CRM_Contribute_Form_Contribution_Confirm') {
    $membership_type = $params['selectMembership'];
    $membershipId = $params['membershipID'];

    if (!is_array($membership_type)) {
      $membership_type = array($membership_type);
    }
    $discount_membership_matches = array_intersect($membership_type, $discount['memberships']);
    // check to make sure the discount actually applied to this membership.
    if ( empty($discount_membership_matches) || !$membershipId) {
      return;
    }

    $description = CRM_Utils_Array::value('description', $params);

    $membership = _cividiscount_get_membership($membershipId);
    $contact_id = $membership['contact_id'];
    $membership_payment = _cividiscount_get_membership_payment($membershipId);
    $contribution_id = $membership_payment['contribution_id'];

    CRM_CiviDiscount_BAO_Item::incrementUsage($discount['id']);
    $track = new CRM_CiviDiscount_DAO_Track();
    $track->item_id = $discount['id'];
    $track->contact_id = $contact_id;
    $track->contribution_id = $contribution_id;
    $track->entity_table = 'civicrm_membership';
    $track->entity_id = $membershipId;
    $track->used_date = $ts;
    $track->description = $description;
    $track->save();
  }
  else {
    $contribution_id = NULL;
    // Offline event registration.
    if (in_array($class, array('CRM_Event_Form_Participant', 'CRM_Event_Form_ParticipantFeeSelection'))) {
      if ($class == 'CRM_Event_Form_ParticipantFeeSelection') {
        $entity_id = $form->getVar('_participantId');
      }
      else {
        $entity_id = $form->getVar('_id');
      }
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

      $entity_table = 'civicrm_membership';
      $entity_id = $form->getVar('_id');

      $membership_payment = _cividiscount_get_membership_payment($entity_id);
      $contribution_id = $membership_payment['contribution_id'];
      $description = CRM_Utils_Array::value('description', $params);

      $membership = _cividiscount_get_membership($entity_id);
      $contact_id = $membership['contact_id'];
    }
    else {
      $entity_table = 'civicrm_contribution';
      $entity_id = $contribution_id;
    }

    CRM_CiviDiscount_BAO_Item::incrementUsage($discount['id']);
    $track = new CRM_CiviDiscount_DAO_Track();
    $track->item_id = $discount['id'];
    $track->contact_id = $contact_id;
    $track->contribution_id = $contribution_id;
    $track->entity_table = $entity_table;
    $track->entity_id = $entity_id;
    $track->used_date = $ts;
    $track->description = $description;
    $track->save();
  }
}

/**
 * For participant and member delete, decrement the code usage value since
 * they are no longer using the code.
 * When a contact is deleted, we should also delete their tracking info/usage.
 * When removing participant (and additional) from events, also delete their tracking info/usage.
 */
function cividiscount_civicrm_pre($op, $name, $id, &$obj) {
  if ($op == 'delete') {
    if ( in_array($name, array('Individual','Household','Organization')) ) {
      $result = _cividiscount_get_item_by_track(null, null, $id);
    }
    elseif ($name == 'Participant') {
      if (($result = _cividiscount_get_participant($id)) && ($contactid = $result['contact_id'])) {
        $result = _cividiscount_get_item_by_track('civicrm_participant', $id, $contactid);
      }
    }
    else if ($name == 'Membership') {
      if (($result = _cividiscount_get_membership($id)) && ($contactid = $result['contact_id'])) {
        $result = _cividiscount_get_item_by_track('civicrm_membership', $id, $contactid);
      }
    }
    else {
      return false;
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
 * Returns an array of all discount codes.
 */
function _cividiscount_get_discounts() {
  return CRM_CiviDiscount_BAO_Item::getValidDiscounts();
}

/**
 * Returns all items within the field specified by 'key' for all discounts.
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
 * Returns an array of all discountable priceset ids.
 */
function _cividiscount_get_discounted_priceset_ids() {
  return _cividiscount_get_items_from_discounts(_cividiscount_get_discounts(), 'pricesets');
}

/**
 * Returns an array of all discountable membership ids.
 */
function _cividiscount_get_discounted_membership_ids() {
  return _cividiscount_get_items_from_discounts(_cividiscount_get_discounts(), 'memberships');
}

/**
 * Get discounts that apply to at least one of the specified memberships.
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
 */
function _cividiscount_calc_discount($amount, $label, $discount, $autodiscount, $currency = 'USD') {
  $title = $autodiscount ? 'Member Discount' : "Discount {$discount['code']}";
  if ($discount['amount_type'] == '2') {
    $newamount = CRM_Utils_Rule::cleanMoney($amount) - CRM_Utils_Rule::cleanMoney($discount['amount']);
    $fmt_discount = CRM_Utils_Money::format($discount['amount'], $currency);
    $newlabel = $label . " ({$title}: {$fmt_discount} {$discount['description']})";

  }
  else {
    $newamount = $amount - ($amount * ($discount['amount'] / 100));
    $newlabel = $label ." ({$title}: {$discount['amount']}% {$discount['description']})";
  }

  $newamount = round($newamount, 2);
  // Return a formatted string for zero amount.
  // @see http://issues.civicrm.org/jira/browse/CRM-12278
  if ($newamount <= 0) {
    $newamount = '0.00';
  }

  return array($newamount, $newlabel);
}

/**
 * Returns TRUE if the code is not case sensitive.
 *
 * TODO: Add settings for admin to set this.
 */
function _cividiscount_ignore_case() {
  return TRUE;
}

/**
 * Returns TRUE if the code should allow multiple participants.
 *
 * TODO: Add settings for admin to set this.
 */
function _cividiscount_allow_multiple() {
  return TRUE;
}

function _cividiscount_get_tracking_count($cid) {
  $sql = "SELECT count(id) as count FROM cividiscount_track WHERE contact_id = $cid";
  $count = CRM_Core_DAO::singleValueQuery($sql, array());

  return $count;
}

function _cividiscount_get_tracking_count_by_org($cid) {
  $sql = "SELECT count(id) as count FROM cividiscount_item WHERE organization_id = $cid";
  $count = CRM_Core_DAO::singleValueQuery($sql, array());

  return $count;
}

function _cividiscount_get_item_by_track($table, $eid, $cid, $returnCode = FALSE) {
  $entityTableClause = "entity_table IN ('civicrm_membership','civicrm_participant')";
  if (!empty($eid) && !empty($table)) {
    $entityTableClause = "entity_table = '{$table}' AND entity_id = {$eid}";
  }

  $sql = "SELECT cdt.id as id, item_id, code
FROM cividiscount_track cdt
LEFT JOIN cividiscount_item cdi ON cdt.item_id = cdi.id
WHERE {$entityTableClause} AND contact_id = $cid";
  $dao = CRM_Core_DAO::executeQuery($sql, array());
  $discountEntries = array();
  while ($dao->fetch()) {
    if ($returnCode) {
      return $dao->code;
    }
    $discountEntries[] = array('id' => $dao->id, 'item_id' => $dao->item_id);
  }

  return $discountEntries;
}

/**
 * Returns TRUE if contact type is an organization
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

function _cividiscount_get_membership($mid = 0) {
  $result = civicrm_api('Membership', 'get', array('version' => '3', 'membership_id' => $mid));
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

function _cividiscount_get_membership_payment($mid = 0) {
  $result = civicrm_api('MembershipPayment', 'get', array('version' => '3', 'membership_id' => $mid));
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

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

function _cividiscount_get_participant_payment($pid = 0) {
  $result = civicrm_api('ParticipantPayment', 'get', array('version' => '3', 'participant_id' => $pid));
  if ($result['is_error'] == 0) {
    return array_shift($result['values']);
  }

  return FALSE;
}

/**
 * Add the discount textfield to a form
 */
function _cividiscount_add_discount_textfield(&$form) {
  $element = $form->addElement('text', 'discountcode', ts('If you have a discount code, enter it here'));
  $errorMessage = $form->get('discountCodeErrorMsg');
  if ($errorMessage) {
    $form->setElementError('discountcode', $errorMessage);
  }
  $form->set('discountCodeErrorMsg', null);
  $buttonName = $form->getButtonName('reload');
  $form->addElement('submit', $buttonName, ts('Apply'), array('formnovalidate' => 1));
  $template =& CRM_Core_Smarty::singleton();
  $bhfe = $template->get_template_vars('beginHookFormElements');
  if (!$bhfe) {
    $bhfe = array();
  }
  $bhfe[] = 'discountcode';
  $bhfe[] = $buttonName;
  $form->assign('beginHookFormElements', $bhfe);
}

/**
 * Add navigation for CiviDiscount under "Administer" menu
 *
 * @param $params associated array of navigation menus
 */
function cividiscount_civicrm_navigationMenu( &$params ) {
  // get the id of Administer Menu
  $administerMenuId = CRM_Core_DAO::getFieldValue('CRM_Core_BAO_Navigation', 'Administer', 'id', 'name');

  // skip adding menu if there is no administer menu
  if ($administerMenuId) {
    // get the maximum key under adminster menu
    $maxKey = max( array_keys($params[$administerMenuId]['child']));
    $params[$administerMenuId]['child'][$maxKey+1] =  array (
      'attributes' => array (
        'label'      => 'CiviDiscount',
        'name'       => 'CiviDiscount',
        'url'        => 'civicrm/cividiscount?reset=1',
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

/**
 * Implementation of hook_civicrm_entityTypes
 */
function cividiscount_civicrm_entityTypes(&$entityTypes) {
  $entityTypes['CRM_CiviDiscount_DAO_Item'] = array(
    'name' => 'DiscountCode',
    'class' => 'CRM_CiviDiscount_DAO_Item',
    'table' => 'cividiscount_item'
  );
}
