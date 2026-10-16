{script src="js/addons/moyasar_payment/frontend/jquery.creditCardValidator.js"}
<style>
  .payment-card-container {
    background: #fff;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    margin-bottom: 20px;
  }

  .payment-title {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 15px;
    color: #333;
    text-align: center;
  }

  .form-group {
    margin-bottom: 15px;
  }


  .form-row {
    display: flex;
    gap: 15px;
  }

  .form-group.half {
    flex: 1;
  }

  .form-input {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #ddd;
    border-radius: 8px;
    font-size: 15px;
    transition: border-color 0.2s ease;
  }

  .form-input:focus {
    border-color: #6c2bd9; /* bisa sesuaikan dengan warna brand */
    outline: none;
  }

  .input-with-icon {
    position: relative;
  }

  .card-icon {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    height: 24px;
  }

  .form-error {
    display: none;
    font-size: 13px;
    color: #d32f2f;
    margin-top: 5px;
  }

  .moyasar-payment-methods-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin-top: 20px;
  }

  .payment-methods {
    display: flex;
    gap: 20px;
    margin-bottom: 20px;
    font-weight: 600;
  }

  .payment-methods label {
    cursor: pointer;
  }

  .moyasar-forms {
    width: 100%;
    max-width: 400px; /* supaya form tidak melebar */
  }

  #moyasar-card-ui,
  #moyasar-stcpay-ui {
    margin-top: 10px;
  }

  .stcpay-box {
    border: 2px solid #6a1b9a;
    border-radius: 8px;
    padding: 16px;
    background: #faf8ff;
    margin-top: 10px;
  }

  .stcpay-header {
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: bold;
    font-size: 16px;
    color: #6a1b9a;
    margin-bottom: 12px;
  }

  .stcpay-logo {
    height: 28px;
  }

  .stcpay-input {
    border: 1px solid #6a1b9a;
    border-radius: 4px;
  }

  .stcpay-hint {
    display: block;
    font-size: 12px;
    color: #777;
    margin-top: 4px;
  }
</style>
<div class="litecheckout__container" id="moyasar-div">
  <div class="moyasar-payment-methods-container">

    <!-- Radio pilihan -->
    <div class="payment-methods">
      <label>
        <input type="radio" name="moyasar_method" value="stcpay" checked onchange="toggleMoyasarUI('stcpay')">
        {__("pay_with_stcpay")}
      </label>
      <label>
        <input type="radio" name="moyasar_method" value="card"  onchange="toggleMoyasarUI('card')">
        {__("pay_with_card")}
      </label>
    </div>

    <!-- Container form -->
    <div class="moyasar-forms">
      <div id="moyasar-stcpay-ui">
        {include file="addons/moyasar_payment/views/orders/components/payments/stc_form.tpl"}
      </div>
      <div id="moyasar-card-ui" style="display: none;">
        {include file="addons/moyasar_payment/views/orders/components/payments/card_form.tpl"}
      </div>
    </div>

  </div>
</div>
<script>
  var fc = '#litecheckout_payments_form'; //form tag
  var pl = '#litecheckout_place_order'; //submit button
  function toggleMoyasarUI(method) {
    if (method === 'card') {
      $('#moyasar-card-ui').show();
      $('#moyasar-stcpay-ui').hide();
    } else {
      $('#moyasar-card-ui').hide();
      $('#moyasar-stcpay-ui').show();
    }
  }
