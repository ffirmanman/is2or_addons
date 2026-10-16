<div class="stcpay-box">
    <div class="stcpay-header">
        <!-- Ganti src jika hosting mandiri -->
        <img src="https://commons.wikimedia.org/wiki/Special:Redirect/file/Stc_pay.svg"
             alt="STC Pay" class="stcpay-logo">
        <span>{__("pay_with_stcpay")}</span>
    </div>
    <div class="form-group">
        <label for="stc_mobile">{__("stcpay_mobile_number")}</label>
        <input type="text" name="mobile" id="stc_mobile"
               placeholder="05xxxxxxxx"
               class="input-full stcpay-input" required/>
        <small class="stcpay-hint">
            {__("enter_your_registered_stcpay_number")}
        </small>
    </div>
</div>
