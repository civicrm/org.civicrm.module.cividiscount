# Automatic discounts

Anyone with the selected memberships or contact attributes will automatically have the discount code applied provided they are logged in to the website. Autodiscount is generally used with a randomly-generated code which is not given out to users. Cividiscount will apply the discount with no code required.

![Screenshot of automatic discount setup](/images/autodiscount.png)

* Automatic discounts can be applied based on membership status and/or type.
* Automatic discounts can be applied to contacts based on type, age (minimum or maximum) and/or country

### Advanced Filters
CiviDiscount now allows you to specify your own criteria for who should get automatic discounts. This is a powerful feature but does require some technical skill and thorough testing is recommended. Use the api explorer (on your site at the url civicrm/api/explorer) to help you discover the api options you could pass.

You need to specify an API Entity that you want to query and a query string. If you specify contact then the logged in contact id will be passed in as 'id'. For all other api it will be passed in as 'contact_id'

In this image you can see that contacts with a value of 1 in custom id field 65 (which happens to be 'are you retired' in this case) will get an automatic discount if they meet other criteria above - ie a minimum age of 65)

![Screenshot of advanced filter example](/images/advancedFilters.jpg)
