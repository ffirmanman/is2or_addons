(function (_, $) {
    'use strict';

    // ========== FUNGSI BANTU ==========
    function getPrefixElement($input) {
        var groupId = $input.data('caPhoneMaskGroupId');
        return groupId ? $('#prefix_' + groupId) : $();
    }

    function getCountryFromPhone(fullNumber) {
        if (!fullNumber) return null;
        try {
            var phoneNumber = libphonenumber.parsePhoneNumber(fullNumber);
            if (phoneNumber && phoneNumber.country) {
                return {
                    country: phoneNumber.country,
                    prefix: '+' + phoneNumber.countryCallingCode
                };
            }
        } catch (e) {
            return null;
        }
        return null;
    }

    function updateFlag($input, country) {
        if (!$input.length || !country) return;
        var isUndefinedCountry = country === 'UNDEFINED_COUNTRY';
        var flagIconCode = isUndefinedCountry ? '01' : country.toLowerCase();
        var flagClass = _.area === 'A' ? 'flag' : 'ty-flag';
        var toggleAttrSelector = _.area === 'A' ? '[data-toggle="dropdown"]' : '[data-ca-toggle="dropdown"]';
        var $icon = $(toggleAttrSelector + ' .' + flagClass, $input.parent());
        if ($icon.length) {
            $.each($icon.prop('classList'), function (index, className) {
                if (!className.startsWith(flagClass + '-')) {
                    return;
                }
                $icon.removeClass(className);
            });
            $icon.addClass(flagClass + '-' + flagIconCode);
        }
    }

    // ========== INISIALISASI ==========
    $.ceEvent('on', 'ce.commoninit', function (context) {
        if (_.phone_validation_mode !== 'phone_number_with_country_selection') {
            return;
        }

        var $phoneElems = context.find('.cm-mask-phone');
        if (!$phoneElems.length) {
            return;
        }

        $phoneElems.each(function () {
            var $input = $(this);
            var id = $input.attr('id');
            if (!$('label[for="' + id + '"]').length) {
                return;
            }

            phoneNumberWithCountrySelectionInit($input);
            bindEvents($input);
            $input.addClass('js-mask-phone-inited');

            // =========================================================
            // PROSES NILAI DARI DATABASE
            // =========================================================
            var fullValue = $input.val() || '';
            var $prefix = getPrefixElement($input);

            // Bersihkan nilai jika mengandung email (proteksi autofill)
            if (fullValue && fullValue.indexOf('@') !== -1) {
                fullValue = '';
                $input.val('');
            }

            if (fullValue) {
                var parsed = getCountryFromPhone(fullValue);
                if (parsed) {
                    var prefix = parsed.prefix;
                    var numberPart = fullValue.replace(prefix, '');
                    
                    // Update prefix element
                    if ($prefix.length) {
                        $prefix.text(prefix);
                    }
                    
                    // Update data di input
                    $input.data('caPhoneMaskCountry', parsed.country);
                    $input.data('caPhoneMaskSymbol', prefix);
                    $input.val(numberPart);
                    
                    // Update flag
                    updateFlag($input, parsed.country);
                } else {
                    // Jika tidak bisa parse (misal format tidak standar), 
                    // biarkan nilai apa adanya di input
                    // dan set prefix dari yang ada di template
                    if ($prefix.length && $prefix.text()) {
                        $input.data('caPhoneMaskSymbol', $prefix.text());
                    }
                }
            } else {
                // Jika kosong, set prefix default dari template
                if ($prefix.length && $prefix.text()) {
                    $input.data('caPhoneMaskSymbol', $prefix.text());
                }
                // Kosongkan input
                $input.val('');
            }
            // =========================================================

            // Hapus placeholder
            $input.attr('placeholder', '');
            
            // Validasi awal (tidak akan mengosongkan input karena sudah ada js-mask-phone-inited)
            validatePhone($input);
        });

        registerValidatorPhoneMask();
    });

    // ========== EVENT DROPDOWN ==========
    $(document).ready(function () {
        if (_.phone_validation_mode !== 'phone_number_with_country_selection') {
            return;
        }

        $(_.doc).on('click', '.cm-phone-number-with-country-selection-li-link, ' +
            '.cm-phone-number-with-country-selection-li-link .cs-icon, ' +
            '.cm-phone-number-with-country-selection-li-link .ty-icon', function (e) {
            
            e.preventDefault();
            e.stopPropagation();

            var $listItemLink = $(this);
            var $input = $('.cm-mask-phone', $listItemLink.closest('.cm-mask-phone-group'));
            var country = $listItemLink.data('caName') ? $listItemLink.data('caName') : $listItemLink.attr('name');
            var symbol = $listItemLink.data('caListItemSymbol');

            if ($input.length && country && symbol) {
                var $prefix = getPrefixElement($input);
                if ($prefix.length) {
                    $prefix.text(symbol);
                }

                $input.data('caPhoneMaskCountry', country);
                $input.data('caPhoneMaskSymbol', symbol);
                $input.attr('placeholder', '');
                updateFlag($input, country);

                // Tutup dropdown
                var $toggleBtn = $listItemLink.closest('.ty-btn-group').find('[data-ca-toggle="dropdown"]');
                if ($toggleBtn.length && $toggleBtn.hasClass('open')) {
                    $toggleBtn.trigger('click');
                }
                
                var $container = $listItemLink.closest('.ty-select-block, .ty-btn-group, .dropdown, .open');
                if ($container.length) {
                    $container.removeClass('open');
                    $container.find('.open').removeClass('open');
                }
                
                $listItemLink.parents().each(function() {
                    if ($(this).hasClass('open')) {
                        $(this).removeClass('open');
                    }
                });
                
                var $dropdownBlock = $listItemLink.closest('.ty-select-block');
                if ($dropdownBlock.length && !$dropdownBlock.hasClass('hidden')) {
                    $dropdownBlock.addClass('hidden');
                }

                // Focus ke input
                setTimeout(function() {
                    $input.focus();
                    $input.select();
                }, 150);
            }

            return false;
        });
    });

    // ========== FUNGSI INTI ==========
    function phoneNumberWithCountrySelectionInit($input) {
        if (!$input.length || $input.closest('.cm-mask-phone-group').length) {
            return;
        }
        var inputId = $input.attr('id');
        var $labelField = $('label[for="' + inputId + '"]');
        if (!$labelField.length) {
            return;
        }
        $labelField.parent().addClass('cm-mask-phone-group').attr('data-ca-phone-mask-group-id', inputId);
        $input.data('caCheckFilter', '[data-ca-phone-mask-group-id="' + inputId + '"]');
    }

    function bindEvents($input) {
        $input.on('input blur', function (e) {
            var tempData = undefined;
            var isShowValidationErrors = true;
            if (e.type === 'input') {
                tempData = { 'caSkipTooShort': true };
            } else {
                isShowValidationErrors = !($(this).prop('defaultValue') === '' && $(this).val() === '');
            }
            checkFieldWithoutScroll($(this), true, tempData, isShowValidationErrors);
        });
    }

    function validatePhone($input) {
        if (!$input.length) {
            return false;
        }
        var input = $input[0];
        if ($.is.blank($input.val()) || !$input.hasClass('js-mask-phone-inited')) {
            input.setCustomValidity('');
            return true;
        }

        var mask_is_valid = false;
        if (_.phone_validation_mode === 'phone_number_with_country_selection') {
            var prefix = $input.data('caPhoneMaskSymbol') || '';
            var number = $input.val() || '';
            var fullNumber = prefix + number;

            if (!fullNumber || fullNumber === prefix) {
                input.setCustomValidity('');
                return true;
            }

            try {
                mask_is_valid = libphonenumber.isValidPhoneNumber(fullNumber);
                var errorCode = libphonenumber.validatePhoneNumberLength(fullNumber);

                if ($input.data('caSkipTooShort') && (errorCode === 'TOO_SHORT' || fullNumber === '+')) {
                    mask_is_valid = true;
                    input.setCustomValidity('');
                } else if (mask_is_valid) {
                    input.setCustomValidity('');
                    var phoneNumber = libphonenumber.parsePhoneNumber(fullNumber);
                    if (!phoneNumber) {
                        mask_is_valid = false;
                    }
                } else {
                    input.setCustomValidity(_.tr('error_validator_phone_phone_number_with_country_selection'));
                }
            } catch (e) {
                input.setCustomValidity(_.tr('error_validator_phone_phone_number_with_country_selection'));
                mask_is_valid = false;
            }
        }
        return mask_is_valid;
    }

    function registerValidatorPhoneMask() {
        $.ceFormValidator('registerValidator', {
            class_name: 'cm-mask-phone-label',
            message: _.tr('error_validator_phone_mask'),
            func: function (id) {
                return validatePhone($('#' + id));
            }
        });
    }

    function checkFieldWithoutScroll($input, isFieldClickedElm, tempData, isShowValidationErrors) {
        var $form = $input.closest('form');
        var $fieldContainer = $input.closest('.cm-field-container');
        var isUndefinedNoScroll = typeof $input.data('caNoScroll') === 'undefined';
        isShowValidationErrors = typeof isShowValidationErrors === 'undefined' ? true : isShowValidationErrors;

        if (isUndefinedNoScroll) {
            $input.data('caNoScroll', true);
        }
        if (isUndefinedNoScroll && $fieldContainer.length) {
            $fieldContainer.data('caNoScroll', true);
        }

        if (isFieldClickedElm) {
            $form.ceFormValidator('setClicked', $input);
        } else {
            if (!$('[type=submit]', $form).length && !$('input[type=image]', $form).length) {
                var $submit = $('.cm-submit', $form).length ? $('.cm-submit:first', $form) : $input;
                $form.ceFormValidator('setClicked', $submit);
            }
        }

        if (tempData) {
            $input.data(tempData);
        }
        $form.ceFormValidator('check', true, null, isShowValidationErrors);
        if (tempData) {
            $input.removeData(Object.keys(tempData));
        }
        if (isUndefinedNoScroll) {
            $input.removeData('caNoScroll');
            if ($fieldContainer.length) {
                $fieldContainer.removeData('caNoScroll');
            }
        }
    }

    // ========== EVENT SUBMIT ==========
    $(document).on('submit', 'form', function () {
        $(this).find('.cm-mask-phone').each(function () {
            var $input = $(this);
            var prefix = $input.data('caPhoneMaskSymbol') || '';
            var number = $input.val() || '';
            $input.val(prefix + number);
        });
    });

})(Tygh, Tygh.$);