CRM.$(function($) {
  if ($('#discountcode').length > 0) {
    skipPaymentMethod();
    function showHidePayment(flag) {
      $('.payment_options-group, div.payment_processor-section, #payment_information').toggle(!flag);
      if (flag) {
        // also unset selected payment methods
        $('input[name="payment_processor"]').prop('checked', false);
      }
    }

    function skipPaymentMethod() {
      var flag = false;
      $('.price-set-option-content input').each( function(){
        currentTotal = $(this).attr('data-amount').replace(/[^\/\d]/g,'');
        if( $(this).is(':checked') && currentTotal == 0 ) {
            flag = true;
        }
      });
      $('.price-set-option-content input').change( function () {
        if ($(this).attr('data-amount').replace(/[^\/\d]/g,'') == 0 ) {
          flag = true;
        } else {
          flag = false;
        }
        showHidePayment(flag);
      });
      showHidePayment(flag);
    }
  }
});

