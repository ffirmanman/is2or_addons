{if $order_messages}
<div class="sidebar-row">
    <h6>{__("refund_notes")}</h6>
    <blockquote style="font-size: 12px; line-height: 1.4; border-left: 3px solid #ccc; padding-left: 10px;">
        {$order_messages}
    </blockquote>
</div>
{/if}