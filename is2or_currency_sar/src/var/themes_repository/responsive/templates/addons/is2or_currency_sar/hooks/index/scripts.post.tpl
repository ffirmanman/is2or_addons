<script>
(function(_, $) {
    $(document).on('blur', '.ty-vendor-communication-new-message__message textarea, [id^="new_thread_message_"] textarea', function() {
        const el = $(this);
        const value = el.val();

        const maskContactInfo = function(text) {
            {literal}
            const patterns = [
                // Obfuscated emails
                /\b[a-z0-9._%+-]+\s*(?:@|\[at\]|\(at\)|\sat\s)\s*[a-z0-9.-]+\s*(?:\.|\[dot\]|\(dot\)|\sdot\s)\s*[a-z]{2,}\b/gi,
                // Normal emails
                /[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}/gi,
                // Phone numbers
                /(\+?\d[\d\s\-]{7,14}\d)/g,
                // URLs
                /(https?:\/\/[^\s]+|www\.[^\s]+)/gi,
                // Messaging keywords
                /\b(whatsapp|telegram|wechat|line|viber)\b/gi
            ];
            {/literal}

            let masked = text;
            patterns.forEach(regex => {
                masked = masked.replace(regex, '***');
            });

            return masked;
        }

        const masked = maskContactInfo(value);

        el.val(maskContactInfo(value));
    })
})(Tygh, Tygh.$);
</script>