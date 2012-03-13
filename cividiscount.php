<?php

/**
 *
 * Notes for transition from module to extension:
 *
 * TODO:
 * Properly handle tracking (inc/dec)
 *    _get_code_tracking_count_org()
 *    _get_code_tracking_count()
 *    _decrement_counter()
 *    _increment_counter()
 *    _set_tracking()
 *     
 * Use v3 api
 * Better handle contact type that can "own" a code; currently only Organization
 * _ignore_case() and _allow_multiple() need to get from the admin settings
 * cividiscount_civicrm_tabs() display needs to use registered menu items
 *
 * Would be nice:
 * Better placement of discount textfield in form
 * Switch from serialized arrays to columns for better/easier reporting
 *
 */

/**
 * Implementation of hook_civicrm_config()
 */
function cividiscount_civicrm_config( &$config ) {

    $template =& CRM_Core_Smarty::singleton( );

    $cividiscountRoot = 
        dirname( __FILE__ ) . DIRECTORY_SEPARATOR;

    $cividiscountDir = $cividiscountRoot . 'templates';

    if ( is_array( $template->template_dir ) ) {
        array_unshift( $template->template_dir, $cividiscountDir );
    } else {
        $template->template_dir = array( $cividiscountDir, $template->template_dir );
    }

    // also fix php include path
    $include_path = $cividiscountRoot . PATH_SEPARATOR . get_include_path( );
    set_include_path( $include_path );
}


/**
 * Implementation of hook_civicrm_perm()
 * 
 * Module extensions dont implement this hook as yet, will need to add for 4.2
 */
function cividiscount_civicrm_perm( ) {
    return array('view CiviDiscount', 'administer CiviDiscount');
}


/**
 * Implementation of hook_civicrm_xmlMenu
 */
function cividiscount_civicrm_xmlMenu( &$files ) {

    $files[] =
        dirname( __FILE__ ) . DIRECTORY_SEPARATOR .
        'xml'               . DIRECTORY_SEPARATOR .
        'Menu'              . DIRECTORY_SEPARATOR .
        'cividiscount.xml';
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

    // Display discount textfield for offline membership/events
    $display_forms = array(
            'CRM_Contribute_Form_Contribution',
            'CRM_Member_Form_Membership',
            'CRM_Event_Form_Participant',
            'CRM_Member_BAO_Membership',
    );

    if ( in_array( $fname, $display_forms ) ) {
        // and only when creating new event participant/membership
        // See http://drupal.org/node/1251198 and http://drupal.org/node/1096688
        $formid = $form->getVar( '_id' );

        if ( !empty( $formid ) ) {
            return;
        }

        if ( $form->_single == 1 || $form->_context == 'membership' ) {
            _add_discount_textfield( $form );
        }

        return;
    }

    // Display the discount textfield for online events (including
    // pricesets) and memberships.
    $ids = array( );
    $formid = NULL;

    if ( $fname == 'CRM_Event_Form_Registration_Register' ) {
        $ids = _get_discounted_event_ids( );
        $formid = $form->getVar( '_eventId' );
    } elseif ( $fname == 'CRM_Contribute_Form_Contribution_Main' ) {
        $ids = _get_discounted_membership_ids( );
        $memtypes = explode( ',', $form->_membershipBlock['membership_types'] );

        foreach ( $memtypes as $k => $v ) {

            if ( in_array( $v, $ids ) ) {
                $formid = $v;
            }
        }
    }

    if ( empty( $ids ) ) {
        $psids = _get_discounted_priceset_ids( );

        if ( !empty( $psids ) ) {
            $formid = $form->getVar( '_eventId' );
            $ids = $psids;
        }
    }

    // Try to add the textfield. If in a multi-step form, hide the textfield
    // but preserve the value for later processing.
    if ( $formid != NULL && !empty( $ids ) ) {

        if ( in_array( $formid, array_keys( $ids ) ) ) {
            $display_forms = array(
                'CRM_Event_Form_Registration_Register',
                'CRM_Event_Form_Registration_AdditionalParticipant',
                'CRM_Contribute_Form_Contribution_Main',
            );

            if ( !in_array( $fname, $display_forms ) ) {
                return;
            }

            _add_discount_textfield($form);
            $code = CRM_Utils_Request::retrieve( 'discountcode', 'String', $form, false, null, $_REQUEST );
            if ( $code ) {
                $defaults = array( 'discountcode' => $code );
                $form->setDefaults( $defaults );
                if ( !in_array( $fname, $display_forms ) ) {
                    $form->addElement( 'hidden', 'discountcode', $code );
                }
            }
        }
    }
}