</script>
{literal}
    <script>
      $(document).ready(function () {
        const cardBrandIconMap = {
          visa: 'https://img.icons8.com/color/48/visa.png',
          mastercard: 'https://img.icons8.com/color/48/mastercard-logo.png',
          amex: 'https://img.icons8.com/color/48/amex.png',
          discover: 'https://img.icons8.com/color/48/discover.png'
        };

        $('#card_number').validateCreditCard(function (result) {
          const $input = $('#card_number');
          const $icon = $('#card-brand-icon');

          if (result.valid) {
            $input.removeClass('input-invalid').addClass('input-valid');
          } else {
            $input.removeClass('input-valid').addClass('input-invalid');
          }

          if (result.card_type && cardBrandIconMap[result.card_type.name]) {
            $icon.attr('src', cardBrandIconMap[result.card_type.name]).show();
          } else {
            $icon.hide();
          }
        });

        // Format input every 4 digits
        $('#card_number').on('input', function () {
          let val = $(this).val();

          // Remove non-numeric characters
          val = val.replace(/\D/g, '');

          // Format as groups of 4 digits
          val = val.replace(/(\d{4})(?=\d)/g, '$1 ');

          // Set the value back to the input
          $(this).val(val);
        });

        $('#card_cvc').on('input', function () {
          const val = $(this).val();
          if (/^\d{3,4}$/.test(val)) {
            $(this).removeClass('input-invalid').addClass('input-valid');
          } else {
            $(this).removeClass('input-valid').addClass('input-invalid');
          }
        });

        $('#card_expiry').on('input', function () {
          let val = $(this).val();

          // replace non numeric
          val = val.replace(/\D/g, '');

          // Format to MM/YY
          if (val.length >= 2) {
            val = val.substring(0, 2) + '/' + val.substring(2, 4);
          }

          $(this).val(val);

          // validate month and year
          const month = parseInt(val.substring(0, 2), 10);
          const year = parseInt(val.substring(3, 5), 10);
          const now = new Date();
          const currentYear = now.getFullYear() % 100;  // Ambil 2 digit tahun saat ini
          const currentMonth = now.getMonth() + 1;

          let valid = false;

          if (val.length === 5) {  // Validasi hanya jika input lengkap MM/YY
            if (month >= 1 && month <= 12) {
              if (year > currentYear || (year === currentYear && month >= currentMonth)) {
                valid = true;
              }
            }
          }

          $('#card_expiry').removeClass('input-valid input-invalid');
          if (valid) {
            $('#card_expiry').addClass('input-valid');
          } else {
            $('#card_expiry').addClass('input-invalid');
          }
        });
      });

      async function initiatePayment(event) {
        // This line is crucial to prevent the default form behaviour
        event.preventDefault();
        const method = document.querySelector('input[name="moyasar_method"]:checked').value;
        if (method === 'card') {
          const form = document.getElementById('moyasar-token-form');
          if (!form) {
            alert('form moyasar not found');
          }
          const formData = new FormData(form);
          const tokenData = Object.fromEntries(formData);

          const name = tokenData.name || '';
          if (name.trim().split(' ').length < 2) {
            $('#name-error').text('Name must contain at least two.').show();
            return;
          } else {
            $('#name-error').text('').hide();
          }
          // remove space for number CC
          if (tokenData.number) {
            tokenData.number = tokenData.number.replace(/\s/g, '');
          }
          // reformat expiry date
          let expiry = tokenData.expiry_date || '';

          let validExpiry = false;
          if (expiry.length === 5 && expiry.includes('/')) {
            const [month, year] = expiry.split('/');

            // format 2 digit for month
            const formattedMonth = month.padStart(2, '0');

            // format 4 digit for year
            let formattedYear = year.length === 2 ? '20' + year : year;

            // validate the date should not expire
            const now = new Date();
            const currentYear = now.getFullYear() % 100;  // Ambil 2 digit tahun saat ini
            const currentMonth = now.getMonth() + 1;

            if (formattedMonth >= 1 && formattedMonth <= 12) {
              if (parseInt(formattedYear) > currentYear || (parseInt(formattedYear) === currentYear && formattedMonth >= currentMonth)) {
                validExpiry = true;
              }
            }
            tokenData.month = formattedMonth;
            tokenData.year = formattedYear;
          }

          if (!validExpiry) {
            $('#expiry-error').text('Invalid expiry date. Please use MM/YY and ensure it is not expired.').show();
            return;
          } else {
            $('#expiry-error').text('').hide();
          }
          delete tokenData.expiry_date;

          // Create temporary checkout token
          let response = await fetch('https://api.moyasar.com/v1/tokens', {
            method: 'post',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify(tokenData),
          });

          response = await response.json();
          console.log(response);
          let token = response.id;
          if (token) {
            $(fc).append('<input type="hidden" name="moyasar_method" value="card">');
            $(fc).append('<input type="hidden" name="moyasar_token" value="' + token + '">');
            $(fc).submit();
          } else {
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: response.message
            });
          }
        } else if (method === 'stcpay') {
          const mobile = $('#stc_mobile').val();
          const regex = /^(?:05\d{8}|\+9665\d{8}|009665\d{8})$/;
          if (!mobile) {
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: 'Please enter STC Pay mobile number'
            });
            return;
          }

          if (!regex.test(mobile)) {
            $.ceNotification('show', {
              type: 'E',
              title: _.tr('error'),
              message: 'Please enter valid mobile number'
            });
            return;
          }
          $(fc).append('<input type="hidden" name="moyasar_method" value="stcpay">');
          $(fc).append('<input type="hidden" name="stc_mobile" value="' + mobile + '">');
          $(fc).submit();
        }
      }

      $(pl).on('click', function (e) {
        e.preventDefault();
        initiatePayment(e)
      })
    </script>
{/literal}
