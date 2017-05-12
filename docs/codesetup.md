# Setup discount codes

Under **Administer > CiviDiscount** click **New Discount Code.**

![Screenshot of discount code setup](/images/codesetup.png)

**Discount Code:** Create a custom code or click the random code button. Codes can only consist of alphanumeric characters. Do not use spaces. Once created discount codes cannot be changed.

**Description:** Description of code for reference purposes. This description is displayed as part of amounts it is applied too in front end forms and receipts.

**Active:** Check to make code active

**Discount Amount:** Enter the amount of discount. Either a percentage or a set monetary amount.

### Additional Options:
* Usage Limit: The maximum number of times this discount code can be used. Leave blank for no limit.
* Activation Date: This discount will not be usable before this date. Leave blank to have it be enabled right away.
* Expiration Date: This discount will not be usable after this date. Leave blank to have it never expire.
* Organization: Used to associate this discount code with an organization. Every time this code is used it will be recorded on that organization's contact record.
* Price Field Options: If your form uses a price set, the discount will be applied to all options in the set by default. However, if you want the discount to be applied only to specific price options - select those options here.
* Message to non-eligible users: Check box and enter the message you'd like users to see if they are not receiving a discount. The message will appear above the form.
* Events: This discount can be used on the selected event(s). Only active, public and current/future events are listed.
* Event Type: This discount can be used on any event of the select type(s).
* Memberships: Discount the price of purchasing memberships. Only active memberships with start and end dates defined will appear in this list.