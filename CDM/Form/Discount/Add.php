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

require_once 'CRM/Admin/Form.php';

/**
 * This class generates form components for Location Type
 * 
 */
class CDM_Form_Discount_Add extends CRM_Admin_Form
{
    /**
     * Function to build the form
     *
     * @return None
     * @access public
     */
    public function buildQuickForm( ) 
    {
        parent::buildQuickForm( );
       
        if ($this->_action & CRM_Core_Action::DELETE ) { 
            return;
        }
        
        $this->applyFilter('__ALL__', 'trim');
        $this->add('text',
                   'code',
                   ts('Code'),
                   CRM_Core_DAO::getAttribute( 'CDM_DAO_Item', 'code' ),
                   true );
        $this->addRule( 'code',
                        ts('Code already exists in Database.'),
                        'objectExists',
                        array( 'CDM_DAO_Item', $this->_id ) );
        $this->addRule( 'code',
                        ts( 'Code can only consist of alpha-numeric characters' ),
                        'variable' );
         
        $this->add('text', 'description', ts('Description'), CRM_Core_DAO::getAttribute( 'CDM_DAO_Item', 'description' ) );

        $this->addMoney( 'amount', ts('Discount'), true, CRM_Core_DAO::getAttribute( 'CDM_DAO_Item', 'amount' ), false );

        $this->add( 'select', 'amount_type', ts( 'Amount Type' ),
                    array( 1 => ts( 'Percentage' ),
                           2 => ts( 'Monetary'   ) ),
                    true );

        $this->add('text', 'count_max', ts( 'Usage' ), CRM_Core_DAO::getAttribute( 'CDM_DAO_Item', 'count_max' ), true );
        $this->addRule( 'count_max', ts('Must be an integer') , 'integer' );

        $this->addDate( 'expiration_date', ts( 'Expiration Date' ), false );

        $this->add( 'text', 'organization', ts( 'Organization' ) );
        $this->add( 'hidden', 'organization_id', '', array( 'id' => 'organization_id' ) );
    }

       
    /**
     * Function to process the form
     *
     * @access public
     * @return None
     */
    public function postProcess() {
        CRM_Utils_System::flushCache( 'CDM_DAO_Item' );

        if ( $this->_action & CRM_Core_Action::DELETE ) {
            CRM_Core_BAO_LocationType::del( $this->_id );
            CRM_Core_Session::setStatus( ts('Selected Location type has been deleted.') );
            return;
        }

        // store the submitted values in an array
        $params = $this->exportValues();
        $params['is_active'] =  CRM_Utils_Array::value( 'is_active', $params, false );
        $params['is_default'] =  CRM_Utils_Array::value( 'is_default', $params, false );
            
        // action is taken depending upon the mode
        $locationType               = new CDM_DAO_Item( );
        $locationType->name         = $params['name'];
        $locationType->display_name = $params['display_name'];
        $locationType->vcard_name   = $params['vcard_name'];
        $locationType->description  = $params['description'];
        $locationType->is_active    = $params['is_active'];
        $locationType->is_default   = $params['is_default'];
            
        if ($params['is_default']) {
            $query = "UPDATE civicrm_location_type SET is_default = 0";
            CRM_Core_DAO::executeQuery($query, CRM_Core_DAO::$_nullArray);
        }
            
        if ($this->_action & CRM_Core_Action::UPDATE ) {
            $locationType->id = $this->_id;
        }
            
        $locationType->save( );
        
        CRM_Core_Session::setStatus( ts('The location type \'%1\' has been saved.',
                                        array( 1 => $locationType->name )) );
    } //end of function

}


