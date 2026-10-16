{foreach from=$order_info.shipping item="shipping" name="f_shipp"}
    {if $shipping.module == 'torod'}
        <script type="text/javascript">
          (function(_, $) {
            $(document).ready(function() {
              var $shippingSelect = $('select[id^="shipping_name"]');
              if ($shippingSelect.length) {
                $shippingSelect.prop('disabled', true);
                if (!$('input[name="' + $shippingSelect.attr('name') + '"]').length) {
                  $shippingSelect.after('<input type="hidden" name="' + $shippingSelect.attr('name') + '" value="' + $shippingSelect.val() + '" />');
                }
              }

              var providerName = "{$shipping.service_params.torod_provider_name}";
              var $carrierSelect = $('select[id^="carrier_"]');

              if ($carrierSelect.length && providerName) {
                if ($carrierSelect.find("option[value='torod']").length == 0) {
                  $carrierSelect.append('<option value="torod">' + providerName + '</option>');
                }

                $carrierSelect.val('torod').prop('disabled', true);

                if (!$('input[name="' + $carrierSelect.attr('name') + '"]').length) {
                  $carrierSelect.after('<input type="hidden" name="' + $carrierSelect.attr('name') + '" value="torod" />');
                }
              }

              $('select[name^="shipping_ids"]').prop('disabled', true);
              $('.cm-new-shipment').hide();
            });
          })(Tygh, Tygh.$);
        </script>
    {/if}
{/foreach}
