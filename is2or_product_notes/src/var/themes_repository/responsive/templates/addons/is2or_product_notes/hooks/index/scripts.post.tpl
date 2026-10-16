<script>
(function(_, $) {
    $(_.doc).on('blur', ".product-notes__input", function() {
        const item_id = $(this).data('caItemId');
        const notes = $(this).val();

        $.ceAjax(
            'request',
            fn_url('product_notes.update'),
            {
                method: 'POST',
                data: {
                    item_id,
                    notes
                }
            }
        );
    });
})(Tygh, Tygh.$);
</script>