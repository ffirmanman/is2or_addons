{$order_statuses=$smarty.const.STATUSES_ORDER|fn_get_statuses:$statuses:$get_additional_statuses:true}
<div class="order-status-read-only">
    <span  class="btn btn-info o-status-{$order_info.status|lower} order-status">
        {$order_statuses.{$order_info.status}.description|default:$order_info.status}
    </span>
</div>
