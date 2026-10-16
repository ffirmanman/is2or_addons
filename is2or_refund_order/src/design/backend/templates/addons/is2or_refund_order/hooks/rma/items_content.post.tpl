{assign var="refund_evidences"
    value=$return_info.return_id|fn_get_is2or_evidences}

{if $refund_evidences}
<div id="refund-evidence-container" class="hidden">
    <div class="return-evidence-box" style="margin-top:30px;">
        <h4 class="subheader">Refund Evidence</h4>

        <div style="display:flex;flex-wrap:wrap;gap:15px;">
            {foreach from=$refund_evidences item="file"}
                <div style="width:200px;text-align:center;">
                    {if $file.file_type == "photo"}
                        <a class="cm-image-previewer"
                           href="{$config.current_location}/var/files/refund/{$file.return_id}/{$file.filename}">
                            <img src="{$config.current_location}/var/files/refund/{$file.return_id}/{$file.filename}"
                                 style="width:100%;height:150px;object-fit:cover;">
                        </a>
                    {elseif $file.file_type == "video"}
                        <video width="100%" height="150" controls>
                            <source src="{$config.current_location}/var/files/refund/{$file.return_id}/{$file.filename}" type="video/mp4">
                        </video>
                    {/if}
                </div>
            {/foreach}
        </div>
    </div>
</div>
{/if}

{literal}
<script>
(function (_, $) {
    $.ceEvent('on', 'ce.commoninit', function () {

        var $evidence = $('#refund-evidence-container');
        if (!$evidence.length) {
            return;
        }

        var $returnTab = $('#content_return_products');
        var $tableWrapper = $returnTab.find('.table-responsive-wrapper');

        if ($tableWrapper.length) {
            $tableWrapper.after($evidence.removeClass('hidden'));
        }
    });
}(Tygh, Tygh.$));
</script>
{/literal}
