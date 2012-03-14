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

