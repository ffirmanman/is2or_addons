<form action="{""|fn_url}" method="POST" name="quotationdisapprove">
    <input type="hidden" name="quotation[quotation_id]" value="{$quotationdata.id}">
    <input type="hidden" name="quotation[status]" value="D">
    <div class="control-group">
        <label for="message" class="control-label cm-required disapproveLabel" style=>{__("reason")}:</label>
        <div class="controls">
            <textarea name="quotation[reason]" id="message" class="input-textarea-long" cols="70" rows="8"></textarea>
        </div>
    </div>
    <div class="buttons-container">
        {include file="buttons/save_cancel.tpl" but_name="dispatch[sg_quotation.disapprove_quotation]" but_meta='cm-ajax cm-dialog-closer' cancel_action="close" extra="" hide_first_button=$hide_first_button save=true but_text=__('disapprove')}
    </div>
</form>
<style>
    .control-label.cm-required.disapproveLabel{
        background-image:url('data:image/svg+xml,<svg viewBox="0 0 5 5" width="5" height="5" xmlns="http://www.w3.org/2000/svg" fill="%23ff697a"><path d="M2.5 0c.3 0 .5.2.5.5V1.7l1.2-.6c.3-.1.6-.1.7.2.1.2.1.5-.2.6l-1.2.6 1.2.6c.3.1.3.4.2.6-.2.2-.5.3-.7.2L3 3.3v1.2c0 .3-.2.5-.5.5-.3 0-.5-.2-.5-.5V3.3l-1.2.6c-.3.1-.6.1-.7-.2-.1-.2-.1-.5.2-.6l1.2-.6-1.2-.6c-.3-.1-.3-.4-.2-.6.2-.2.5-.3.7-.2l1.21.6V.5c0-.3.2-.5.5-.5Z"/></svg>');
        width: 55px;
    }
</style>