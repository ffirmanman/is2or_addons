<div class="control-group setting-wide">
    <label for="moyasar_payment_iban" class="control-label">{__('moyasar_payment.iban')}:</label>
    <div class="controls">
        <input type="text" id="moyasar_payment_iban" class="input-large" name="mp_settings[iban]" value="{$mp_settings.iban}" />
    </div>
<!--moyasar_group_iban--></div>

<div class="control-group setting-wide">
    <label for="moyasar_payment_currency" class="control-label">{__('currency')}:</label>
    <div class="controls">
        <input type="text" id="moyasar_payment_currency" readonly value="{$mp_settings.currency}" />
    </div>
<!--moyasar_group_currency--></div>

<div class="control-group setting-wide">
    <label for="moyasar_payment_payout_id" class="control-label">{__('moyasar_payment.payout_id')}:</label>
    <div class="controls">
        <input type="text" id="moyasar_payment_payout_id" class="input-large" readonly value="{$mp_settings.payout_id}" />

        <div>
            <button class="btn btn-info" type="button" style="margin-top: 10px;" id="btn_generate">
                {if $mp_settings.payout_id}{__('moyasar_payment.regenerate_payout_id')}
                {else}{__('moyasar_payment.generate_payout_id')}
                {/if}
            </button>
        </div>
    </div>
</div>

<script>
(function(_, $) {
    $('#btn_generate').on('click', function() {
        const iban = $('#moyasar_payment_iban').val();

        if (!iban) {
            return $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: '{__('moyasar_payment.iban_required')}',
            });
        }
            

        $.ceAjax('request', fn_url('moyasar_payment.create_payout'), {
            method: 'POST',
            data: {
                iban
            },
            callback: function(data) {
                const settings = data.mp_settings || {};
                
                Object.keys(settings).map(key => {
                    {literal}
                        $(`#moyasar_payment_${key}`).val(settings[key]);
                    {/literal}
                });
            }
        });
    });
}(Tygh, Tygh.$));
</script>