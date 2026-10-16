{**
 * OTP verification screen shown right after a successful email/password login
 * when the "Email AND SMS OTP" login mode is active.
 *
 * This renders as a full-screen dimmed overlay with a centered card, to give a
 * "popup" feel even though it's technically its own page/route (see chat notes
 * on why a same-page AJAX modal needs theme-specific JS integration).
 *}
<div class="is2or-otp-overlay">
    <div class="is2or-otp-card">
        <div class="is2or-otp-icon">
            <svg viewBox="0 0 24 24" width="32" height="32" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 2L4 6V11C4 16 7.5 20.5 12 22C16.5 20.5 20 16 20 11V6L12 2Z" stroke="#ffffff" stroke-width="1.6" stroke-linejoin="round"/>
                <path d="M9 12l2 2 4-4" stroke="#ffffff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>

        <h2 class="is2or-otp-title">{__("is2or_sms_integration.otp_verify_title")}</h2>
        <p class="is2or-otp-subtitle">{__("is2or_sms_integration.otp_verify_subtitle")}</p>

        <form action="{"is2or_sms_integration.otp_verify_submit"|fn_url}" method="post" id="is2or_otp_form" class="is2or-otp-form">
            <div class="is2or-otp-inputs" id="is2or_otp_inputs">
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="is2or-otp-digit" autocomplete="one-time-code" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="is2or-otp-digit" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="is2or-otp-digit" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="is2or-otp-digit" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="is2or-otp-digit" />
                <input type="text" inputmode="numeric" pattern="[0-9]*" maxlength="1" class="is2or-otp-digit" />
            </div>
            <input type="hidden" name="otp_code" id="is2or_otp_code" value="" />

            <button type="submit" class="is2or-otp-submit">{__("is2or_sms_integration.otp_verify_button")}</button>
        </form>

        <div class="is2or-otp-timer" id="is2or_otp_timer">
            {__("is2or_sms_integration.otp_expires_in")} <span id="is2or_otp_countdown">{$otp_ttl_minutes}:00</span>
        </div>

        <div class="is2or-otp-footer">
            <form action="{"is2or_sms_integration.otp_resend"|fn_url}" method="post" class="is2or-otp-inline-form">
                <button type="submit" class="is2or-otp-link">{__("is2or_sms_integration.otp_resend")}</button>
            </form>
            <span class="is2or-otp-dot">&middot;</span>
            <form action="{"is2or_sms_integration.otp_cancel"|fn_url}" method="post" class="is2or-otp-inline-form">
                <button type="submit" class="is2or-otp-link is2or-otp-link-muted">{__("is2or_sms_integration.otp_cancel")}</button>
            </form>
        </div>
    </div>
</div>

<style>
.is2or-otp-overlay {
    position: fixed;
    inset: 0;
    z-index: 99999;
    background: rgba(17, 24, 39, 0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    animation: is2or-fade-in 0.25s ease-out;
}

@keyframes is2or-fade-in {
    from { opacity: 0; }
    to { opacity: 1; }
}

.is2or-otp-card {
    background: #ffffff;
    border-radius: 16px;
    max-width: 400px;
    width: 100%;
    padding: 36px 32px 28px;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
    animation: is2or-pop-in 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes is2or-pop-in {
    from { opacity: 0; transform: scale(0.92) translateY(10px); }
    to { opacity: 1; transform: scale(1) translateY(0); }
}

.is2or-otp-icon {
    width: 56px;
    height: 56px;
    margin: 0 auto 16px;
    border-radius: 50%;
    background: linear-gradient(135deg, #fb923c, #f97316);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 20px rgba(249, 115, 22, 0.35);
}

.is2or-otp-title {
    margin: 0 0 6px;
    font-size: 20px;
    font-weight: 700;
    color: #111827;
}

.is2or-otp-subtitle {
    margin: 0 0 24px;
    font-size: 13.5px;
    line-height: 1.5;
    color: #6b7280;
}

.is2or-otp-inputs {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin-bottom: 20px;
}

.is2or-otp-digit {
    width: 44px;
    height: 52px;
    text-align: center;
    font-size: 22px;
    font-weight: 700;
    color: #111827;
    border: 1.5px solid #d1d5db;
    border-radius: 10px;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}

.is2or-otp-digit:focus {
    border-color: #f97316;
    box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.2);
}

.is2or-otp-submit {
    width: 100%;
    background: linear-gradient(135deg, #f97316, #d97706);
    color: #ffffff;
    border: none;
    border-radius: 10px;
    padding: 13px 20px;
    font-size: 15px;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.15s, transform 0.1s;
}

.is2or-otp-submit:hover {
    opacity: 0.92;
}

.is2or-otp-submit:active {
    transform: scale(0.98);
}

.is2or-otp-timer {
    margin-top: 16px;
    font-size: 12.5px;
    color: #9ca3af;
}

#is2or_otp_countdown {
    font-weight: 700;
    color: #ea580c;
}

.is2or-otp-footer {
    margin-top: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.is2or-otp-inline-form {
    display: inline;
}

.is2or-otp-link {
    background: none;
    border: none;
    padding: 0;
    color: #ea580c;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.is2or-otp-link:hover {
    text-decoration: underline;
    color: #c2410c;
}

.is2or-otp-link-muted {
    color: #9ca3af;
}

.is2or-otp-dot {
    color: #d1d5db;
}
</style>

<script>
(function () {
    var inputs = document.querySelectorAll('#is2or_otp_inputs .is2or-otp-digit');
    var hiddenField = document.getElementById('is2or_otp_code');
    var form = document.getElementById('is2or_otp_form');

    function syncHiddenField() {
        var value = '';
        inputs.forEach(function (input) {
            value += input.value.replace(/[^0-9]/g, '');
        });
        hiddenField.value = value;
    }

    inputs.forEach(function (input, index) {
        input.addEventListener('input', function () {
            input.value = input.value.replace(/[^0-9]/g, '').slice(0, 1);
            if (input.value && inputs[index + 1]) {
                inputs[index + 1].focus();
            }
            syncHiddenField();
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Backspace' && !input.value && inputs[index - 1]) {
                inputs[index - 1].focus();
            }
        });

        input.addEventListener('paste', function (e) {
            var pasted = (e.clipboardData || window.clipboardData).getData('text').replace(/[^0-9]/g, '');
            if (pasted.length) {
                e.preventDefault();
                pasted.split('').forEach(function (digit, i) {
                    if (inputs[i]) {
                        inputs[i].value = digit;
                    }
                });
                syncHiddenField();
                var nextIndex = Math.min(pasted.length, inputs.length - 1);
                inputs[nextIndex].focus();
            }
        });
    });

    if (inputs.length) {
        inputs[0].focus();
    }

    form.addEventListener('submit', function (e) {
        syncHiddenField();
        if (hiddenField.value.length < inputs.length) {
            e.preventDefault();
        }
    });

    // Simple countdown display (visual only — the server independently enforces expiry).
    var totalSeconds = {$otp_ttl_minutes} * 60;
    var countdownEl = document.getElementById('is2or_otp_countdown');
    var timer = setInterval(function () {
        totalSeconds -= 1;
        if (totalSeconds <= 0) {
            clearInterval(timer);
            countdownEl.textContent = '00:00';
            return;
        }
        var m = Math.floor(totalSeconds / 60);
        var s = totalSeconds % 60;
        countdownEl.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
    }, 1000);
})();
</script>
