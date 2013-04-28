<?php

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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2013
 * $Id$
 *
 */

require_once 'CRM/Core/Page/Basic.php';
require_once 'CDM/DAO/Item.php';

/**
 * Page for displaying list of discount codes
 */
class CDM_Page_Discount_List extends CRM_Core_Page_Basic {
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
  function getBAOName() {
    return 'CDM_BAO_Item';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  function &links() {
    if (!(self::$_links)) {
      self::$_links = array(
                            CRM_Core_Action::VIEW  => array(
                                                              'name'  => ts('View'),
                                                              'url'   => 'civicrm/cividiscount/discount/view',
                                                              'qs'    => 'id=%%id%%&reset=1',
                                                              'title' => ts('View Discount Code')
                                                            ),
                            CRM_Core_Action::UPDATE  => array(
                                                              'name'  => ts('Edit'),
                                                              'url'   => 'civicrm/cividiscount/discount/edit',
                                                              'qs'    => '&id=%%id%%&reset=1',
                                                              'title' => ts('Edit Discount Code')
                                                            ),
                            CRM_Core_Action::DISABLE => array(
                                                              'name'  => ts('Disable'),
                                                              'extra' => 'onclick = "enableDisable(%%id%%, \'' . 'CDM_BAO_Item' . '\', \'' . 'enable-disable' . '\', 0, \'CiviDiscount_Item\');"',
                                                              'ref'   => 'disable-action',
                                                              'title' => ts('Disable Discount Code')
                                                            ),

                            CRM_Core_Action::ENABLE => array(
                                                              'name'  => ts('Enable'),
                                                              'extra' => 'onclick = "enableDisable(%%id%%, \'' . 'CDM_BAO_Item' . '\' ,\'' . 'disable-enable' . '\', 0, \'CiviDiscount_Item\');"',
                                                              'ref'   => 'enable-action',
                                                              'title' => ts('Enable Discount Code')
                                                            ),
                            CRM_Core_Action::DELETE  => array(
                                                              'name'  => ts('Delete'),
                                                              'url'   => 'civicrm/cividiscount/discount/delete',
                                                              'qs'    => '&id=%%id%%',
                                                              'title' => ts('Delete Discount Code')
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
  function editForm() {
    return 'CDM_Form_Item';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  function editName() {
    return 'Discount Code';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  function userContext($mode = null) {
    return 'civicrm/cividiscount/discount';
  }
}

