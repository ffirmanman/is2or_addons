(function (_, $) {
  $(document).on('click', '.js-address-selector-select', function (event) {
    event.preventDefault();

    var $button = $(this);
    if ($button.prop('disabled')) {
      return;
    }

    $.ceAjax('request', $button.data('caAddressSelectorUrl'), {
      method: 'post',
      hidden: true,
      data: {
        profile_id: $button.data('caAddressSelectorProfileId'),
        is_checkout_page: Number($button.data('caAddressSelectorIsCheckoutPage'))
      },
      callback: function (response) {
        if (!response || !response.success) {
          if (response && response.edit_url) {
            window.location.href = response.edit_url;
          }
          return;
        }

        $('[data-ca-geo-map-location-element="location"]').text(response.city || '');
        $('[data-ca-geo-map-location-element="location_block"]').each(function () {
          $(this).data('caGeoMapLocationIsLocationDetected', true);
        });

        if (response.reload_checkout) {
          window.location.reload();
          return;
        }

        var $grid = $button.closest('.is2or-address-selector__grid');
        var activeLabel = $grid.data('caAddressSelectorActiveLabel');

        $grid.find('.is2or-address-selector__card').each(function () {
          var $card = $(this);
          var isTargetCard = String($card.data('caAddressSelectorCardProfileId')) === String(response.active_profile_id);

          $card.toggleClass('is-active', isTargetCard);

          $card.find('.is2or-address-selector__badge--active').remove();

          if (isTargetCard) {
            $card.find('.is2or-address-selector__badges').append(
              $('<span/>', {
                'class': 'is2or-address-selector__badge is2or-address-selector__badge--active',
                text: activeLabel
              })
            );
          }

          var $selectBtn = $card.find('.js-address-selector-select');
          if ($selectBtn.length) {
            var isIncomplete = $card.hasClass('is-incomplete');
            $selectBtn.prop('disabled', isTargetCard || isIncomplete);
          }
        });
      }
    });
  });
})(Tygh, Tygh.$);