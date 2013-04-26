<?php
// $Id$

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
 * File for the CiviCRM APIv3 group functions
 *
 * @package Civicrm Discount
 * @subpackage API
 * @copyright CiviCRM LLC (c) 2004-2013
 */
//not sure why this is required but didn't seem to autoload
require_once 'CDM/BAO/Item.php';
/**
 * Create or update a discount code
 *
 * @param array $params  Associative array of property
 *                       name/value pairs to insert in new 'item'
 *
 * @return array api result array
 * {@getfields item_create}
 * @access public
 */
function civicrm_api3_discount_code_create($params) {
  return _civicrm_api3_basic_create('CDM_BAO_Item', $params);
}

/**
 * Adjust Metadata for Create action
 *
 * The metadata is used for setting defaults, documentation & validation
 * @param array $params array or parameters determined by getfields
 */
function _civicrm_api3_discount_code_create_spec(&$params) {
  $params['is_active']['api.default'] = 1;
  $params['multi_valued']['api.default'] = array();
  $params['multi_valued']['title'] = 'List of discount types being passed in';
}

/**
 * Returns array of items  matching a set of one or more item properties
 *
 * @param array $params  Array of one or more valid property_name=>value pairs. If $params is set
 *                       as null, all items will be returned
 *
 * @return array api result array
 * {@getfields item_get}
 * @access public
 */
function civicrm_api3_discount_code_get($params) {
  return _civicrm_api3_basic_get('CDM_BAO_Item', $params);
}

/**
 * delete an existing item
 *
 * This method is used to delete any existing item. id of the group
 * to be deleted is required field in $params array
 *
 * @param array $params array containing id of the item to be deleted
 *
 * @return array API result Array
 * {@getfields item_delete}
 * @access public
 */
function civicrm_api3_discount_code_delete($params) {
  return _civicrm_api3_basic_delete('CDM_BAO_Item', $params);
}

