<script>
(function(_, $) {
    $.extend(_, {
        is2or_spl_integration: {$addons.is2or_spl_integration|json_encode nofilter}
    });

    _.tr({
        "is2or_spl_integration.short_address_is_required": '{__("is2or_spl_integration.short_address_is_required")|escape:"javascript"}',
        "is2or_spl_integration.address_generated_successfully": '{__("is2or_spl_integration.address_generated_successfully")|escape:"javascript"}',
    });

    {** Expose building_no field IDs for JS auto-fill **}
    {if $is2or_spl_building_no_fields}
    _.is2or_spl_integration.s_building_no_field_id = {$is2or_spl_building_no_fields.s|intval};
    _.is2or_spl_integration.b_building_no_field_id = {$is2or_spl_building_no_fields.b|intval};
    {/if}
})(Tygh, Tygh.$);
</script>

{script src="js/addons/is2or_spl_integration/func.js"}