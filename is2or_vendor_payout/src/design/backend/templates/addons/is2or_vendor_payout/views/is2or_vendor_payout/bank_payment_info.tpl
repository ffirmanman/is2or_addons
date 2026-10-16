<div class="is2or-bank-payment-info">

    {** ── Info Payout ── **}
    <table class="table table-middle">
        <tbody>
            <tr>
                <th width="35%">{__("vendor")}</th>
                <td>{$company.company|default:"-"}</td>
            </tr>
            <tr>
                <th>{__("vendor_payouts.transaction_value")}</th>
                <td>{include file="common/price.tpl" value=$payout.payout_amount}</td>
            </tr>
            <tr>
                <th>{__("comment")}</th>
                <td>{$payout.comments|default:"-"}</td>
            </tr>
        </tbody>
    </table>

    <h5 style="margin: 15px 0 8px;">{__("is2or_vendor_payout.bank_accounts")}</h5>

    <table class="table table-middle">
        <tbody>
            <tr>
                <th width="35%">{__("country")}</th>
                <td>{$bank_details.country|default:"-"}</td>
            </tr>
            <tr>
                <th>{__("city")}</th>
                <td>{$bank_details.city|default:"-"}</td>
            </tr>
            <tr>
                <th>{__("is2or_vendor_payout.bank_name")}</th>
                <td>{$bank_details.bank_name|default:"-"}</td>
            </tr>
            <tr>
                <th>{__("is2or_vendor_payout.account_name")}</th>
                <td>{$bank_details.account_name|default:"-"}</td>
            </tr>
            <tr>
                <th>{__("is2or_vendor_payout.iban")}</th>
                <td>{$bank_details.iban|default:"-"}</td>
            </tr>
            <tr>
                <th>{__("is2or_vendor_payout.mobile")}</th>
                <td>{$bank_details.mobile|default:"-"}</td>
            </tr>
        </tbody>
    </table>

    <h5 style="margin: 20px 0 8px;">{__("is2or_vendor_payout.transfer_receipt")}</h5>

    <form
        id="is2or-receipt-form"
        method="post"
        enctype="multipart/form-data"
        action="{"is2or_vendor_payout.save_receipt"|fn_url}"
    >
        <input type="hidden" name="payout_id" value="{$payout.payout_id|intval}" />
        <input type="hidden" name="order_id"  value="{$payout.order_id|intval}" />
        <input type="hidden" name="security_hash" class="cm-no-hide-input" value="{""|fn_generate_security_hash}" />

        <table class="table table-middle">
            <tbody>

                {** 0. Status **}
                <tr>
                    <th width="35%">{__("status")}</th>
                    <td>
                        <select
                            name="receipt[approval_status]"
                            id="approval_status"
                            required
                            style="width:100%; padding:6px 10px; border:1px solid #555; border-radius:4px; font-size:13px;"
                        >
                            <option value="C" selected>Completed</option>
                            <option value="D">Declined</option>
                        </select>
                    </td>
                </tr>

                {** 1. Upload Transfer Receipt **}
                <tr>
                    <th width="35%">
                        {__("is2or_vendor_payout.receipt_image")}
                    </th>
                    <td>
                        {if $receipt_image_url}
                            <div style="margin-bottom: 8px;">
                                <a href="{$receipt_image_url}" target="_blank">
                                    <img
                                        src="{$receipt_image_url}"
                                        alt="{__("is2or_vendor_payout.receipt_image")}"
                                        style="max-width:180px; max-height:120px; border:1px solid #444; border-radius:4px; cursor:pointer;"
                                    />
                                </a>
                                <div style="margin-top:4px; font-size:12px; color:#aaa;">
                                    {__("is2or_vendor_payout.receipt_image_replace_hint")}
                                </div>
                            </div>
                        {/if}
                        <input
                            type="file"
                            name="receipt_image"
                            id="receipt_image"
                            accept="image/jpeg,image/png,image/gif,image/webp"
                            style="display:block; margin-top:4px;"
                        />
                        <div style="font-size:11px; color:#888; margin-top:4px;">
                            {__("is2or_vendor_payout.receipt_image_hint")}
                        </div>
                    </td>
                </tr>

                {** 2. Bank Name **}
                <tr>
                    <th>{__("is2or_vendor_payout.receipt_bank_name")}</th>
                    <td>
                        <input
                            type="text"
                            name="receipt[bank_name]"
                            class="input-text"
                            value="{$existing_receipt.bank_name|default:''|escape}"
                            placeholder="{__("is2or_vendor_payout.receipt_bank_name_placeholder")}"
                            style="width:100%;"
                        />
                    </td>
                </tr>

                {** 3. Account Name **}
                <tr>
                    <th>{__("is2or_vendor_payout.account_name")}</th>
                    <td>
                        <input
                            type="text"
                            name="receipt[account_name]"
                            class="input-text"
                            value="{$existing_receipt.account_name|default:''|escape}"
                            placeholder="{__("is2or_vendor_payout.receipt_account_name_placeholder")}"
                            style="width:100%;"
                        />
                    </td>
                </tr>

                {** 4. IBAN **}
                <tr>
                    <th>{__("is2or_vendor_payout.iban")}</th>
                    <td>
                        <input
                            type="text"
                            name="receipt[iban]"
                            class="input-text"
                            value="{$existing_receipt.iban|default:''|escape}"
                            placeholder="{__("is2or_vendor_payout.receipt_iban_placeholder")}"
                            style="width:100%;"
                        />
                    </td>
                </tr>

                {** 5. Transaction Value **}
                <tr>
                    <th>{__("vendor_payouts.transaction_value")}</th>
                    <td>
                        <input
                            type="number"
                            name="receipt[transaction_value]"
                            class="input-text"
                            value="{$existing_receipt.transaction_value|default:$payout.payout_amount}"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            style="width:100%; background-color:#1e1e1e; color:#888; cursor:not-allowed;"
                            readonly
                        />
                    </td>
                </tr>

                {** 6. Comment **}
                <tr>
                    <th>{__("comment")}</th>
                    <td>
                        <textarea
                            name="receipt[comment]"
                            id="receipt_comment"
                            class="input-text"
                            rows="3"
                            required
                            placeholder="{__("is2or_vendor_payout.receipt_comment_placeholder")}"
                            style="width:100%; resize:vertical;"
                        >{$existing_receipt.comment|default:''|escape}</textarea>
                    </td>
                </tr>

            </tbody>
        </table>

    </form>

    {** ── Footer: Button Update & Close ── **}
    <div style="
        display: flex;
        justify-content: flex-end;
        gap: 8px;
        margin-top: 20px;
        padding-top: 12px;
        border-top: 1px solid #3a3a3a;
    ">
        <button
            type="button"
            class="btn btn-default"
            onclick="fn_is2or_close_dialog();"
        >
            {__("close")}
        </button>

        <button
            type="button"
            id="is2or-receipt-submit-btn"
            class="btn btn-primary"
            onclick="fn_is2or_submit_receipt();"
        >
            {__("update")}
        </button>
    </div>

