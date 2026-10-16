{capture name="mainbox"}

    {* 1. Tambahkan div wrapper dengan ID untuk target auto-refresh (AJAX) *}
    <div id="update_risky_listings_list">
        <form action="{""|fn_url}" method="get" name="risky_listings_filter_form">
            <input type="hidden" name="dispatch" value="is2or_ai_integration.risky_listings" />

            <div class="control-group">
                <label class="control-label">{__("status")}</label>
                <div class="controls">
                    <select name="status" onchange="this.form.submit()">
                        <option value="pending_review" {if $queue_status == "pending_review"}selected="selected"{/if}>{__("is2or_ai_integration.status_pending_review")}</option>
                        <option value="approved" {if $queue_status == "approved"}selected="selected"{/if}>{__("is2or_ai_integration.status_approved")}</option>
                        <option value="rejected" {if $queue_status == "rejected"}selected="selected"{/if}>{__("is2or_ai_integration.status_rejected")}</option>
                    </select>
                </div>
            </div>

            {if $rows}
                <table width="100%" class="table table-middle">
                    <thead>
                    <tr>
                        <th>{__("product")}</th>
                        <th width="10%">{__("is2or_ai_integration.risk")}</th>
                        <th width="10%">{__("is2or_ai_integration.suggested_action")}</th>
                        <th>{__("is2or_ai_integration.reason")}</th>
                        <th class="right" width="160">&nbsp;</th>
                    </tr>
                    </thead>
                    {foreach from=$rows item="row"}
                        <tr>
                            <td>
                                {if $row.product_id}
                                    <a href="{"products.update?product_id=`$row.product_id`"|fn_url}" target="_blank">{$row.product_name}</a>
                                    <div class="muted small">ID: {$row.product_id}</div>
                                {else}
                                    {$row.product_name}
                                {/if}
                            </td>
                            <td>
                                {if $row.risk == "high"}
                                    <span class="label label-danger">{__("is2or_ai_integration.risk_high")}</span>
                                {elseif $row.risk == "medium"}
                                    <span class="label label-warning">{__("is2or_ai_integration.risk_medium")}</span>
                                {elseif $row.risk == "low"}
                                    <span class="label label-info">{__("is2or_ai_integration.risk_low")}</span>
                                {else}
                                    <span class="label label-default">&mdash;</span>
                                {/if}
                                {if $row.score !== null}
                                    <div class="muted small">{"%.0f"|sprintf:$row.score*100}%</div>
                                {/if}
                            </td>
                            <td>
                                {if $row.action == "block"}
                                    <span class="label label-danger">{__("is2or_ai_integration.action_block")}</span>
                                {elseif $row.action == "review"}
                                    <span class="label label-warning">{__("is2or_ai_integration.action_review")}</span>
                                {elseif $row.action == "allow"}
                                    <span class="label label-success">{__("is2or_ai_integration.action_allow")}</span>
                                {else}
                                    <span class="label label-default">&mdash;</span>
                                {/if}
                            </td>
                            <td>
                                {if $row.reasons}
                                    <ul class="is2or-reason-list">
                                    {foreach from=$row.reasons item="reason"}
                                        <li>
                                            {if $lang_code == "ar" && $reason.detail_ar}{$reason.detail_ar}{else}{$reason.detail}{/if}
                                            {if $reason.confidence}<span class="muted small">({"%.0f"|sprintf:$reason.confidence*100}%)</span>{/if}
                                        </li>
                                    {/foreach}
                                    </ul>
                                {else}
                                    <span class="muted">&mdash;</span>
                                {/if}
                            </td>
                            <td class="right nowrap">
                                {if $row.status == "pending_review"}
                                    <a href="{"is2or_ai_integration.risky_listings_approve?suggestion_id=`$row.id`&status=`$queue_status`"|fn_url}" class="btn btn-primary cm-post cm-ajax" data-ca-target-id="update_risky_listings_list">{__("approve")}</a>
                                    
                                    <a href="{"is2or_ai_integration.risky_listings_reject?suggestion_id=`$row.id`&status=`$queue_status`"|fn_url}" class="btn text-error cm-confirm cm-post cm-ajax" data-ca-target-id="update_risky_listings_list">{__("reject")}</a>
                                {else}
                                    <span class="muted">{$row.status}</span>
                                {/if}
                            </td>
                        </tr>
                    {/foreach}
                </table>
            {else}
                <p class="no-items">{__("is2or_ai_integration.no_risky_listings")}</p>
            {/if}

        </form>
    <!--update_risky_listings_list--></div>
{/capture}

{include file="common/mainbox.tpl" title=__("is2or_ai_integration.risky_listings") content=$smarty.capture.mainbox}

<style>
.is2or-reason-list { margin: 0; padding-left: 18px; }
.is2or-reason-list li { margin-bottom: 4px; }
.is2or-reason-list li:last-child { margin-bottom: 0; }
</style>