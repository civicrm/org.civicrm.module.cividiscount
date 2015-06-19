{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      if (cj('#discountcode').length > 0) {
        skipPaymentMethod();
      }
    });
    function showHidePayment(flag) {
      var payment_options = cj(".payment_options-group");
      var payment_processor = cj("div.payment_processor-section");
      var payment_information = cj("div#payment_information");
      if (flag) {
        payment_options.hide();
        payment_processor.hide();
        payment_information.hide();
        // also unset selected payment methods
        cj('input[name="payment_processor"]').removeProp('checked');
      }
      else {
        payment_options.show();
        payment_processor.show();
        payment_information.show();
      }
    }
  
  function skipPaymentMethod() {
    var flag = false;
    cj('.price-set-option-content input').each( function(){
      currentTotal = cj(this).attr('data-amount').replace(/[^\/\d]/g,'');
      if( cj(this).is(':checked') && currentTotal == 0 ) {
          flag = true;
      }
    });
    cj('.price-set-option-content input').change( function () {
      if (cj(this).attr('data-amount').replace(/[^\/\d]/g,'') == 0 ) {
        flag = true;
      } else {
        flag = false;
      }
      showHidePayment(flag);
    });
    showHidePayment(flag);
  }

    </script> 
{/literal}