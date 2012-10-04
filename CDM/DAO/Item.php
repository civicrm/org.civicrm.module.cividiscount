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
require_once 'CRM/Core/DAO.php';
require_once 'CRM/Utils/Type.php';
class CDM_DAO_Item extends CRM_Core_DAO {
  /**
   * static instance to hold the table name
   *
   * @var string
   * @static
   */
  static $_tableName = 'cividiscount_item';
  /**
   * static instance to hold the field values
   *
   * @var array
   * @static
   */
  static $_fields = null;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = null;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = null;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = null;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = false;
  /**
   * Discount Item ID
   *
   * @var int unsigned
   */
  public $id;
  /**
   * Discount Code.
   *
   * @var string
   */
  public $code;
  /**
   * Discount Description.
   *
   * @var string
   */
  public $description;
  /**
   * Amount of discount either actual or percentage?
   *
   * @var string
   */
  public $amount;
  /**
   * Type of discount, actual or percentage?
   *
   * @var string
   */
  public $amount_type;
  /**
   * Max number of times this code can be used.
   *
   * @var int
   */
  public $count_max;
  /**
   * Number of times this code has been used.
   *
   * @var int
   */
  public $count_use;
  /**
   * Serialized list of events for which this code can be used
   *
   * @var text
   */
  public $events;
  /**
   * Serialized list of pricesets for which this code can be used
   *
   * @var text
   */
  public $pricesets;
  /**
   * Serialized list of memberships for which this code can be used
   *
   * @var text
   */
  public $memberships;
  /**
   * Some sort of autodiscounting mechanism?
   *
   * @var text
   */
  public $autodiscount;
  /**
   * FK to Contact ID for the organization that originated this discount
   *
   * @var int unsigned
   */
  public $organization_id;
  /**
   * When is this discount active?
   *
   * @var datetime
   */
  public $active_on;
  /**
   * When does this discount expire?
   *
   * @var datetime
   */
  public $expire_on;
  /**
   * Is this property active?
   *
   * @var boolean
   */
  public $is_active;
  /**
   * class constructor
   *
   * @access public
   * @return cividiscount_item
   */
  function __construct() {
    parent::__construct();
  }
  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
        'organization_id' => 'civicrm_contact:id',
      );
    }
    return self::$_links;
  }
  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = array(
          'id' => array(
            'name' => 'id',
            'type' => CRM_Utils_Type::T_INT,
            'required' => true,
          ),
          'code' => array(
            'name' => 'code',
            'type' => CRM_Utils_Type::T_STRING,
            'title' => ts('Code'),
            'required' => true,
            'maxlength' => 255,
            'size' => CRM_Utils_Type::HUGE,
          ),
          'description' => array(
            'name' => 'description',
            'type' => CRM_Utils_Type::T_STRING,
            'title' => ts('Description'),
            'required' => true,
            'maxlength' => 255,
            'size' => CRM_Utils_Type::HUGE,
          ),
          'amount' => array(
            'name' => 'amount',
            'type' => CRM_Utils_Type::T_STRING,
            'title' => ts('Amount'),
            'required' => true,
            'maxlength' => 255,
            'size' => CRM_Utils_Type::HUGE,
          ),
          'amount_type' => array(
            'name' => 'amount_type',
            'type' => CRM_Utils_Type::T_STRING,
            'title' => ts('Amount Type'),
            'required' => true,
            'maxlength' => 4,
            'size' => CRM_Utils_Type::FOUR,
          ),
          'count_max' => array(
            'name' => 'count_max',
            'type' => CRM_Utils_Type::T_INT,
            'title' => ts('Count Max'),
            'required' => true,
          ),
          'count_use' => array(
            'name' => 'count_use',
            'type' => CRM_Utils_Type::T_INT,
            'title' => ts('Count Use'),
            'required' => true,
            'default' => 0,
          ),
          'events' => array(
            'name' => 'events',
            'type' => CRM_Utils_Type::T_TEXT,
            'title' => ts('Events'),
          ),
          'pricesets' => array(
            'name' => 'pricesets',
            'type' => CRM_Utils_Type::T_TEXT,
            'title' => ts('Pricesets'),
          ),
          'memberships' => array(
            'name' => 'memberships',
            'type' => CRM_Utils_Type::T_TEXT,
            'title' => ts('Memberships'),
          ),
          'autodiscount' => array(
            'name' => 'autodiscount',
            'type' => CRM_Utils_Type::T_TEXT,
            'title' => ts('Autodiscount'),
          ),
          'organization_id' => array(
            'name' => 'organization_id',
            'type' => CRM_Utils_Type::T_INT,
            'FKClassName' => 'CRM_Contact_DAO_Contact',
          ),
          'active_on' => array(
            'name' => 'active_on',
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
            'title' => ts('Activation Date'),
          ),
          'expire_on' => array(
            'name' => 'expire_on',
            'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
            'title' => ts('Expiration Date'),
          ),
          'is_active' => array(
            'name' => 'is_active',
            'type' => CRM_Utils_Type::T_BOOLEAN,
          ),
       );
    }
    return self::$_fields;
  }
  /**
   * returns the names of this table
   *
   * @access public
   * @return string
   */
  static function getTableName() {
    return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
  }
  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  function getLog() {
    return self::$_log;
  }
  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   */
  function &import($prefix = false) {
    if (!(self::$_import)) {
      self::$_import = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['ount_item'] = & $fields[$name];
          }
          else {
            self::$_import[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_import;
  }
  /**
   * returns the list of fields that can be exported
   *
   * @access public
   * return array
   */
  function &export($prefix = false) {
    if (!(self::$_export)) {
      self::$_export = array();
      $fields = self::fields();
      foreach($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['ount_item'] = & $fields[$name];
          }
          else {
            self::$_export[$name] = & $fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
