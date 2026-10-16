{$mfPaymentId = ''}
{foreach $payment_methods as $payment}
    {if $payment.payment == 'moyasar'}{$mfPaymentId = $payment.payment_id}{/if}
{/foreach}
<script>
  (function (_, $) {
    function moyasar_hidePlaceOrder() {
      if ($('#radio_{$mfPaymentId}').is(':checked')) {
        $('#litecheckout_place_order').hide();
      }
    }

    $.ceEvent('on', 'ce.loaderhide', function () {
      moyasar_hidePlaceOrder();
    });
    moyasar_hidePlaceOrder();
  })(Tygh, Tygh.$);
</script>
