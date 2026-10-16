{capture name="mainbox"}
    <form action="{""|fn_url}" method="post" name="wa_template_form" class="form-horizontal">
        <input type="hidden" name="event_id" value="{$event_id}" />
        <input type="hidden" name="receiver" value="{$receiver}" />
        <div class="control-group">
            <label class="control-label">{__("receiver")}:</label>
            <div class="controls">
                <span class="shift-input">{$receiver_name}</span>
            </div>
        </div>
        <div class="control-group">
            <label class="control-label">{__("event")}:</label>
            <div class="controls">
                <span class="shift-input">{$event_id}</span>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label" for="elm_wa_content">{__("message_content")}:</label>
            <div class="controls">
                {* TextArea untuk input pesan *}
                <textarea id="elm_wa_content" name="wa_template[content]" cols="55" rows="12" class="span10 cm-focus">{$wa_template.content}</textarea>
                <p class="muted description">{__("is2or_wa_use_variables_hint")}</p>
            </div>
        </div>

        {capture name="buttons"}
            {include file="buttons/save_cancel.tpl" but_name="dispatch[is2or_whatsapp_notification.update]" but_role="submit-link" but_target_form="wa_template_form" save=true}
        {/capture}
    </form>
{/capture}

{* SIDEBAR: Menampilkan Parameter *}
{capture name="sidebar"}
    <div class="sidebar-row">
        <h6>{__("variables")}</h6>
        <p class="muted">{__("is2or_click_to_insert")}</p>
        <ul class="nav nav-list variables-list" style="max-height: 600px; overflow-y: auto;">
            {foreach from=$variables key="group_name" item="group"}
                <li class="nav-header">{$group_name|upper}</li>

                {* Jika variabel memiliki atribut (seperti o.order_id) *}
                {if $group.attributes}
                    {foreach from=$group.attributes item="attr"}
                        {if !is_array($attr)}
                            <li>
                            <span class="label label-info hand cm-wa-insert-var" data-var-tag="{$group.alias}.{$attr}">
                                {$group.alias}.{$attr}
                            </span>
                            </li>
                        {/if}
                    {/foreach}
                {/if}
            {/foreach}
        </ul>
    </div>
{/capture}

{include file="common/mainbox.tpl"
title="{__("is2or_edit_whatsapp_template")}: `$status_name` (`$receiver_name`)"
content=$smarty.capture.mainbox
buttons=$smarty.capture.buttons
sidebar=$smarty.capture.sidebar
}

<script>
  (function(_, $) {
    $(document).on('click', '.cm-wa-insert-var', function() {
      var varTag = '{{ ' + $(this).data('var-tag') + ' }}';
      var $textarea = $('#elm_wa_content');
      var cursorPos = $textarea.prop('selectionStart');
      var text = $textarea.val();

      // Insert variabel ke posisi kursor
      $textarea.val(text.substring(0, cursorPos) + varTag + text.substring(cursorPos));
      $textarea.focus();
    });
  })(Tygh, Tygh.$);
</script>
