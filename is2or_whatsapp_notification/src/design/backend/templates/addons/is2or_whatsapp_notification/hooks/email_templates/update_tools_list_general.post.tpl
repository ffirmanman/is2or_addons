{if ($smarty.request.receiver == "C" || $smarty.request.receiver == "V") && $smarty.request.event_id}
    <li>
        <a href="{"is2or_whatsapp_notification.manage?event_id=`$smarty.request.event_id`&receiver=`$smarty.request.receiver`"|fn_url}">
            {__("is2or_edit_whatsapp_template")}
        </a>
    </li>
{/if}
