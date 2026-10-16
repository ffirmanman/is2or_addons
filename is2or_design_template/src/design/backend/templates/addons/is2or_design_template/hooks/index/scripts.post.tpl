<script>
(function(_, $) {
    $.ceEvent('on', 'ce.commoninit', function(context) {

        // 1. Stronger page detection (check BODY dispatch and URL)
        var dispatch = $('body').data('ca-dispatch') || '';
        var url = window.location.href;

        // Check whether current page is Register or Edit Profile
        var isValidationPage = (
            dispatch.indexOf('profiles.add') !== -1 ||
            dispatch.indexOf('profiles.update') !== -1 ||
            dispatch.indexOf('auth.register') !== -1 ||
            dispatch.indexOf('is2or_reset_password.reset') !== -1 ||
            url.indexOf('profiles-add') !== -1 || // Fallback for SEO URLs
            url.indexOf('profiles-update') !== -1 ||
            url.indexOf('is2or-reset-password-reset') !== -1
        );

        // 2. Setup icons
        var eyeIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>';
        var eyeSlashIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line></svg>';

        // Find password fields (exclude confirm password fields that have 'confirm' in name/id)
        var $passwordFields = context.find(
            'input[type="password"]:not([id*="confirm"]):not([name*="confirm"]):not(.is2or-ready)'
        );
        
        $passwordFields.each(function() {
            
            var $self = $(this);
            var fieldName = $self.attr('name') || '';
            $self.addClass('is2or-ready');

            // Check if field name contains "password"
            var isTargetFieldPassword = (fieldName.indexOf('password') !== -1);

            // Check if field name contains "user_data[password"
            var isTargetFieldCheck = (fieldName.indexOf('user_data[password') !== -1);
            
            if (isTargetFieldPassword) {
                var $wrapper = $self.closest('.ty-control-group');
                
                $self.css('padding-right', '40px');

                // PERBAIKAN: Bungkus input dengan wrapper khusus agar posisi icon selalu tepat di tengah input field
                var $inputWrapper = $('<div style="position: relative; display: block;"></div>');
                $self.wrap($inputWrapper);
                
                var topPosition = '21px'; 
                // Condition in login
                if (url.indexOf('login') !== -1 || dispatch.indexOf('is2or_reset_password.reset') !== -1 || url.indexOf('is2or_reset_password.reset') !== -1) {
                    topPosition = '18px';
                }
                
                var $toggleBtn = $('<span class="is2or-eye-btn">' + eyeIcon + '</span>').css({
                    position: 'absolute',
                    right: '10px',
                    top: topPosition,
                    transform: 'translateY(-50%)',
                    cursor: 'pointer',
                    color: '#999',
                    display: 'flex',
                    zIndex: '5'
                });

                // Masukkan icon di dalam inputWrapper (bersebelahan dengan input)
                $self.after($toggleBtn);

                // PERBAIKAN: Masukkan msgDiv KE DALAM .ty-control-group (menggunakan append), bukan after.
                // Ini memastikan grid kolom tidak berantakan dan pesan selalu berada di bawah.
                var $msgDiv = $('<div class="is2or-msg-box" style="margin-top: 5px; font-size: 13px; display: none;"></div>');
                $wrapper.append($msgDiv);
                
                var initialValue = $self.val();

                // Toggle password visibility
                $toggleBtn.on('click', function() {
                    var isPass = $self.attr('type') === 'password';
                    var currentValue = $self.val();
                    $self.attr('type', isPass ? 'text' : 'password');
                    $(this).html(isPass ? eyeSlashIcon : eyeIcon);

                    if (isPass) {
                        if (currentValue === initialValue || currentValue.trim() === "") {
                            $self.val('');
                            $self.removeClass('cm-required').prop('required', false);
                            $msgDiv.hide();
                        }
                    }
                    $self.focus();
                });

                // VALIDATION LOGIC
                if (isValidationPage && isTargetFieldCheck) {
                    // CONFIRM PASSWORD MATCH
                    var $passField = context.find('#password1');
                    var $confirmField = context.find('#password2');
                    var $confirmMsgDiv = $('<div class="is2or-confirm-msg" style="margin-top: 5px; font-size: 13px; display: none;"></div>');
                    
                    if ($confirmField.length) {
                        // Pastikan append Confirm Msg hanya dirender 1x di parent #password2
                        if ($self.is('#password2')) {
                            setTimeout(function() {
                                $confirmField.closest('.ty-control-group').append($confirmMsgDiv);
                            }, 100);
                        }

                        var validateConfirmMatch = function() {
                            var p1 = $passField.val();
                            var p2 = $confirmField.val();
                            
                            if (!p2 || p2.trim() === "") {
                                $confirmMsgDiv.hide();
                                return;
                            }

                            if (p2.length > 0 && $self.is('#password2')) {
                                $confirmMsgDiv.hide();
                                $confirmMsgDiv.show();
                                if (p1 === p2) {
                                    $confirmMsgDiv.html('✔ Passwords match.').css('color', '#28a745');
                                } else {
                                    $confirmMsgDiv.html('Password confirmation does not match.').css('color', '#d11202');
                                }
                            } else {
                                $confirmMsgDiv.hide();
                            }
                        };
                    }

                    var validate = function() {
                        var val = $self.val();
                        if (!val || val.trim() === "") {
                            $msgDiv.hide();
                            return;
                        }

                        var criteria = {
                            length: val.length >= 8,
                            upper: /[A-Z]/.test(val),
                            lower: /[a-z]/.test(val),
                            number: /[0-9]/.test(val),
                            symbol: /[^A-Za-z0-9]/.test(val)
                        };

                        var isSecure =
                            criteria.length &&
                            criteria.upper &&
                            criteria.lower &&
                            criteria.number &&
                            criteria.symbol;

                        $msgDiv.show();

                        if (isSecure) {
                            $msgDiv.html('✔ Password is strong and secure.').css('color', '#28a745');
                        } else {
                            var missing = [];
                            if (!criteria.length) missing.push('minimum 8 characters');
                            if (!criteria.upper || !criteria.lower) missing.push('uppercase and lowercase letters');
                            if (!criteria.number) missing.push('numbers');
                            if (!criteria.symbol) missing.push('symbols');

                            // CS-Cart style error color
                            $msgDiv
                                .html('Password requirements: ' + missing.join(', ') + '.')
                                .css('color', '#d11202');
                        }
                    };

                    // Trigger validation on blur or Enter key
                    $self.on('blur', validate);
                    $self.on('keypress', function(e) {
                        if (e.which === 13) validate();
                    });
                    
                    $self.on('blur', validateConfirmMatch);
                    $self.on('keypress', function(e) {
                        if (e.which === 13) validateConfirmMatch();
                    });
                }
            }
        });
    });
}(Tygh, Tygh.$));
</script>