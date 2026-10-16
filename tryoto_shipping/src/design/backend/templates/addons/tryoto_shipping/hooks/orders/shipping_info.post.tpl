{foreach from=$order_info.shipping item="shipping" name="f_shipp"}
    {if $shipping.module == 'tryoto'}
        <script type="text/javascript">
          (function(_, $) {
            $(document).ready(function() {
              // 1. Disable dropdown Carrier
              var $shippingSelect = $('select[id^="shipping_name"]');
              if ($shippingSelect.length) {
                $shippingSelect.prop('disabled', true);
                // Tambahkan hidden input agar value shipping_id tetap terkirim
                if (!$('input[name="' + $shippingSelect.attr('name') + '"]').length) {
                  $shippingSelect.after('<input type="hidden" name="' + $shippingSelect.attr('name') + '" value="' + $shippingSelect.val() + '" />');
                }
              }

              // --- 2. Handling Carrier ---
              var providerName = "{$shipping.service_params.tryoto_provider_name}";
              var $carrierSelect = $('select[id^="carrier_"]');

              if ($carrierSelect.length && providerName) {
                // Tambahkan opsi "tryoto" jika belum ada
                if ($carrierSelect.find("option[value='tryoto']").length == 0) {
                  $carrierSelect.append('<option value="tryoto">' + providerName + '</option>');
                }

                $carrierSelect.val('tryoto').prop('disabled', true);

                // Tambahkan hidden input agar value carrier (tryoto) tetap terkirim
                if (!$('input[name="' + $carrierSelect.attr('name') + '"]').length) {
                  $carrierSelect.after('<input type="hidden" name="' + $carrierSelect.attr('name') + '" value="tryoto" />');
                }
              }

              // --- 3. UI Cleanup ---
              $('select[name^="shipping_ids"]').prop('disabled', true);
              $('.cm-new-shipment').hide();
            });
          })(Tygh, Tygh.$);
        </script>
    {/if}
{/foreach}
