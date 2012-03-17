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
class CDM_Page_Discount_View extends CRM_Core_Page
{
    /**
     * The id of the discount code
     *
     * @var int
     */
    protected $_id;

    protected $_multiValued = null;

    /**
     * The action links that we need to display for the browse screen
     *
     * @var array
     * @static
     */
    static $_links = null;

    /**
     * Get BAO Name
     *
     * @return string Classname of BAO.
     */
    function getBAOName() 
    {
        return 'CDM_BAO_Item';
    }

    /**
     * Get action Links
     *
     * @return array (reference) of action links
     */
    function &links()
    {
        if (!(self::$_links)) {
            self::$_links = array(
                                  CRM_Core_Action::UPDATE  => array(
                                                                    'name'  => ts('Edit'),
                                                                    'url'   => 'civicrm/cividiscount/discount/edit',
                                                                    'qs'    => '&id=%%id%%&reset=1',
                                                                    'title' => ts('Edit Discount') 
                                                                   ),
                                  CRM_Core_Action::DISABLE => array(
                                                                    'name'  => ts('Disable'),
                                                                    'extra' => 'onclick = "enableDisable( %%id%%,\''. 'CDM_BAO_Item' . '\',\'' . 'enable-disable' . '\' );"',
                                                                    'ref'   => 'disable-action',
                                                                    'title' => ts('Disable Discount')
                                                                   ),

                                  CRM_Core_Action::ENABLE => array(
                                                                    'name'  => ts('Enable'),
                                                                    'extra' => 'onclick = "enableDisable( %%id%%,\''. 'CDM_BAO_Item' . '\',\'' . 'enable-disable' . '\' );"',
                                                                    'ref'   => 'enable-action',
                                                                    'title' => ts('Enable Discount')
                                                                   ),

                                  CRM_Core_Action::DELETE  => array(
                                                                    'name'  => ts('Delete'),
                                                                    'url'   => 'civicrm/cividiscount/discount/delete',
                                                                    'qs'    => '&id=%%id%%&reset=1',
                                                                    'title' => ts('Delete Discount') 
                                                                   )
                                  );
        }
        return self::$_links;
    }

    /**
     * Get name of edit form
     *
     * @return string Classname of edit form.
     */
    function editForm() 
    {
        return 'CDM_Form_Item';
    }
    
    /**
     * Get edit form name
     *
     * @return string name of this page.
     */
    function editName() 
    {
        return 'Discount Code';
    }
    
    /**
     * Get user context.
     *
     * @return string user context.
     */
    function userContext($mode = null) 
    {
        return 'civicrm/cividiscount/discount';
    }

    function preProcess( ) {
        $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, false);

        require_once 'CRM/Utils/Rule.php';
        if ( ! CRM_Utils_Rule::positiveInteger( $this->_id ) ) {
            CRM_Core_Error::fatal( ts( 'We need a valid discount ID for view' ) );
        }

        $this->assign( 'id', $this->_id );
        $defaults = array( );
        $params = array( 'id' => $this->_id );

        require_once 'CDM/BAO/Item.php';
        CDM_BAO_Item::retrieve( $params, $defaults );

        $this->assign( 'code_id', $defaults['id'] );
        $this->assign( 'code', $defaults['code'] );
        $this->assign( 'description', $defaults['description'] );
        $this->assign( 'amount', $defaults['amount'] );
        $this->assign( 'amount_type', $defaults['amount_type'] );
        $this->assign( 'count_use', $defaults['count_use'] );
        $this->assign( 'count_max', $defaults['count_max'] );
        $this->assign( 'is_active', $defaults['is_active'] );

        if ( array_key_exists( 'expire_on', $defaults ) ) {
            $this->assign( 'expire_on', $defaults['expire_on'] );
        }

        if ( array_key_exists( 'active_on', $defaults ) ) {
            $this->assign( 'active_on', $defaults['active_on'] );
        }

        if ( array_key_exists( 'organization_id', $defaults ) ) {
            $this->assign( 'organization_id', $defaults['organization_id'] );
            require_once 'CRM/Contact/BAO/Contact.php';
            $orgname = CRM_Contact_BAO_Contact::displayName($defaults['organization_id']);
            $this->assign( 'organization', $orgname );
        }

        $this->_multiValued = array( 'autodiscount' => null,
                                     'memberships'  => null,
                                     'events'       => null,
                                     'pricesets'    => null );

        foreach ( $this->_multiValued as $mv => $info ) { 
            if ( ! empty( $defaults[$mv] ) ) { 
                $v = substr( $defaults[$mv], 1, -1 );
                $values = explode( CRM_Core_DAO::VALUE_SEPARATOR, $v );

                $defaults[$mv] = array( );
                if ( ! empty( $values ) ) { 
                    foreach ( $values as $val ) { 
                        $defaults[$mv][] = $val;
                    }   
                }   
            }   
        }   

        require_once 'CDM/Utils.php';
        require_once 'CRM/Member/BAO/MembershipType.php';

        if ( array_key_exists( 'events', $defaults ) ) {
            $events = CDM_Utils::getEvents( );
            $defaults['events'] = CDM_Utils::getIdsTitles( $defaults['events'], $events );
            $this->assign( 'events', $defaults['events'] );
        }

        $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes();
        if ( array_key_exists( 'memberships', $defaults )  ) {
            $defaults['memberships'] = CDM_Utils::getIdsTitles( $defaults['memberships'], $membershipTypes );
            $this->assign( 'memberships', $defaults['memberships'] );
        }

        if ( array_key_exists( 'autodiscount', $defaults ) ) {
            $defaults['autodiscount'] = CDM_Utils::getIdsTitles( $defaults['autodiscount'], $membershipTypes );
            $this->assign( 'autodiscount', $defaults['autodiscount'] );
        }

        if ( array_key_exists( 'pricesets', $defaults ) ) {
            $priceSets = CDM_Utils::getPriceSets( );
            $defaults['pricesets'] = CDM_Utils::getIdsTitles( $defaults['pricesets'], $priceSets );
            $this->assign( 'pricesets', $defaults['pricesets'] );
        }

        CRM_Utils_System::setTitle( $defaults['code'] );
    }

    function run( ) {
        $this->preProcess();
        return parent::run();
    }
}

