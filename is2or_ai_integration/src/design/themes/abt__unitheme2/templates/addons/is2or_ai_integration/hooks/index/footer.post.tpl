{if $auth.user_id}
<div id="is2or-chat-widget" class="is2or-chat-widget">
    <button type="button" id="is2or-chat-toggle" class="is2or-chat-toggle" aria-label="{__("is2or_ai_integration.chat_open")}">
        <svg width="26" height="26" viewBox="0 0 24 24" fill="none"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
    </button>

    <div id="is2or-chat-panel" class="is2or-chat-panel" style="display:none;">
        <div class="is2or-chat-header">
            <span>{__("is2or_ai_integration.chat_title")}</span>
            <button type="button" id="is2or-chat-close" class="is2or-chat-close">&times;</button>
        </div>
        <div id="is2or-chat-messages" class="is2or-chat-messages"></div>
        <div id="is2or-chat-chips" class="is2or-chat-chips"></div>
        <div class="is2or-chat-input-row">
            <input type="text" id="is2or-chat-input" class="is2or-chat-input" placeholder="{__("is2or_ai_integration.chat_placeholder")}" />
            <button type="button" id="is2or-chat-send" class="is2or-chat-send">{__("is2or_ai_integration.chat_send")}</button>
        </div>
    </div>
</div>

