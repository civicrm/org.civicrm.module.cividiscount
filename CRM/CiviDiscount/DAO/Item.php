<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 4.6                                                |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2015                                |
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

use CRM_CiviDiscount_ExtensionUtil as E;

/**
 * @package CiviDiscount
 */
class CRM_CiviDiscount_DAO_Item extends CRM_Core_DAO {
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
  static $_fields = NULL;
  /**
   * static instance to hold the FK relationships
   *
   * @var string
   * @static
   */
  static $_links = NULL;
  /**
   * static instance to hold the values that can
   * be imported
   *
   * @var array
   * @static
   */
  static $_import = NULL;
  /**
   * static instance to hold the values that can
   * be exported
   *
   * @var array
   * @static
   */
  static $_export = NULL;
  /**
   * static value to see if we should log any modifications to
   * this table in the civicrm_log table
   *
   * @var boolean
   * @static
   */
  static $_log = FALSE;
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
   * Discount Filters.
   *
   * @var string
   */
  public $filters;
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
   * Is discount code applicable for New Members applicant?
   *
   * @var boolean
   */
  public $membership_new;
  /**
   * Is discount code applicable for Existing Members applicant?
   *
   * @var boolean
   */
  public $membership_renew;
  /**
   * Is there a message to users not eligible for a discount?
   *
   * @var boolean
   */
  public $discount_msg_enabled;
  /**
   * Discount message.
   *
   * @var string
   */
  public $discount_msg;

  /**
   * class constructor
   *
   * @access public
   * @return cividiscount_item
   */
  public function __construct() {
    parent::__construct();
  }

  /**
   * return foreign links
   *
   * @access public
   * @return array
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        'organization_id' => 'civicrm_contact:id',
      ];
    }
    return self::$_links;
  }

  /**
   * returns all the column names of this table
   *
   * @access public
   * @return array
   */
  public static function &fields() {
    if (!(self::$_fields)) {
      self::$_fields = [
        'id' => [
          'name' => 'id',
          'type' => CRM_Utils_Type::T_INT,
          'required' => TRUE,
        ],
        'code' => [
          'name' => 'code',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Code'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'description' => [
          'name' => 'description',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Description'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'filters' => [
          'name' => 'filters',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Discount Filters'),
          'required' => FALSE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'amount' => [
          'name' => 'amount',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Amount'),
          'required' => TRUE,
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'amount_type' => [
          'name' => 'amount_type',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Amount Type'),
          'required' => TRUE,
          'maxlength' => 4,
          'size' => CRM_Utils_Type::FOUR,
        ],
        'count_max' => [
          'name' => 'count_max',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Count Max'),
          'required' => TRUE,
        ],
        'count_use' => [
          'name' => 'count_use',
          'type' => CRM_Utils_Type::T_INT,
          'title' => E::ts('Count Use'),
          'required' => TRUE,
          'default' => 0,
        ],
        'events' => [
          'name' => 'events',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Events'),
        ],
        'pricesets' => [
          'name' => 'pricesets',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Pricesets'),
        ],
        'memberships' => [
          'name' => 'memberships',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Memberships'),
        ],
        'autodiscount' => [
          'name' => 'autodiscount',
          'type' => CRM_Utils_Type::T_TEXT,
          'title' => E::ts('Autodiscount'),
        ],
        'organization_id' => [
          'name' => 'organization_id',
          'type' => CRM_Utils_Type::T_INT,
          'FKClassName' => 'CRM_Contact_DAO_Contact',
        ],
        'active_on' => [
          'name' => 'active_on',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Activation Date'),
        ],
        'expire_on' => [
          'name' => 'expire_on',
          'type' => CRM_Utils_Type::T_DATE + CRM_Utils_Type::T_TIME,
          'title' => E::ts('Expiration Date'),
        ],
        'is_active' => [
          'name' => 'is_active',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ],
        'discount_msg_enabled' => [
          'name' => 'discount_msg_enabled',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ],
        'discount_msg' => [
          'name' => 'discount_msg',
          'type' => CRM_Utils_Type::T_STRING,
          'title' => E::ts('Discount Message'),
          'maxlength' => 255,
          'size' => CRM_Utils_Type::HUGE,
        ],
        'membership_new' => [
          'name' => 'membership_new',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ],
        'membership_renew' => [
          'name' => 'membership_renew',
          'type' => CRM_Utils_Type::T_BOOLEAN,
        ],
      ];
    }
    return self::$_fields;
  }

  /**
   * returns the names of this table
   *
   * @access public
   * @return string
   */
  public static function getTableName() {
    return CRM_Core_DAO::getLocaleTableName(self::$_tableName);
  }

  /**
   * returns if this table needs to be logged
   *
   * @access public
   * @return boolean
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * returns the list of fields that can be imported
   *
   * @access public
   * return array
   */
  public function &import($prefix = FALSE) {
    if (!(self::$_import)) {
      self::$_import = [];
      $fields = self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('import', $field)) {
          if ($prefix) {
            self::$_import['cividiscount_item'] = &$fields[$name];
          }
          else {
            self::$_import[$name] = &$fields[$name];
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
  public function &export($prefix = FALSE) {
    if (!(self::$_export)) {
      self::$_export = [];
      $fields = self::fields();
      foreach ($fields as $name => $field) {
        if (CRM_Utils_Array::value('export', $field)) {
          if ($prefix) {
            self::$_export['cividiscount_item'] = &$fields[$name];
          }
          else {
            self::$_export[$name] = &$fields[$name];
          }
        }
      }
    }
    return self::$_export;
  }
}
