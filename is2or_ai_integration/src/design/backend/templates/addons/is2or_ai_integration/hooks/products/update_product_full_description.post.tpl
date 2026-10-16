{if $smarty.request.dispatch == "products.update"}
<div class="control-group is2or-ai-generate-details-wrapper">
    <div class="controls">
        <button type="button" 
                class="btn btn-default is2or-ai-generate-details-btn cm-tooltip" 
                title="{__("is2or_ai_integration.generate_product_features_tooltip")}">
            <span class="is2or-ai-generate-details-btn-label">
                {__("is2or_ai_integration.generate_product_features")}
            </span>
        </button>
        <span class="is2or-ai-generate-details-status" style="margin-{if $lang_code == "ar"}right{else}left{/if}:10px; vertical-align:middle;"></span>
    </div>
</div>

<script type="text/javascript">
(function ($) {
    "use strict";

    var is2orAiIntegrationDetails = {
        selectors: {
            nameField: '[name="product_data[product]"]',
            descriptionField: '[name="product_data[full_description]"]',
            productIdField: 'input[name="product_id"]',
            csrfField: 'input[name="security_hash"]',
            brandField: '[name="product_data[brand]"]',
            featureSelect: 'select.cm-object-picker[data-ca-object-picker-object-type="product_feature_variants"]'
        },
        currentLang: "{$lang_code|default:'en'}",
        i18n: {
            en: {
                loading: 'Generating\u2026',
                nameRequired: 'Please enter a product name first.',
                genericError: 'Could not generate AI content. Please try again.'
            },
            ar: {
                loading: '\u062c\u0627\u0631\u064d \u0627\u0644\u0625\u0646\u0634\u0627\u0621\u2026',
                nameRequired: '\u064a\u0631\u062c\u0649 \u0625\u062f\u062e\u0627\u0644 \u0627\u0633\u0645 \u0627\u0644\u0645\u0646\u062a\u062c \u0623\u0648\u0644\u0627\u064b.',
                genericError: '\u062a\u0639\u0630\u0631 \u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0645\u062d\u062a\u0648\u0649 \u0628\u0648\u0627\u0633\u0637\u0629 \u0627\u0644\u0630\u0643\u0627\u0621 \u0627\u0644\u0627\u0635\u0637\u0646\u0627\u0639\u064a. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u062d\u0627\u0648\u0644\u0629 \u0645\u0631\u0629 \u0623\u062e\u0631\u0649.'
            }
        },

        lang: function () {
            return (this.currentLang || 'en').substr(0, 2) === 'ar' ? 'ar' : 'en';
        },

        t: function (key) {
            return this.i18n[this.lang()][key] || this.i18n.en[key];
        },

        setStatus: function ($btn, text, isError) {
            var $status = $btn.closest('.is2or-ai-generate-details-wrapper').find('.is2or-ai-generate-details-status');
            $status.text(text || '');
            $status.css('color', isError ? '#c0392b' : '#2e7d32');
        },

        generateDetails: function ($btn) {
            var self = this;
            var $name = $(this.selectors.nameField).first();
            var productName = $.trim($name.val());

            if (productName === '') {
                this.setStatus($btn, this.t('nameRequired'), true);
                $name.trigger('focus');
                return;
            }

            var $productId = $(this.selectors.productIdField).first();
            var productId = $productId.length ? $productId.val() : '';

            var $brand = $(this.selectors.brandField).first();
            var brand = $brand.length ? $.trim($brand.val()) : '';

            var $description = $(this.selectors.descriptionField).first();
            var description = '';
            var descId = $description.attr('id');
            if (descId && window.tinyMCE && tinyMCE.get && tinyMCE.get(descId)) {
                description = tinyMCE.get(descId).getContent();
            } else if ($description.length) {
                description = $description.val();
            }
            description = $('<div>').html(description).text();

            var $csrf = $(this.selectors.csrfField).first();
            var csrf = $csrf.length ? $csrf.val() : ((window.Tygh && Tygh.csrf_token) ? Tygh.csrf_token : '');

            var $label = $btn.find('.is2or-ai-generate-details-btn-label');
            var originalLabel = $label.text();
            $btn.prop('disabled', true);
            $label.text(this.t('loading'));
            this.setStatus($btn, '');

            $.ajax({
                url: "{"is2or_ai_integration.generate_details"|fn_url}",
                type: 'POST',
                dataType: 'json',
                data: {
                    product_name: productName,
                    product_id: productId,
                    brand: brand,
                    description: description,
                    notes: '',
                    security_hash: csrf
                }
            }).done(function (response) {
                if (!response || !response.success || !response.output) {
                    self.setStatus($btn, response && response.error ? response.error : self.t('genericError'), true);
                    return;
                }

                var attributes = response.output.attributes || [];

                if (!attributes.length) {
                    self.setStatus($btn, self.lang() === 'ar' ? 'لم يتم العثور على خصائص.' : 'No attributes found.', true);
                    return;
                }

                self.switchToFeaturesTab();
                self.applyAttributesToFeatures(attributes);

                self.setStatus($btn, self.lang() === 'ar' ? 'تم تطبيق الخصائص.' : 'Attributes applied.', false);
            }).fail(function () {
                self.setStatus($btn, self.t('genericError'), true);
            }).always(function () {
                $btn.prop('disabled', false);
                $label.text(originalLabel);
            });
        },

        switchToFeaturesTab: function () {
            var $tabLink = $('li#features > a').first();

            if ($tabLink.length) {
                $tabLink.trigger('click');
                return;
            }

            // fallback selector jika struktur tab berbeda
            $('a[href="#content_features"], a[href="#features"]').first().trigger('click');
        },

        normalize: function (str) {
            return $.trim(String(str || ''))
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim();
        },

        applyAttributesToFeatures: function (attributes) {
            var self = this;

            $.each(attributes, function (i, attr) {
                if (!attr || !attr.name || attr.value === undefined || attr.value === null || attr.value === '') {
                    return;
                }

                var attrNameNorm = self.normalize(attr.name);
                var attrValueNorm = $.trim(String(attr.value)).toLowerCase();

                $(self.selectors.featureSelect).each(function () {
                    var $select = $(this);
                    var $controlGroup = $select.closest('.control-group');
                    var labelText = $.trim($controlGroup.find('.control-label a').first().text());
                    var labelNorm = self.normalize(labelText);

                    if (labelNorm === '') {
                        return;
                    }

                    var isMatch = labelNorm.indexOf(attrNameNorm) !== -1 || attrNameNorm.indexOf(labelNorm) !== -1;
                    if (!isMatch) {
                        return;
                    }

                    var matchedOption = self.findMatchingOption($select, attrValueNorm);
                    if (matchedOption) {
                        self.setFeatureSelectValue($select, matchedOption);
                    }
                });
            });
        },

        findMatchingOption: function ($select, valueNorm) {
            var matched = null;

            $select.find('option').each(function () {
                var $opt = $(this);
                var val = $opt.val();
                if (!val) {
                    return;
                }

                var optName = '';
                var dataAttr = $opt.attr('data-data');

                if (dataAttr) {
                    try {
                        var parsed = JSON.parse(dataAttr);
                        optName = (parsed && parsed.data && parsed.data.name) ? parsed.data.name : '';
                    } catch (e) {
                        optName = $opt.text();
                    }
                } else {
                    optName = $opt.text();
                }

                if ($.trim(optName).toLowerCase() === valueNorm) {
                    matched = { id: String(val), text: optName };
                    return false; // break each
                }
            });

            return matched;
        },

        setFeatureSelectValue: function ($select, option) {
            var picker = $select.data('caObjectPicker');

            if (picker && typeof picker.addObjects === 'function') {
                picker.addObjects([option], true, true);
                return;
            }

            if ($select.hasClass('select2-hidden-accessible') && $.fn.select2) {
                var newOption = new Option(option.text, option.id, true, true);
                $select.append(newOption).trigger('change');
                return;
            }

            $select.val(option.id).trigger('change');
        }
    };

    $(document).on('click', '.is2or-ai-generate-details-btn', function (e) {
        e.preventDefault();
        is2orAiIntegrationDetails.generateDetails($(this));
    });

})(jQuery);
</script>

{/if}