</div>{* /.is2or-bank-payment-info *}

<script>
(function () {
    'use strict';

    window.fn_is2or_submit_receipt = function () {
        var form = document.getElementById('is2or-receipt-form');
        var btn  = document.getElementById('is2or-receipt-submit-btn');

        if (!form) { return; }

        // ── Validation mandatory fields ──
        var statusEl  = document.getElementById('approval_status');
        var commentEl = document.getElementById('receipt_comment'); {* FIX: id sudah ditambahkan di textarea *}

        if (!statusEl || !statusEl.value) {
            alert('Please select a Status.');
            statusEl && statusEl.focus();
            return;
        }

        if (!commentEl || commentEl.value.trim() === '') {
            alert('Please fill in the Comment field.');
            commentEl && commentEl.focus();
            return;
        }
        // ── End Validation ──

        btn.disabled  = true;
        btn.innerHTML = '<i class="icon-spinner icon-spin"></i> Saving...';

        var formData = new FormData(form);

        if (typeof Tygh !== 'undefined' && Tygh.security_token) {
            formData.set('security_token', Tygh.security_token);
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);

        xhr.onload = function () {
            if (xhr.status === 200) {
                var closeBtn = form.closest('.ui-dialog')
                    ? form.closest('.ui-dialog').querySelector('.ui-dialog-titlebar-close')
                    : null;

                if (closeBtn) { closeBtn.click(); }

                setTimeout(function () { window.location.reload(); }, 150);
            } else {
                btn.disabled  = false;
                btn.innerHTML = '{__("update")}';
                alert('Error: ' + xhr.status);
            }
        };

        xhr.onerror = function () {
            btn.disabled  = false;
            btn.innerHTML = '{__("update")}';
        };

        xhr.send(formData);
    };

    window.fn_is2or_close_dialog = function () {
        var closeBtn = document.querySelector('.ui-dialog-titlebar-close');
        if (closeBtn) { closeBtn.click(); }
    };

})();
</script>