<div class="control-group is2or-ai-generate-wrapper">
    <div class="controls">
        <button type="button" class="btn btn-default is2or-ai-generate-btn">
            <span class="is2or-ai-generate-btn-label">
                {__("is2or_ai_integration.generate_ai_product_detail")}
            </span>
        </button>
        <span class="is2or-ai-generate-status" style="margin-{if $lang_code == "ar"}right{else}left{/if}:10px; vertical-align:middle;"></span>
    </div>
</div>

<script type="text/javascript">
(function ($) {
    "use strict";

    var is2orAiIntegration = {
        selectors: {
            nameField: '[name="product_data[product]"]',
            descriptionField: '[name="product_data[full_description]"]',
            categoryField: 'select[name="product_data[category_ids][]"]',
            productIdField: 'input[name="product_id"]',
            csrfField: 'input[name="security_hash"]'
        },
        currentLang: "{$lang_code|default:'en'}",
        ajaxUrl: "{"is2or_ai_integration.generate_listing"|fn_url}",
        i18n: {
            en: {
                loading: 'Generating\u2026',
                success: 'Description generated. Please review before saving.',
                nameRequired: 'Please enter a product name first.',
                genericError: 'Could not generate AI content. Please try again.'
            },
            ar: {
                loading: '\u062c\u0627\u0631\u064d \u0627\u0644\u0625\u0646\u0634\u0627\u0621\u2026',
                success: '\u062a\u0645 \u0625\u0646\u0634\u0627\u0621 \u0627\u0644\u0648\u0635\u0641. \u064a\u0631\u062c\u0649 \u0627\u0644\u0645\u0631\u0627\u062c\u0639\u0629 \u0642\u0628\u0644 \u0627\u0644\u062d\u0641\u0638.',
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
            var $status = $btn.closest('.is2or-ai-generate-wrapper').find('.is2or-ai-generate-status');
            $status.text(text || '');
            $status.css('color', isError ? '#c0392b' : '#2e7d32');
        },

        generate: function ($btn) {
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

            var $csrf = $(this.selectors.csrfField).first();
            var csrf = $csrf.length ? $csrf.val() : ((window.Tygh && Tygh.csrf_token) ? Tygh.csrf_token : '');

            var $label = $btn.find('.is2or-ai-generate-btn-label');
            var originalLabel = $label.text();
            $btn.prop('disabled', true);
            $label.text(this.t('loading'));
            this.setStatus($btn, '');
            
            $.ajax({
                    url: this.ajaxUrl,
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        product_name: productName,
                        product_id: productId,
                        security_hash: csrf
                    }
                }).done(function (response) {
                    if (!response || !response.success || !response.output) {
                        self.setStatus($btn, (response && response.error) ? response.error : self.t('genericError'), true);
                        return;
                    }
                    self.applyOutput(response.output);
                    self.setStatus($btn, self.t('success'), false);

                    // teruskan csrf ke sini
                    self.generateCategories(productName, response.output, productId, csrf);
                }).fail(function () {
                    self.setStatus($btn, self.t('genericError'), true);
                }).always(function () {
                    $btn.prop('disabled', false);
                    $label.text(originalLabel);
                });
        },

        applyOutput: function (output) {
            var lang = this.lang();
            var description = output['description_' + lang] || output.description_en || output.description_ar || '';

            if (description === '') {
                return;
            }

            this.setFieldContent(this.selectors.descriptionField, description);
        },
        generateCategories: function (productName, output, productId, csrf) {
            var self = this;
            var description = output.description_en || output.description_ar || '';

            $.ajax({
                url: '{"is2or_ai_integration.generate_categorize"|fn_url}',
                type: 'POST',
                dataType: 'json',
                data: {
                    product_name: productName,
                    description: $('<div>').html(description).text(),
                    product_id: productId,
                    security_hash: csrf
                }
            }).done(function (response) {
                if (response && response.success && response.categories) {
                    self.applyCategories(response.categories);
                } else if (window.console) {
                    console.warn('is2or categorize failed:', response && response.error);
                }
            }).fail(function (jqXHR) {
                if (window.console) {
                    console.warn('is2or categorize request failed:', jqXHR.status, jqXHR.responseText);
                }
            });
        },
        applyCategories: function (categories) {
            if (!categories || !categories.category_id) {
                return;
            }

            var $select = $(this.selectors.categoryField).first();
            if ($select.length === 0) {
                return;
            }

            var picker = $select.data('caObjectPicker');
            if (!picker || typeof picker.addObjects !== 'function') {
                if (window.console) {
                    console.warn('is2or: caObjectPicker instance not found on category field');
                }
                return;
            }

            var suggestions = [];
            if (categories.category_id) {
                suggestions.push({ id: String(categories.category_id), text: categories.category_name || '' });
            }
            if ($.isArray(categories.alternatives)) {
                $.each(categories.alternatives, function (i, alt) {
                    if (alt && alt.category_id) {
                        suggestions.push({ id: String(alt.category_id), text: alt.category_name || '' });
                    }
                });
            }
            if (!suggestions.length) {
                return;
            }

            // dedupe by id
            var seen = {};
            suggestions = suggestions.filter(function (item) {
                if (seen[item.id]) { return false; }
                seen[item.id] = true;
                return true;
            });

            picker.addObjects(suggestions, true, true);
        },
        setFieldContent: function (selector, html) {
            var $field = $(selector).first();
            if ($field.length === 0) {
                return;
            }
            var id = $field.attr('id');

            if (id && window.tinyMCE && tinyMCE.get && tinyMCE.get(id)) {
                tinyMCE.get(id).setContent(html);
                return;
            }

            $field.val(html).trigger('change');
        }
    };

    $(document).on('click', '.is2or-ai-generate-btn', function (e) {
        e.preventDefault();
        is2orAiIntegration.generate($(this));
    });

})(jQuery);
</script>
