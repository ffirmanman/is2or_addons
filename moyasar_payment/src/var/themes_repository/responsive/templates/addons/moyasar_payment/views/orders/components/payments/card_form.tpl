<form
        id="moyasar-token-form"
        accept-charset="UTF-8"
        action="https://api.moyasar.com/v1/tokens"
        method="POST"
        class="cm-checkout-form card-form">

    {if isset($mfError)}
        <div class="alert alert-error">
            <strong>{__("error")}:</strong> {$mfError}
        </div>
    {else}
        <input type="hidden" name="publishable_api_key" value="{$publicKey}"/>
        <input type="hidden" name="save_only" value="true"/>
        <div class="payment-card-container">
            <h3 class="payment-title">{__("payment_details")}</h3>

            <!-- Cardholder Name -->
            <div class="form-group">
                <label for="card_name">{__("cardholder_name")}</label>
                <input type="text" name="name" id="card_name" placeholder="John Doe"
                       class="form-input" required/>
                <div id="name-error" class="form-error"></div>
            </div>

            <!-- Card Number -->
            <div class="form-group">
                <label for="card_number">{__("card_number")}</label>
                <div class="input-with-icon">
                    <input type="text" name="number" id="card_number"
                           placeholder="•••• •••• •••• ••••"
                           class="form-input" required maxlength="19"/>
                    <img id="card-brand-icon" src="" alt=""
                         style="display:none;" class="card-icon">
                </div>
                <div id="moyasar-loader" class="loader-overlay" style="display: none;">
                    <div class="spinner"></div>
                </div>
            </div>

            <!-- Expiry & CVC -->
            <div class="form-row">
                <div class="form-group half">
                    <label for="card_expiry">{__("expiry_date")}</label>
                    <input type="text" name="expiry_date" id="card_expiry"
                           placeholder="MM/YY"
                           class="form-input" required maxlength="5"/>
                    <div id="expiry-error" class="form-error"></div>
                </div>

                <div class="form-group half">
                    <label for="card_cvc">{__("cvv")}</label>
                    <input type="text" name="cvc" id="card_cvc"
                           placeholder="CVC"
                           class="form-input" required maxlength="4"/>
                </div>
            </div>
        </div>
    {/if}
</form>
