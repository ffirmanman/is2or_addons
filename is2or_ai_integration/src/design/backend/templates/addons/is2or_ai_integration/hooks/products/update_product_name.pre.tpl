{if $is2or_risky_flag}
<div class="control-group">
    <div class="controls">
        <div class="alert {if $is2or_risky_flag.risk == "high"}alert-danger{else}alert-warning{/if}">
            <strong>{__("is2or_ai_integration.risky_listing_warning")}</strong>
            <div>{$is2or_risky_flag.detail}</div>
        </div>
    </div>
</div>
{/if}