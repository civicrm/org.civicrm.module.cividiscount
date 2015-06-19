CiviDiscount Extension
==

ADVANCED FILTERS
--

CiviDiscount now allows you to specify you own criteria for who should get automatic discounts. This is a powerful feature
but does require some technical skill and thorough testing is recommended. Use the api explorer (on your site
at the url civicrm/api/explorer to help you discover the api options you could pass.

You need to specify an API Entity that you want to query and a query string. If you specify contact then the logged in contact id
will be passed in as 'id'. For all other api it will be passed in as 'contact_id'

In this image you can see that contacts with a value of 1 in custom id field 65 (which happens to be 'are you retired' in this case)
will get an automatic discount if they meet other criteria above  - ie a minimum age of 65)

<img src='https://github.com/dlobo/org.civicrm.module.cividiscount/blob/master/docs/images/advancedFilters.jpg'>

NEEDED
--

* Enhance UI with an "apply discount" button that updates displayed prices on any forms
  + basically you want to emulate having the discount code sent in via th URL and let the person know if discount code is
    bogus
  + it would be nice to do this with ajax but isn't worth much effort.

* Create some user documentation
  + Two types of discounts: automatic membership and code-based.
  + Supports discounted memberships, events, pricesets
  + Describe automatic member discounts (use random code never given to public)
  + Show tracking interface


NICE TO HAVE
--

* Give some control over the placement of the discount code text field on the form.

FIXED ALREADY
--

* Fix/check cividiscount_civicrm_buildAmount() for both online and offline forms/cases (event registration):
  + check multi-participant case well ( currently this needs to be enabled in code )
* Fix/check cividiscount_civicrm_buildForm() for both online, offline and renewal forms/cases (memberships):
  + check multi-step form case well
* Check all transaction amounts match amount displayed on forms.
* Fix or remove CDM_BAO_Item::copy(). Currently it's just a copy of CDM_BAO_Item::delete().
* Adapt for 4.2 priceset structure.
* Create menu link for CiviDiscount extension under Administer
* Fix cividiscount_civicrm_pre():
  + When a contact is deleted, we should also delete their tracking info/usage.
  + When removing participant (and additional) from events, also delete their tracking info/usage.
  + When deleting membership record, also delete their tracking info/usage
* Fix/check cividiscount_civicrm_postProcess() tracking for all forms/cases:
  + check we have correct participantID, membershipID, contributionID when it's needed for tracking
  + check generic generic contribution cases well ( discount is not available for this )
* Fix/check discount tracking tabs.
* Generally clean up the code and logic.
* Carefully review discount verification and applicability:
  + fix _get_discounted_event_ids(), _get_discounted_priceset_ids(), _get_discounted_membership_ids() to return events/pricesets/memberships only for active code
* Enhance UI to show which discount code has been applied.
* Fix membership discounts not working from civicrm/contact/view [Submit Credit Card Membership] - CRM-11028.
