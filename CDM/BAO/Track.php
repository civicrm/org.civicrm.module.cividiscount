<?php

/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.1                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2011                                |
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
 * @copyright CiviCRM LLC (c) 2004-2011
 * $Id$
 *
 */

require_once 'CDM/DAO/Track.php';


class CDM_BAO_Track extends CDM_DAO_Track {

    /**
     * class constructor
     */
    function __construct( ) {
        parent::__construct( );
    }

    /**
     * Takes a bunch of params that are needed to match certain criteria and
     * retrieves the relevant objects. Typically the valid params are only
     * contact_id. We'll tweak this function to be more full featured over a period
     * of time. This is the inverse function of create. It also stores all the retrieved
     * values in the default array
     *
     * @param array $params   (reference ) an assoc array of name/value pairs
     * @param array $defaults (reference ) an assoc array to hold the flattened values
     *
     * @return object CDM_BAO_Item object on success, null otherwise
     * @access public
     * @static
     */
    static function retrieve( &$params, &$defaults ) {
        $item = new CDM_DAO_Track( );
        $item->copyValues( $params );
        if ( $item->find( true ) ) {
            CRM_Core_DAO::storeValues( $item, $defaults );
            return $item;
        }
        return null;
    }

    static function getUsageByContact( $id ) {
        return CDM_BAO_Track::getUsage( NULL, $id, NULL );
    }

    static function getUsageByOrg( $id ) {
        return CDM_BAO_Track::getUsage( NULL, NULL, $id );
    }

    static function getUsageByCode( $id ) {
        return CDM_BAO_Track::getUsage( $id, NULL, NULL );
    }

    static function getUsage( $id = NULL, $cid = NULL, $orgid = NULL ) {

        require_once 'CDM/Utils.php';
        require_once 'CRM/Member/BAO/Membership.php';
        require_once 'CRM/Contact/BAO/Contact.php';

        $events = CDM_Utils::getEvents( );

        $where = '';

        $sql = "
SELECT    t.item_id as item_id,
          t.contact_id as contact_id,
          t.used_date as used_date,
          t.contribution_id as contribution_id,
          t.event_id as event_id,
          t.entity_table as entity_table,
          t.entity_id as entity_id ";

        $from = " FROM cividiscount_track AS t ";

        if ( $orgid ) {
            $sql .= ", i.code ";
            $where = " LEFT JOIN cividiscount_item AS i ON (i.id = t.item_id) ";
            $where .= " WHERE i.organization_id = " . CRM_Utils_Type::escape( $orgid, 'Integer' );
        } else if ( $cid ) {
            $where = " WHERE t.contact_id = " . CRM_Utils_Type::escape( $cid, 'Integer' );
        } else {
            $where = " WHERE t.item_id = " . CRM_Utils_Type::escape( $id, 'Integer' );
        }

        $orderby = " ORDER BY t.item_id, t.used_date ";

        $sql = $sql . $from . $where . $orderby;

        $dao = new CRM_Core_DAO( );
        $dao->query( $sql );
        $rows = array();
        while ( $dao->fetch( ) ) {
            $row = array();
            $row['contact_id'] = $dao->contact_id;
            $row['display_name'] = CRM_Contact_BAO_Contact::displayName( $dao->contact_id );
            $row['used_date'] = $dao->used_date;
            $row['contribution_id'] = $dao->contribution_id;
            $row['event_id'] = $dao->event_id;
            $row['entity_table'] = $dao->entity_table;
            $row['entity_id'] = $dao->entity_id;
            if ( isset( $dao->code ) ) {
                $row['code'] = $dao->code;
            }
            if ( $row['entity_table'] == 'civicrm_participant' ) {
                if ( array_key_exists( $dao->event_id, $events ) ) {
                    $row['event_title'] = $events[$dao->event_id];
                }
            } else if ( $row['entity_table'] == 'civicrm_membership' ) {
                $result = CRM_Member_BAO_Membership::getStatusANDTypeValues( $dao->entity_id );
                if ( array_key_exists( $dao->entity_id, $result ) ) {
                    if ( array_key_exists( 'membership_type', $result[$dao->entity_id] ) ) {
                      $row['membership_title'] = $result[$dao->entity_id]['membership_type'];
                    }
                }
            }
            $rows[] = $row;
        }

        return $rows;
    }
    
    
    /**
     * Function to delete discount codes track
     * 
     * @param  int  $trackID     ID of the discount code track to be deleted.
     * 
     * @access public
     * @static
     * @return true on success else false
     */
    static function del($trackID) 
    {
        require_once 'CRM/Utils/Rule.php';
        if ( ! CRM_Utils_Rule::positiveInteger( $trackID ) ) {
            return false;
        }

        require_once 'CDM/DAO/Track.php';
        $item = new CDM_DAO_Track( );
        $item->id = $trackID;
        $item->delete( );

        return true;
    }
}

