// OTP Form Handler for CS-Cart 4
(function (_, $) {

  document.addEventListener("DOMContentLoaded", function () {
    const inputs = document.querySelectorAll(".otp-inputs input");
    const verifyBtn = document.getElementById("verify-btn");
    const btnText = verifyBtn.querySelector(".btn-text");
    const spinner = verifyBtn.querySelector(".spinner");
    const form = document.getElementById("stcpay-otp-form");

    // Fungsi cek kelengkapan OTP
    function checkOtpComplete() {
      const allFilled = Array.from(inputs).every(input => input.value.trim() !== "");
      verifyBtn.disabled = !allFilled;
    }

    inputs.forEach((input, index) => {
      // Input angka otomatis pindah ke next
      input.addEventListener("input", (e) => {
        const value = e.target.value;
        if (value && index < inputs.length - 1) {
          inputs[index + 1].focus();
        }
        checkOtpComplete();
      });

      // Handle backspace
      input.addEventListener("keydown", (e) => {
        if (e.key === "Backspace" && !input.value && index > 0) {
          inputs[index - 1].focus();
        }
      });

      // Handle paste OTP
      input.addEventListener("paste", (e) => {
        e.preventDefault();
        const pasteData = (e.clipboardData || window.clipboardData).getData("text");
        if (!/^\d+$/.test(pasteData)) return; // hanya angka

        pasteData.split("").forEach((char, i) => {
          if (inputs[index + i]) {
            inputs[index + i].value = char;
          }
        });

        // Fokus ke input terakhir yang terisi
        const lastInput = inputs[Math.min(index + pasteData.length - 1, inputs.length - 1)];
        lastInput.focus();
        checkOtpComplete();
      });
    });
    // Handle submit: disable button + ganti text
    form.addEventListener("submit", function () {
      verifyBtn.disabled = true;
      btnText.textContent = "Verifying...";
      spinner.style.display = "inline-block";
    });


    // Set awal button disable
    checkOtpComplete();
  });

  // Wait for DOM and initialize
  $(document).ready(function () {
    $('.default-header').hide();
    $('#tygh_footer').hide();
  });

})(Tygh, Tygh.$);
