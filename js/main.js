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

    /**
     * Function copied from templates/CRM/Price/Form/Calculate.tpl - calculateTotalFee() (in case it gets changed somewhere along the way)
     * This Calculates the total fee on CiviCRM
     */
    function discountCalculateTotalFee() {
      var totalFee = 0;
      $('#priceset [price]').each(function () {
        totalFee = totalFee + $(this).data('line_raw_total');
      });

      return totalFee;
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
        if (discountCalculateTotalFee() == 0) {
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
