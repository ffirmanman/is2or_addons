{capture name="mainbox"}

<div class="control-group">
    <form action="{""|fn_url}" method="get" class="form-inline">
        <input type="hidden" name="dispatch" value="is2or_ai_integration.document_checks" />
        <label>{__("status")}:</label>
        <select name="status" onchange="this.form.submit()">
            <option value="pending_review" {if $queue_status == 'pending_review'}selected="selected"{/if}>{__("is2or_ai_integration.status_pending_review")}</option>
            <option value="failed" {if $queue_status == 'failed'}selected="selected"{/if}>{__("is2or_ai_integration.status_failed")}</option>
            <option value="synced_approved" {if $queue_status == 'synced_approved'}selected="selected"{/if}>{__("is2or_ai_integration.status_synced_approved")}</option>
            <option value="synced_rejected" {if $queue_status == 'synced_rejected'}selected="selected"{/if}>{__("is2or_ai_integration.status_synced_rejected")}</option>
        </select>
    </form>
</div>

<div id="document_checks_list">

{if !empty($rows)}
    <table class="table table-middle" width="100%">
        <thead>
            <tr>
                <th width="20%">{__("vendor")}</th>
                <th width="15%">{__("is2or_ai_integration.document_type")}</th>
                <th width="10%" class="center">{__("is2or_ai_integration.legible")}</th>
                <th width="10%" class="center">{__("is2or_ai_integration.mismatches")}</th>
                <th width="15%">{__("date")}</th>
                <th width="15%">{__("status")}</th>
                <th width="15%" class="right">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
        {foreach from=$rows item="row"}
            <tr>
                <td>
                    <a href="{"companies.update?company_id=`$row.company_id`"|fn_url}">{$row.company_name|escape}</a>
                </td>

                <td>{$row.document_type|escape}</td>

                <td class="center">
                    {if $row.output.legible === false}
                        <span class="label label-important">{__("no")}</span>
                    {elseif $row.output.legible === true}
                        <span class="label label-success">{__("yes")}</span>
                    {else}
                        <span class="muted">&mdash;</span>
                    {/if}
                </td>

                <td class="center">
                    {if !empty($row.output.mismatches)}
                        <span class="label label-important">{$row.output.mismatches|@count}</span>
                    {else}
                        <span class="label label-success">0</span>
                    {/if}
                </td>

                <td>{$row.timestamp|date_format:"%d.%m.%Y %H:%M"}</td>

                <td>{$row.status|escape}</td>

                <td class="right nowrap">
                    <a class="btn" href="{"companies.update?company_id=`$row.company_id`"|fn_url}">{__("details")}</a>
                </td>
            </tr>
        {/foreach}
        </tbody>
    </table>
{else}
    <p class="no-items">{__("no_data")}</p>
{/if}

<!--document_checks_list--></div>

{/capture}

{include file="common/mainbox.tpl" title=__("is2or_ai_integration.documents_verification") content=$smarty.capture.mainbox}