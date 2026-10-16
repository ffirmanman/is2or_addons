{capture name="mainbox"}

<div class="panel panel-default">
    <div class="panel-heading">
        <h4>{__("is2or_sms_integration.test_send_sms")}</h4>
    </div>
    <div class="panel-body">
        <form action="{""|fn_url}" method="post" name="is2or_sms_test_form">
            <input type="hidden" name="dispatch" value="is2or_sms_integration.test_send" />

            <div class="control-group">
                <label class="control-label">{__("is2or_sms_integration.phone_number")}</label>
                <div class="controls">
                    <input type="text" name="test_phone" class="input-large" placeholder="+966501234567" />
                </div>
            </div>
            <div class="control-group">
                <label class="control-label">{__("is2or_sms_integration.message")}</label>
                <div class="controls">
                    <textarea name="test_message" class="input-large" rows="3">{__("is2or_sms_integration.test_message_default")}</textarea>
                </div>
            </div>
            <div class="control-group">
                <div class="controls">
                    <button type="submit" class="btn btn-primary">{__("is2or_sms_integration.send")}</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="panel panel-default">
    <div class="panel-heading">
        <h4>{__("is2or_sms_integration.sms_logs")}</h4>
    </div>
    <div class="panel-body">
        <table class="table table-striped table-bordered">
            <thead>
                <tr>
                    <th>{__("is2or_sms_integration.log_id")}</th>
                    <th>{__("is2or_sms_integration.order_id")}</th>
                    <th>{__("is2or_sms_integration.phone_number")}</th>
                    <th>{__("is2or_sms_integration.message")}</th>
                    <th>{__("status")}</th>
                    <th>{__("date")}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$logs item="log"}
                <tr>
                    <td>{$log.id}</td>
                    <td>{if $log.order_id}{$log.order_id}{else}&mdash;{/if}</td>
                    <td>{$log.phone}</td>
                    <td>{$log.message|truncate:80}</td>
                    <td>{$log.status}</td>
                    <td>{$log.created_at|date_format:"%d %b %Y %H:%M"}</td>
                </tr>
                {foreachelse}
                <tr>
                    <td colspan="6">{__("no_data")}</td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    </div>
</div>

{/capture}
{include file="common/mainbox.tpl" title=__("is2or_sms_integration.sms_integration") content=$smarty.capture.mainbox}
