<style>
.voice-search {
    position: absolute;
    top: 50%;
    {if $smarty.const.CART_LANGUAGE == 'ar'}
        left: 40px;
    {else}
        right: 40px;
    {/if}
    transform: translateY(-50%);
    cursor: pointer;
    background: transparent !important;
    border: none !important;
}

.voice-search.listening {
    color: #FF5401;
}
</style>

<button type="button" class="voice-search" id="voice_search_btn">
    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
        <path d="M8 11a2 2 0 0 0 2-2V4a2 2 0 1 0-4 0v5a2 2 0 0 0 2 2z"/>
        <path d="M5 10a3 3 0 0 0 6 0h1a4 4 0 0 1-8 0h1z"/>
        <path d="M10 10a2 2 0 0 1-4 0H5a3 3 0 0 0 6 0h-1z"/>
    </svg>
</button>

<script>
(function(_, $) {
    $(document).ready(function() {
        var micBtn = $('#voice_search_btn');
        var inputSearch = micBtn.parent().find('input.ty-search-block__input').first();

        if (!('webkitSpeechRecognition' in window)) {
            console.warn('Your browser does not support speech recognition');
            micBtn.hide();

            return;
        }

        var recognition = new webkitSpeechRecognition();
        recognition.interimResults = false;
        recognition.continuous = false;
        recognition.maxAlternatives = 1;

        var listening = false;

        micBtn.on('click', function() {
            if (listening) {
                recognition.stop();
            } else {
                recognition.start();
                micBtn.addClass('listening');
            }

            listening = !listening;
        });

        recognition.onresult = function(event) {
            var text = event.results[0][0].transcript;
            inputSearch.focus().val(text);
            recognition.stop();
            micBtn.removeClass('listening');

            if (text.length) {
                inputSearch.closest('form').find('button[type="submit"]').trigger('click');
            }
        };

        recognition.onend = function() {
            micBtn.removeClass('listening');
        }
    })
})(Tygh, Tygh.$);
</script>