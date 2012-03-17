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

require_once 'CRM/Core/Page.php';
require_once 'CDM/DAO/Item.php';

/**
 * Page for displaying discount code details
 */
class CDM_Page_Discount_Usage extends CRM_Core_Page
{
    /**
     * The id of the discount code
     *
     * @var int
     */
    protected $_id;

    function preProcess( ) {

        $cid = CRM_Utils_Request::retrieve('cid', 'Positive', $this, false);
        $oid = CRM_Utils_Request::retrieve('oid', 'Positive', $this, false);

        if ( $oid ) {
            $this->_id = CRM_Utils_Request::retrieve('oid', 'Positive', $this, false);
        } else {
            $this->assign( 'hide_contact', TRUE);
            $this->_id = $cid;
        }

        require_once 'CRM/Utils/Rule.php';
        if ( ! CRM_Utils_Rule::positiveInteger( $this->_id ) ) {
            CRM_Core_Error::fatal( ts( 'We need a valid discount ID for view' ) );
        }

        $this->assign( 'id', $this->_id );
        $defaults = array( );
        $params = array( 'id' => $this->_id );

        require_once 'CDM/BAO/Item.php';
        CDM_BAO_Item::retrieve( $params, $defaults );

        require_once 'CDM/BAO/Track.php';
        if ( $cid ) {
            $rows = CDM_BAO_Track::getUsageByContact( $this->_id );
        } else {
            $rows = CDM_BAO_Track::getUsageByOrg( $this->_id );
        }

        $this->assign( 'rows', $rows );
        $this->assign( 'code_details', $defaults );

        if ( !empty( $defaults['code'] ) ) {
            CRM_Utils_System::setTitle( $defaults['code'] );
        }
    }

    function run( ) {
        $this->preProcess();
        return parent::run();
    }
}