/**
 * Implementation of hook_civicrm_membershipTypeValues()
 * 
 * Allow discounts to also be applied to renewing memberships.
 *
 * XXX: error handling should really live in hook_civicrm_validate(), but
 * membership/contribution forms don't call that hook. Another core patch.
 */
function cividiscount_civicrm_membershipTypeValues(&$form, &$membershipTypeValues) {

    $code = CRM_Utils_Request::retrieve( 'discountcode', 'String', $form, false, null, $_REQUEST);

    // First time the page loads or they didn't enter a code.
    if ( empty( $code ) ) {

        // See if they are eligible for automatic discount. If they are, update the
        // membership values.
        $adids = _get_autodiscounted_ids( );
        $codes = _get_discounts( );
        $code = _verify_autodiscount($codes);
        if ( empty( $code ) ) {
            return;
        }

        $code = _get_code_details($code);
        $mids = _get_discounted_membership_ids( );
        $mid = 0;

        foreach ( $membershipTypeValues as &$values ) {
            if ( in_array( $values['id'], $mids ) ) {
                if ( in_array( $values['id'], unserialize( $code['memberships'] ) ) ) {
                    $mid = $values['id'];
                    list( $value, $label ) = _calc_discount( $values['minimum_fee'], $values['name'], $code );
                    $values['minimum_fee'] = $value;
                    $values['name'] = $label;
                }
            }
        }

        return;
    }

    // ignore the thank you page
    if ( $form->getVar( '_name' ) == 'ThankYou' ) {
        return;
    }

    $code = _get_code_details( $code );

    if ( empty($code) ) {
        CRM_Core_Error::fatal( ts( 'The discount code is not valid for this membership.' ) );
        return;
    }

    if (_is_expired($code)) {
        CRM_Core_Error::fatal( ts( 'The discount code has expired.' ) );
        return;
    }

    if ( $code['count_max'] > 0 && $code['count_use'] >= $code['count_max'] ) {
        CRM_Core_Error::fatal( ts( 'There are not enough uses remaining for this discount code.' ) );
        return;
    }

    $mids = _get_discounted_membership_ids( );
    $mid = 0;

    foreach ( $membershipTypeValues as &$values ) {
        if ( in_array( $values['id'], $mids ) ) {
            if ( in_array( $values['id'], unserialize( $code['memberships'] ) ) ) {
                $mid = $values['id'];
                list( $value, $label ) = _calc_discount( $values['minimum_fee'], $values['name'], $code );
                $values['minimum_fee'] = $value;
                $values['name'] = $label;
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

    if ($pagetype == 'event') {

        $v = $form->getVar('_values');
        $currency = $v['event']['currency'];

        /**
         * If additional participants are not allowed to receive a discount we need
         * to interrupt the form processing on build and POST.
         *
         * This is a potential landmine if the form processing ever changes in Civi.
         */
        if ( !_allow_multiple( ) ) {

            // POST from participant form to confirm page
            if ( $form->getVar( '_lastParticipant' ) == 1 ) {
                if ( !_allow_multiple( ) ) {
                    return;
                }
            }

            // On build participant form
            $keys = array_keys( $_GET );
            foreach ( $keys as $key ) {
                if ( substr( $key, 0, 16 ) == "_qf_Participant_" ) {

                    // We can somewhat safely assume we're in the additional participant registration screen.
                    if ( $_GET[$key] == 'true' ) {
                        return;
                    }
                }
            }
        }

        $codes = _get_discounts( );
        $code = CRM_Utils_Request::retrieve( 'discountcode', 'String', $form, false, null, $_REQUEST );

        if ( !$code ) {
            $code = _verify_autodiscount( $codes );
        }

        $code = _get_code_details( $code );

        if ( !$code ) {
            return;
        }

        if ( _is_expired( $code ) ) {
            return;
        }

        $eid = $form->getVar( '_eventId' );
        $psid = $form->get( 'priceSetId' );
        $eids = _get_discounted_event_ids( );

        if ( !empty( $psid ) ) {
            $feeblock =& $amounts;
            $psids = _get_discounted_priceset_ids( );
            if ( !in_array( $pagetype, array( 'contribution', 'event' ) ) ||
                 !is_array( $feeblock ) ||
                 empty( $feeblock ) ) {
                return;
            }

            if ( $pagetype == 'event' ) {
                if ( !in_array( $eid, $eids ) &&
                     !in_array( $psid, $psids )) {
                  return;
                }
            }

            if ($pagetype == 'contribution') {
                if ( !in_array( get_class( $form ),
                      array( 'CRM_Contribute_Form_Contribution',
                             'CRM_Contribute_Form_Contribution_Main' ) ) ) {
                    return;
                }
            }

            foreach ( $feeblock as &$fee ) {
                if ( !is_array( $fee['options'] ) ) {
                    continue;
                }

                foreach ( $fee['options'] as &$option ) {
                    if ( in_array( $option['id'], $psids ) ) {
                        if ( in_array( $option['id'], unserialize($code['pricesets'] ) ) ) {
                            list( $option['amount'], $option['label'] ) = 
                                _calc_discount( $option['amount'], $option['label'], $code, $currency );
                        }
                    }
                }
            }
        } else {
            if ( in_array( $eid, unserialize( $code['events'] ) ) ) {
                foreach ( $amounts as $aid => $vals ) {
                    list( $amounts[$aid]['value'], $amounts[$aid]['label'] ) =
                        _calc_discount( $vals['value'], $vals['label'], $code, $currency );
                }
            }
        }
    } else {
        return;
    }
}


/**
 * For participant and member delete, decrement the code usage value since
 * they are no longer using the code.
 *
 * FIXME: When a contact is deleted, we should also delete their tracking info/usage.
 * FIXME: When removing participant (and additional) from events, also delete their tracking info/usage.
 */
function cividiscount_civicrm_pre( $op, $name, $id, &$obj ) {

    if ( $op == 'delete' ) {

        $contactid = 0;

        if ( $name == 'Participant' ) {
            $result = _get_participant( $id );
            $contactid = $result['contact_id'];
        } else if ( $name == 'Membership' ) {
            $result = _get_membership( $id );
            $contactid = $result[$id]['contact_id'];
        } else {
            return;
        }
    
        $result = _get_code_tracking_details( $contactid );
        foreach ( $result as $item ) {
            if ( $item['track_type'] == 'Event' ||
                 $item['track_type'] == 'Membership' ) {
                if ( $item['track_id'] == $id ) {
                    _decrement_counter( $item['cid'] );
                    _delete_code_tracking_detail( $item['rid'] );
                }
            }
        }
    }
}


/**
 * Implementation of hook_civicrm_postProcess()
 * 
 * If the event id of the form being loaded has a discount code, increment the
 * count and log the usage. If it's a membership, just log the usage.
 *
 * This function is a landmine... it should be called hook_landmine since we're
 * getting important chunks of information from the form values.
 *
 * If tracking ever stops working, look here.
 */
function cividiscount_civicrm_postProcess( $class, &$form ) {

    $params = $form->getVar( '_params' );
    $contactid = 0;

    // Events
    if ( in_array( $class, array(
            'CRM_Event_Form_Registration_Confirm',
            'CRM_Event_Form_Participant' ) ) ) {

        $eid = $form->getVar( '_eventId' );
        if ( $class == 'CRM_Event_Form_Registration_Confirm' ) {
            $contactid = $params['contactID'];
        } else {
            $contactid = $form->getVar( '_contactId' );
        }

        $pids = $form->getVar( '_participantIDS' );
        if ( !empty( $params['contributionID'] ) ) {
            $contributionid = $params['contributionID'];
        }
        $pid = $pids[0];
        $track = array(
            'id' => $pid,
            'type' => 'Event',
            'description' => $params['description']
        );

    // Membership
    } else if ( in_array( $class, array('CRM_Contribute_Form_Contribution_Confirm') ) ) {

        // Skip processing if it's a standard contribution form (no membership/event info)?
        if ( empty( $params['membershipID'] ) ) {
            return;
        }
        $mid = $params['membershipID'];

        // Need to lookup the contact id if it's a 100% discount.
        if ( !empty( $params['contactID'] ) ) {
            $contactid = $params['contactID'];
        } else {
            $contactid = _get_civicrm_contactid_by_memberid( $params['membershipID'] );
        }

        if ( !empty( $params['contributionID'] ) ) {
            $contributionid = $params['contributionID'];
        }

        $track = array(
            'id' => $mid,
            'type' => 'Membership',
            'description' => $params['description']
        );

    } else if (in_array($class, array('CRM_Member_Form_Membership'))) {

        // FIXME: not able to add membership id or description because those values are
        // not in the form params when submitting offline membership.
        //
        // In addition, a contribution id doesn't seem to exist yet.
        $contactid = $form->getVar( '_contactID' );
        $track = array(
            'id' => NULL,
            'type' => 'Membership',
            'description' => 'Offline membership'
        );

    } else {
        return;
    }

    $code = CRM_Utils_Request::retrieve( 'discountcode', 'String', $form, false, null, $_REQUEST );
    $code = _get_code_details( $code );

    if ( empty( $code ) ) {
        return;
    }

    // FIXME: When registering multiple participants, the contactids aren't
    // available to us at this point, so we have to make a db call to find them.
    // Will submit patch to core.
    //
    // CRM_Event_Form_Registration_Confirm = online event registration
    // CRM_Event_Form_Participant = offline event registration

    if ( in_array( $class, array(
          'CRM_Event_Form_Registration_Confirm',
          'CRM_Event_Form_Participant' ) ) ) {

        if ( $class == 'CRM_Event_Form_Registration_Confirm' ) {
            foreach ( $pids as $pid ) {
                $result = _get_participant( $pid );
                $contactid = $result['contact_id'];
                _increment_counter( $code );
                _set_tracking( $code['cid'], $contactid, $contributionid, $eid, 'Event', serialize( $track ) );
            }
        } else {
            _increment_counter( $code );
            // FIXME: contribution id is not available in $params, so null is being passed here.
            _set_tracking( $code['cid'], $contactid, $contributionid, $eid, 'Event', serialize( $track ) );
        }
    } else if ( in_array( $class, array(
            'CRM_Contribute_Form_Contribution_Confirm',
            'CRM_Member_Form_Membership' ) ) ) {

        _increment_counter( $code );
        _set_tracking( $code['cid'], $contactid, $contributionid, $mid, 'Membership', serialize( $track ) );

    } else {
        _set_tracking( $code['cid'], $contactid, $contributionid, NULL, 'Contribution', serialize( $track ) );
    }
}


/**
 * Implementation of hook_civicrm_tabs()
 * 
 * Display a discounts tab listing discount code usage for that contact.
 */
function cividiscount_civicrm_tabs(&$tabs, $cid) {
    if ( _is_org( $cid ) ) {
        $count = _get_code_tracking_count_org( $cid );
        $a = array( 'id' => 'discounts',
                    'count' => $count,
                    'title' => 'Codes Assigned',
                    'weight' => '998');
        if ( $count > 0 ) {
            $a['url'] = "/admin/settings/civievent_discount/report/$cid";
        }
        $tabs[] = $a;
    }

    $count = _get_code_tracking_count( $cid );
    $a = array( 'id' => 'discounts',
                'count' => $count,
                'title' => 'Codes Redeemed',
                'weight' => '999');
    if ( $count > 0 ) {
        $a['url'] = "/admin/settings/civievent_discount/usage/user/$cid";
    }
    $tabs[] = $a;
}


/**
 * Implementation of hook_civicrm_validate()
 * 
 * Used in the initial event registration screen.
 */
function cividiscount_civicrm_validate($name, &$fields, &$files, &$form) {
    if ( !in_array( $name, array( 'CRM_Event_Form_Participant',
                                  'CRM_Member_Form_Membership',
                                  'CRM_Event_Form_Registration_Register' ) ) ) {
        return;
    }

    $code = CRM_Utils_Request::retrieve( 'discountcode', 'String', $form, false, null, $_REQUEST );

    if ( $code == '' ) {
        return;
    }

    $code = _get_code_details( $code );

    if ( !$code ) {
        $codes = _get_discounts( );
        $code = _verify_autodiscount( $codes );
    }

    if ( empty( $code ) ) {
        $errors['discountcode'] = ts( 'Discount code is invalid.' );

        return $errors;
    } else {
        if ( _is_expired( $code ) ) {
            $errors['discountcode'] = ts( 'The discount code has expired.' );
        }

        $sv = $form->getVar( '_submitValues' );
        $apcount = 1;
        if ( array_key_exists( 'additional_participants', $sv ) ) {
            $apcount = $sv['additional_participants'];
        }

        if ( $code['count_max'] > 0 ) {
            if ( empty( $apcount ) ) {
                $apcount = 1;
            } else {
                $apcount++;  // add 1 for person registering
            }

            if ( ( $code['count_use'] + $apcount ) > $code['count_max'] ) {
                $errors['discountcode'] = ts( 'There are not enough uses remaining for this code.' );
            }
        }
    }

    return empty( $errors ) ? true : $errors;
}


/**
 * Helper function for updating the tracking.
 */
function _set_tracking( $cid, $id, $contrib_id, $obj_id, $obj_type, $track ) {

/*
    require_once('civievent_discount.admin.inc');

    return db_query(_sql_set_tracking(), $cid, $id, $contrib_id, $obj_id, $obj_type, time(), $track);
*/

}


/**
 * Add to the usage counter.
 */
function _increment_counter( $code ) {

/*
    require_once('civievent_discount.admin.inc');

    return db_query(_sql_increment_counter(), $code);
*/

}


/**
 * Subtract from the usage counter.
 */
function _decrement_counter( $code ) {

    
/*
    require_once('civievent_discount.admin.inc');

    return db_query(_sql_decrement_counter(), $code);
*/

}


/**
 * Returns the number of discounts a contact id has redeemed.
 */
function _get_code_tracking_count( $id = 0 ) {
    return 0;

/*
    require_once('civievent_discount.admin.inc');
    $result = db_result(db_query(_sql_get_tracking_count(), $id));

    return $result['count'];
*/

}


/**
 * Returns the number of discounts associated with an organization.
 */
function _get_code_tracking_count_org( $id = 0 ) {
    return 0;

/*
    require_once('civievent_discount.admin.inc');
    $result = db_result(db_query(_sql_get_tracking_by_org(), $id));

    return $result['count'];
*/

}


/**
 * Returns an array of all discount codes.
 */
function _get_discounts( ) {
    $codes = array( );

    $sql = "SELECT id, code, description, amount, amount_type, events, pricesets, memberships, autodiscount, expire_on, count_use, count_max FROM cividiscount_item";
    $dao =& CRM_Core_DAO::executeQuery( $sql, array( ) );
    while ( $dao->fetch( ) ) {
        $codes[$dao->code] = (array) $dao;
    }

    return $codes;
}


/**
 * Returns an array ids.
 */
function _get_items_from_codes( $codes, $key ) {

    if ( !in_array( $key, array( 'events', 'pricesets', 'memberships', 'autodiscount' ) ) ) {
        CRM_Core_Error::fatal( 'Attempt to retrieve unknown key from discount code.' );
    }

    $items = array( );

    foreach ( $codes as $cid => $data ) {

        $a = unserialize( $data[$key] );
        if ( !is_array( $a ) ) {
            $a = array( );
        }

        foreach ($a as $itemid) {
            $items[$itemid] = $itemid;
        }
    }

    return $items;
}


/**
 * Returns an array of event ids.
 */
function _get_discounted_event_ids( ) {
    return _get_items_from_codes( _get_discounts( ), 'events' );
}


/**
 * Returns an array of priceset ids.
 */
function _get_discounted_priceset_ids( ) {
    return _get_items_from_codes( _get_discounts( ), 'pricesets' );
}


/**
 * Returns an array of membership ids.
 */
function _get_discounted_membership_ids( ) {
    return _get_items_from_codes( _get_discounts( ), 'memberships' );
}


/**
 * Returns an array of autodiscounted membership ids.
 */
function _get_autodiscounted_ids( ) {
    return _get_items_from_codes( _get_discounts( ), 'autodiscount' );
}


/**
 * Calculate either a monetary or percentage discount.
 */
function _calc_discount( $amount, $label, $code, $currency = 'USD' ) {

    require_once( 'CRM/Utils/Money.php' );
    $newamount = 0.00;
    $newlabel = '';

    if ( $code['amount_type'] == 'M' ) {

        require_once( 'CRM/Utils/Rule.php' );

        $newamount = CRM_Utils_Rule::cleanMoney( $amount ) - CRM_Utils_Rule::cleanMoney( $code['amount'] );
        $fmt_discount = CRM_Utils_Money::format( $code['amount'], $currency );
        $newlabel = $label . " (Discount: {$fmt_discount} {$code['description']})";

    } else {

        $newamount = $amount - ( $amount * ( $code['amount'] / 100 ) );
        $newlabel = $label ." (Discount: {$code['amount']}% {$code['description']})";
    }

    if ( $newamount < 0) { $newamount = 0.00; }

    return array( $newamount, $newlabel );
}


/**
 * Determine if the member should receive the auto discount.
 */
function _verify_autodiscount( $codes = array( ) ) {

    $session =& CRM_Core_Session::singleton( );
    $uid = $session->get( 'userID' );
    if ( !$uid ) {
        return;
    }

    require_once('CRM/Member/BAO/Membership.php');

    foreach ( $codes as $k => $v ) {
        $cads = unserialize( $v['autodiscount'] );
        if ( !is_array( $cads ) ) {
            $cads = array( );
        }

        foreach ( $cads as $cad ) {
            $membership = CRM_Member_BAO_Membership::getContactMembership( $uid, $cad, NULL );
            if ( $membership['is_current_member'] ) {
                $code = $v['code'];

                return $code;
            } 
        }
    }

    return;
}


/**
 * Returns TRUE if the code is not case sensitive.
 */
function _ignore_case( ) {
  return FALSE;

/*
    require_once('civievent_discount.admin.inc');

    return (_get_ignore_case() == 0) ? FALSE : TRUE;
*/

}


/**
 * Returns TRUE if the code should allow multiple participants.
 */
function _allow_multiple( ) {
    return FALSE;

/*
    require_once('civievent_discount.admin.inc');

    return (_get_allow_multiple() == 0) ? FALSE : TRUE;
*/

}


/**
 * Returns TRUE if contact type is an organization
 */
function _is_org( $cid ) {
    $sql = "SELECT contact_type FROM civicrm_contact WHERE id = $cid";
    $dao =& CRM_Core_DAO::executeQuery( $sql, array( ) );
    while ( $dao->fetch( ) ) {
        if ( $dao->contact_type == "Organization" ) {
            return TRUE;
        }
    }

    return FALSE;
}


/**
 * Returns all the details about a code such as pricesets, memberships, etc.
 */
function _get_code_details($code) {
    $ret = array( );
    if ( empty( $code ) ) {
        return $ret;
    }

    $code = trim( $code );
    $codes = _get_discounts( );

    if ( _ignore_case( ) ) {
        $code = strtoupper( $code );
        foreach ( $codes as $k => $v ) {
            if ( $code == strtoupper( $k ) ) {
                $ret = $v;
                break;
            }
        }
    } else {
        $ret = $codes[$code];
    } 
  
    return $ret;
} 


/**
 * Returns a contact id for a member id
 */
function _get_civicrm_contactid_by_memberid($mid) {

    $sql = "SELECT contact_id FROM civicrm_membership WHERE id = $mid";
    $dao =& CRM_Core_DAO::executeQuery( $sql, array( ) );
    $cid = 0;
    while ( $dao->fetch( ) ) {
        $cid = $dao->contact_id;
    }

    return $cid;
}


/**
 * Add the discount textfield to a form
 */
function _add_discount_textfield( &$form ) {
    $form->addElement( 'text', 'discountcode', ts( 'If you have a discount code, enter it here' ) );
    $template =& CRM_Core_Smarty::singleton( );
    $bhfe = $template->get_template_vars( 'beginHookFormElements' );
    if ( !$bhfe ) {
        $bhfe = array( );
    }
    $bhfe[] = 'discountcode';
    $form->assign( 'beginHookFormElements', $bhfe );
}


/**
 * Check if the code is expired.
 */
function _is_expired( $code ) {
    $time = CRM_Utils_Date::getToday( null, 'Y-m-d H:i:s' );

    if ( strtotime( $time ) > abs( strtotime( $code['expire_on'] ) ) ) {
        return TRUE;
    } else {
        return FALSE;
    }
}
