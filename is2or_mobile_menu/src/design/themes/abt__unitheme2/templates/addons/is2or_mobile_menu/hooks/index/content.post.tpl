{$is2or_uri = $smarty.server.REQUEST_URI}

{if ($is2or_uri|strstr:"/faq") || ($is2or_uri|strstr:"/buyer-protection") || ($is2or_uri|strstr:"/accepted-payment-methods")}
<style>
    .ut2-sticky-panel__wrap,
    .ut2-sticky-panel {
        display: none !important;
    }
</style>
{/if}