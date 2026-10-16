(function (_, $) {
  // var $productID = 0;

  // $(document).ready(function() {
  //   $productID = _.product_id || 0
  // });

  var prevAction;
  var prevPath;
  var results = [];

  function toggleSourceButtons() {
    const sourcePath = $('#is2or_ie_upload_path').val();

    if (sourcePath) {
      $('#is2or_ie_upload_image').addClass('hidden');
      $('#is2or_ie_source_actions').removeClass('hidden');
    } else {
      $('#is2or_ie_upload_image').removeClass('hidden');
      $('#is2or_ie_source_actions').addClass('hidden');
    }
  }

  function validateImage() {
    const sourcePath = $('#is2or_ie_upload_path').val();

    if (!sourcePath) {
      $.ceNotification('show', {
        title: _.tr('warning'),
        message: _.tr('is2or_claid_ai.source_image_empty'),
        type: 'W'
      })
    }
    
    return sourcePath ? true : false;
  }

  function validateCredit(action = 'remove_background') {
    const availableCredit = _.is2or_claid_ai.company_credits;
    const cost = _.is2or_claid_ai.credit_costs[action];

    if (availableCredit < cost) {
      $.ceNotification('show', {
        title: _.tr('error'),
        message: _.tr('is2or_claid_ai.insufficient_credit'),
        type: 'E'
      })
    }

    return availableCredit >= cost;
  }

  function validateForm(action = 'remove_background') {
    return validateImage() && validateCredit();
  }

  function processImage(action, path, prompt = null) {
    $.ceAjax('request', fn_url('is2or_claid_ai.process_image'), {
      method: 'post',
      data: {
        action,
        path,
        prompt
      },
      callback: function (response) {
        const image = response.image;

        if (image && image.url && image.path) {
          results.push(image);
          $("#is2or_ie_result__wrapper").show();
          $('#is2or_ie_result_preview').attr('src', image.url);
          $('#is2or_ie_result_path').val(image.path)

          addPastResult(image.url, image.path);

          if (image.company_credits) {
            _.is2or_claid_ai.company_credits = image.company_credits
            $('#is2or_credit_value').html(_.tr('is2or_claid_ai.remaining_credits').replace('[credit]', _.is2or_claid_ai.company_credits));
          }
        }
      }
    })
  }

  function previewImage(url, path) {
    $('#is2or_ie_result_preview').attr('src', url);
    $('#is2or_ie_result_path').val(path);

    $('#is2or_ie_result__wrapper').show();
  }

  function addPastResult(url, path) {
    const container = $('#is2or_ie_past_results .is2or-ie__images');

    // Create new wrapper HTML
    const newWrapper = $(`
      <div class="is2or-ie__img-wrapper">
        <img src="${url}" data-is2or-path="${path}">
        <div class="is2or-ie__image-actions is2or-ie__past-result">
          <div>
            <a href="javascript:void(0)" class="cm-tooltip is2or-ie__img-preview" title="Preview">
              <i class="icon icon-eye-open"></i>
            </a>
          </div>
        </div>
      </div>
    `);

    // Add to the top (prepend)
    container.prepend(newWrapper);

    // If more than 4 wrappers, remove the LAST one
    const wrappers = container.find('.is2or-ie__img-wrapper');
    if (wrappers.length > 4) {
      wrappers.last().remove();
    }

    // Show the parent div if hidden
    $('#is2or_ie_past_results').show();
  }

  // Events
  $(document).on('click', '#is2or_ie_upload_image', function (e) {
    e.preventDefault();
    $('#is2or_ie_upload_image_input').click();
  });

  $(document).on('change', "#is2or_ie_upload_image_input", function(e) {
    var file = this.files[0];
    if (!file) return;

    var formData = new FormData();
    formData.append('security_hash', _.security_hash);
    formData.append('image', file);

    $.toggleStatusBox('show');
    $.ajax({
      url: fn_url('is2or_claid_ai.upload'),
      type: 'post',
      data: formData,
      processData: false,
      contentType: false,
      cache: true,
      success: function (response) {
        $.toggleStatusBox('hide');


        data = $.parseJSON(response);
        if (data.url && data.path) {
          $('#is2or_ie_upload_path').val(data.path)
          $('#is2or_ie_source_preview').attr('src', data.url);

          toggleSourceButtons();
        }

        // reset file input
        $('#is2or_ie_upload_image_input').val('');
      }
    })
  });

  $(document).on('click', '.is2or-ie__img-preview', function(e) {
    e.stopPropagation();

    const wrapper = $(this).closest('.is2or-ie__img-wrapper');

    if (wrapper) {
      const img = wrapper.find('img');
      const url = img ? img.attr('src') : false;
  
      if (url) window.open(url, '_blank', 'noopener,noreferrer');
    }
  });

  $(document).on('click', '#is2or_ie_source_remove', function() {
    if (confirm(_.tr('text_are_you_sure_to_proceed'))) {
      const imagePath = $('#is2or_ie_upload_path').val();

      if (imagePath.includes('tmp')) {
        $.ceAjax('request', fn_url('is2or_claid_ai.remove_tmp_image'), {
          method: 'post',
          data: {
            path: imagePath
          }
        });
      }

      $('#is2or_ie_source_preview').attr('src', _.is2or_claid_ai.default_image);
      $('#is2or_ie_upload_path').val('');
      toggleSourceButtons();
    }
  });

  $(document).on('click', '#is2or_ie_remove_background', function() {
    const action = 'remove_background';
    if (!validateForm(action)) return;

    prevAction = action;
    prevPath = $('#is2or_ie_upload_path').val();
    
    processImage(action, prevPath);
  });

  $(document).on('click', '#is2or_ie_enhance_image', function() {
    const action = 'enhance_image';
    if (!validateForm(action)) return;

    prevAction = action;
    prevPath = $('#is2or_ie_upload_path').val();
    
    processImage(action, prevPath);
  });

  $(document).on('click', '#is2or_ie_generate_background', function() {
    $('#is2or_ie_prompt__wrapper').show();
  });

  $(document).on('click', '#is2or_ie_generate_background_generate', function() {
    const prompt = $('#is2or_ie_prompt').val();

    if (!prompt) {
      return $.ceNotification('show', {
        title: _.tr('warning'),
        message: _.tr('is2or_claid_ai.prompt_empty'),
        type: 'W'
      })
    }

    const action = 'generate_background';
    if (!validateForm(action)) return;

    prevAction = action;
    prevPath = $('#is2or_ie_upload_path').val();
    
    processImage(action, prevPath, prompt);
  });

  $(document).on('click', '.is2or-ie__past-result', function() {
    const wrapper = $(this).closest('.is2or-ie__img-wrapper');

    if (wrapper) {
      const img = wrapper.find('img');

      if (!img) return;
      const url = img.attr('src');
      const path = img.data('is2orPath');

      
      if (url && path) previewImage(url, path);
    }
  });

  $(document).on('click', '#is2or_ie_download_result', function() {
    const url = $('#is2or_ie_result_preview').attr('src');
    
    if (url) {
      const filename = url.split('/').pop().split('?')[0] || 'image.png';
      const a = $('<a>')
        .attr('href', url)
        .attr('download', filename)
        .appendTo('body');

      a[0].click();
      a.remove();
    };
  });

  $(document).on('click', '#is2or_ie_use_result', function() {
    const url = $('#is2or_ie_result_preview').attr('src');
    const path = $('#is2or_ie_result_path').val();

    if (url && path) {
      $('#is2or_ie_source_preview').attr('src', url);
      $('#is2or_ie_upload_path').val(path);
      toggleSourceButtons();
    }
  });

  $(document).on('click', '#is2or_ie_add_result', function() {
    if (confirm(_.tr('text_are_you_sure_to_proceed'))) {
      const path = $('#is2or_ie_result_path').val();
      const product_id = _.is2or_claid_ai.product_id || 0;

      if (path && product_id) {
        $.ceAjax('request', fn_url('is2or_claid_ai.add_product_image'), {
          method: 'post',
          data: {
            path,
            product_id
          },callback: function(response) {
              $.ceAjax('request', fn_url('products.update?product_id=' + product_id), {
                result_ids: 'content_detailed',
              });
            }
        });
      }
    }
  });

})(Tygh, Tygh.$);