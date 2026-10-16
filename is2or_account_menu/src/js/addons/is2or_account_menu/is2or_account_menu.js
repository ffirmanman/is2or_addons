(function (_, $) {

  function extractChatPanelHtml(html) {
    var $parsed = $('<div>').html(html);
    var $fragment = $parsed.find('#chat_panel_container');
    return $fragment.length ? $fragment.html() : html;
  }

  function scrollMessagesToBottom($panel) {
    var $wrapper = $panel.find('.ty-vendor-communication-post__wrapper');
    if ($wrapper.length) {
      $wrapper.scrollTop($wrapper[0].scrollHeight);
    }
  }

  function loadThreadInto(url, $panel) {
    $panel.html('<div class="ty-vc-chat-panel__empty">Loading...</div>');

    $.ajax({
      url: url,
      type: 'GET',
      dataType: 'html',
      cache: false
    }).done(function (html) {
      $panel.html(extractChatPanelHtml(html));

      if ($.ceEvent) {
        $.ceEvent('trigger', 'ce.commoninit', $panel);
      }

      scrollMessagesToBottom($panel);
    }).fail(function () {
      $panel.html('<div class="ty-vc-chat-panel__empty">Failed load messages, try again.</div>');
    });
  }

  function submitMessageForm($form) {
    var $panel = $form.closest('#chat_panel_container');
    if (!$panel.length) {
      $panel = $('#chat_panel_container');
    }

    var url = $form.attr('action');

    // Buang result_ids supaya backend merender fragment view.tpl penuh
    // (sama seperti saat load thread via GET), bukan JSON partial.
    var formData = $form.serializeArray().filter(function (field) {
      return field.name !== 'result_ids';
    });

    // Sertakan nama tombol submit ("Send") secara manual, karena
    // serializeArray() tidak menyertakan tombol yang diklik.
    var $sendBtn = $form.find('.cm-post');
    if ($sendBtn.length && $sendBtn.attr('name')) {
      formData.push({ name: $sendBtn.attr('name'), value: $sendBtn.val() || '' });
    }

    var $textarea = $form.find('textarea[name="message[message]"]');
    if (!$.trim($textarea.val())) {
      return;
    }

    $sendBtn.prop('disabled', true);

    $.ajax({
      url: url,
      type: 'POST',
      data: $.param(formData),
      dataType: 'html',
      cache: false
    }).done(function (html) {
      $panel.html(extractChatPanelHtml(html));

      if ($.ceEvent) {
        $.ceEvent('trigger', 'ce.commoninit', $panel);
      }

      scrollMessagesToBottom($panel);
    }).fail(function () {
      alert('Gagal mengirim pesan, silakan coba lagi.');
      $sendBtn.prop('disabled', false);
    });
  }

  $(document).ready(function () {
    var $panel = $('#chat_panel_container');
    var activeThreadId = $panel.data('active-thread');

    if (activeThreadId) {
      var $activeLink = $('.ty-vc-thread-open[data-ca-thread-id="' + activeThreadId + '"]');
      if ($activeLink.length) {
        $activeLink.closest('.ty-vc-thread-item').addClass('active');
        loadThreadInto($activeLink.attr('href'), $panel);
      }
    }

    $(document).on('click', '.ty-vc-thread-open', function (e) {
      e.preventDefault();

      $('.ty-vc-thread-item').removeClass('active');
      $(this).closest('.ty-vc-thread-item').addClass('active');

      loadThreadInto($(this).attr('href'), $('#chat_panel_container'));
    });

    // Kirim pesan tanpa reload halaman.
    $(document).on('submit', '#chat_panel_container form.ty-vendor-communication-new-message', function (e) {
      e.preventDefault();
      submitMessageForm($(this));
    });
  });
})(Tygh, Tygh.$);