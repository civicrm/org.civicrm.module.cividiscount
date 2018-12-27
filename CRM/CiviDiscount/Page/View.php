<?php

use CRM_CiviDiscount_ExtensionUtil as E;

/**
 * Page for displaying discount code details
 * @package CiviDiscount
 */
class CRM_CiviDiscount_Page_View extends CRM_Core_Page {
  /**
   * The id of the discount code
   *
   * @var int
   */
  protected $_id;

  protected $_multiValued = NULL;

  /**
   * The action links that we need to display for the browse screen
   *
   * @var array
   * @static
   */
  static $_links = NULL;

  /**
   * Get BAO Name
   *
   * @return string Classname of BAO.
   */
  public function getBAOName() {
    return 'CRM_CiviDiscount_BAO_Item';
  }

  /**
   * Get action Links
   *
   * @return array (reference) of action links
   */
  public function &links() {
    if (!(self::$_links)) {
      self::$_links = [
        CRM_Core_Action::UPDATE => [
          'name' => E::ts('Edit'),
          'url' => 'civicrm/cividiscount/discount/edit',
          'qs' => '&id=%%id%%&reset=1',
          'title' => E::ts('Edit Discount')
        ],
        CRM_Core_Action::DISABLE => [
          'name' => E::ts('Disable'),
          'class' => 'crm-enable-disable',
          'title' => E::ts('Disable Discount')
        ],
        CRM_Core_Action::ENABLE => [
          'name' => E::ts('Enable'),
          'class' => 'crm-enable-disable',
          'title' => E::ts('Enable Discount')
        ],
        CRM_Core_Action::DELETE => [
          'name' => E::ts('Delete'),
          'url' => 'civicrm/cividiscount/discount/delete',
          'qs' => '&id=%%id%%&reset=1',
          'title' => E::ts('Delete Discount')
        ]
      ];
    }
    return self::$_links;
  }

  /**
   * Get name of edit form
   *
   * @return string Classname of edit form.
   */
  public function editForm() {
    return 'CRM_CiviDiscount_Form_Item';
  }

  /**
   * Get edit form name
   *
   * @return string name of this page.
   */
  public function editName() {
    return 'Discount Code';
  }

  /**
   * Get user context.
   *
   * @return string user context.
   */
  public function userContext($mode = NULL) {
    return 'civicrm/cividiscount/discount';
  }

  public function preProcess() {
    $this->_id = CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE);

    if (!CRM_Utils_Rule::positiveInteger($this->_id)) {
      CRM_Core_Error::fatal(ts('We need a valid discount ID for view'));
    }

    $this->assign('id', $this->_id);
    $defaults = [];
    $params = ['id' => $this->_id];

    CRM_CiviDiscount_BAO_Item::retrieve($params, $defaults);

    $this->assign('code_id', $defaults['id']);
    $this->assign('code', $defaults['code']);
    $this->assign('description', $defaults['description']);
    $this->assign('amount', $defaults['amount']);
    $this->assign('amount_type', $defaults['amount_type']);
    $this->assign('count_use', $defaults['count_use']);
    $this->assign('count_max', $defaults['count_max']);
    $this->assign('is_active', $defaults['is_active']);

    if (array_key_exists('expire_on', $defaults)) {
      $this->assign('expire_on', $defaults['expire_on']);
    }

    if (array_key_exists('active_on', $defaults)) {
      $this->assign('active_on', $defaults['active_on']);
    }

    if (array_key_exists('organization_id', $defaults)) {
      $this->assign('organization_id', $defaults['organization_id']);
      $orgname = CRM_Contact_BAO_Contact::displayName($defaults['organization_id']);
      $this->assign('organization', $orgname);
    }

    $this->_multiValued = [
      'autodiscount' => NULL,
      'memberships' => NULL,
      'events' => NULL,
      'pricesets' => NULL
    ];

    foreach ($this->_multiValued as $mv => $info) {
      if (!empty($defaults[$mv])) {
        $v = substr($defaults[$mv], 1, -1);
        $values = explode(CRM_Core_DAO::VALUE_SEPARATOR, $v);

        $defaults[$mv] = [];
        if (!empty($values)) {
          foreach ($values as $val) {
            $defaults[$mv][] = $val;
          }
        }
      }
    }

    if (array_key_exists('events', $defaults)) {
      $events = CRM_CiviDiscount_Utils::getEvents();
      $defaults['events'] = CRM_CiviDiscount_Utils::getIdsTitles($defaults['events'], $events);
      $this->assign('events', $defaults['events']);
    }

    $membershipTypes = CRM_Member_BAO_MembershipType::getMembershipTypes(FALSE);
    if (array_key_exists('memberships', $defaults)) {
      $defaults['memberships'] = CRM_CiviDiscount_Utils::getIdsTitles($defaults['memberships'], $membershipTypes);
      $this->assign('memberships', $defaults['memberships']);
    }

    if (array_key_exists('autodiscount', $defaults)) {
      $defaults['autodiscount'] = CRM_CiviDiscount_Utils::getIdsTitles($defaults['autodiscount'], $membershipTypes);
      $this->assign('autodiscount', $defaults['autodiscount']);
    }

    if (array_key_exists('pricesets', $defaults)) {
      $priceSets = CRM_CiviDiscount_Utils::getPriceSets();
      $defaults['pricesets'] = CRM_CiviDiscount_Utils::getIdsTitles($defaults['pricesets'], $priceSets);
      $this->assign('pricesets', $defaults['pricesets']);
    }

    CRM_Utils_System::setTitle($defaults['code']);
  }

  public function run() {
    $this->preProcess();
    return parent::run();
  }

}