<style>
.is2or-chat-widget { position: fixed; right: 20px; bottom: 20px; z-index: 9999; font-family: inherit; }
.is2or-chat-toggle { width: 56px; height: 56px; border-radius: 50%; background: #f57c00; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,.25); display: flex; align-items: center; justify-content: center; }
.is2or-chat-panel { position: fixed; right: 20px; bottom: 90px; width: 340px; max-width: calc(100vw - 40px); height: 460px; max-height: 70vh; background: #fff; border-radius: 10px; box-shadow: 0 4px 20px rgba(0,0,0,.2); display: flex; flex-direction: column; overflow: hidden; }
.is2or-chat-header { background: #f57c00; color: #fff; padding: 12px 14px; display: flex; justify-content: space-between; align-items: center; font-weight: 600; }
.is2or-chat-close { background: none; border: none; color: #fff; font-size: 20px; cursor: pointer; line-height: 1; }
.is2or-chat-messages { flex: 1; overflow-y: auto; padding: 12px; background: #f7f7f7; }
.is2or-chat-msg { margin-bottom: 10px; max-width: 85%; padding: 8px 12px; border-radius: 10px; font-size: 13px; line-height: 1.4; }
.is2or-chat-msg.user { margin-left: auto; background: #f57c00; color: #fff; }
.is2or-chat-msg.bot { margin-right: auto; background: #eee; color: #222; }
.is2or-chat-products-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; margin-top: 6px; margin-bottom: 10px; }
.is2or-chat-product-card { display: flex; flex-direction: column; background: #fff; border: 1px solid #e6e6e6; border-radius: 8px; overflow: hidden; text-decoration: none; color: inherit; transition: box-shadow .15s ease, transform .15s ease; }
.is2or-chat-product-card:hover { box-shadow: 0 3px 10px rgba(0,0,0,.12); transform: translateY(-1px); }
.is2or-chat-product-img { position: relative; width: 100%; aspect-ratio: 1 / 1; background: #f2f2f2; overflow: hidden; }
.is2or-chat-product-img img { position: absolute; top: 0; left: 0; width: 100% !important; height: 100% !important; max-width: none !important; object-fit: cover !important; display: block; }
.is2or-chat-product-noimg { width: 100%; height: 100%; }
.is2or-chat-product-info { padding: 4px 5px 6px; }
.is2or-chat-product-info .name { font-size: 10px; font-weight: 600; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; color: #222; margin-bottom: 2px; }
.is2or-chat-product-info .price { font-size: 10.5px; font-weight: 700; color: #f57c00; }
.is2or-chat-product-card img { width: 44px; height: 44px; object-fit: cover; border-radius: 6px; flex-shrink: 0; }
.is2or-chat-product-card .name { font-size: 12px; font-weight: 600; }
.is2or-chat-product-card .price { font-size: 12px; color: #f57c00; }
.is2or-chat-chips { padding: 0 12px 8px; display: flex; flex-wrap: wrap; gap: 6px; }
.is2or-chat-chip { background: #fff3e0; border: 1px solid #f57c00; color: #f57c00; border-radius: 14px; padding: 4px 10px; font-size: 12px; cursor: pointer; }
.is2or-chat-input-row { display: flex; border-top: 1px solid #e0e0e0; padding: 8px; gap: 6px; }
.is2or-chat-input { flex: 1; border: 1px solid #ddd; border-radius: 6px; padding: 6px 8px; font-size: 13px; }
.is2or-chat-send { background: #f57c00; color: #fff; border: none; border-radius: 6px; padding: 6px 12px; font-size: 13px; cursor: pointer; }
</style>

<script type="text/javascript">
(function ($) {
    "use strict";

    var is2orChat = {
        sessionKey: 'is2or_chat_session_id',
        ajaxUrl: "{"is2or_ai_integration.chat"|fn_url}",
        locale: "{$smarty.const.CART_LANGUAGE|default:'en'}",

        getSessionId: function () {
            try { return sessionStorage.getItem(this.sessionKey) || ''; } catch (e) { return ''; }
        },
        setSessionId: function (id) {
            try { sessionStorage.setItem(this.sessionKey, id || ''); } catch (e) {}
        },

        appendMessage: function (text, cls) {
            var $msg = $('<div class="is2or-chat-msg"></div>').addClass(cls).text(text);
            $('#is2or-chat-messages').append($msg);
            this.scrollToBottom();
        },

        appendProducts: function (products) {
            if (!products || !products.length) { return; }
            var $wrap = $('<div class="is2or-chat-products-grid"></div>');
            $.each(products, function (i, p) {
                var $card = $('<a class="is2or-chat-product-card" target="_blank"></a>').attr('href', p.url);

                var $imgWrap = $('<div class="is2or-chat-product-img"></div>');
                if (p.image_url) {
                    var $img = $('<img>').attr('src', p.image_url).attr('alt', p.product);
                    $img.removeAttr('width').removeAttr('height');
                    $imgWrap.append($img);
                } else {
                    $imgWrap.append($('<div class="is2or-chat-product-noimg"></div>'));
                }
                $card.append($imgWrap);

                var $info = $('<div class="is2or-chat-product-info"></div>');
                $info.append($('<div class="name"></div>').text(p.product));
                if (p.price) { $info.append($('<div class="price"></div>').text(p.price)); }
                $card.append($info);

                $wrap.append($card);
            });
            $('#is2or-chat-messages').append($wrap);
            this.scrollToBottom();
        },

        renderChips: function (chips) {
            var $chips = $('#is2or-chat-chips').empty();
            if (!chips || !chips.length) { return; }
            var self = this;
            $.each(chips, function (i, text) {
                var $chip = $('<button type="button" class="is2or-chat-chip"></button>').text(text);
                $chip.on('click', function () { self.send(text); });
                $chips.append($chip);
            });
        },

        scrollToBottom: function () {
            var $m = $('#is2or-chat-messages');
            $m.scrollTop($m[0].scrollHeight);
        },

        send: function (message) {
            var self = this;
            message = $.trim(message);
            if (message === '') { return; }

            this.appendMessage(message, 'user');
            this.renderChips([]);
            $('#is2or-chat-input').val('');

            $.ajax({
                url: this.ajaxUrl,
                type: 'POST',
                dataType: 'json',
                data: {
                    message: message,
                    session_id: this.getSessionId(),
                    locale: this.locale
                }
            }).done(function (response) {
                if (!response || !response.success) {
                    self.appendMessage((response && response.error) || 'Something went wrong.', 'bot');
                    return;
                }
                if (response.session_id) { self.setSessionId(response.session_id); }
                self.appendMessage(response.reply || '', 'bot');
                self.appendProducts(response.products);
                self.renderChips(response.suggested_replies);
            }).fail(function () {
                self.appendMessage('Could not reach chat service.', 'bot');
            });
        }
    };

    $(document).on('click', '#is2or-chat-toggle', function () {
        $('#is2or-chat-panel').toggle();
    });
    $(document).on('click', '#is2or-chat-close', function () {
        $('#is2or-chat-panel').hide();
    });
    $(document).on('click', '#is2or-chat-send', function () {
        is2orChat.send($('#is2or-chat-input').val());
    });
    $(document).on('keypress', '#is2or-chat-input', function (e) {
        if (e.which === 13) { is2orChat.send($(this).val()); }
    });

})(jQuery);
</script>
{/if}