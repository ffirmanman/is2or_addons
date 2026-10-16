{capture name="mainbox"}
    {* Wrapper ID untuk target refresh AJAX *}
    <div id="update_document_verifications_list">
        
        {if !empty($is2or_document_verifications)}
            <table class="table table-middle" width="100%">
                <thead>
                    <tr>
                        <th width="15%">{__("is2or_ai_integration.document_type")}</th>
                        <th width="20%">{__("file")}</th>
                        <th width="10%" class="center">{__("is2or_ai_integration.legible")}</th>
                        <th width="25%">{__("is2or_ai_integration.fields_found")}</th>
                        <th width="15%">{__("is2or_ai_integration.mismatches")}</th>
                        <th width="15%" class="right">&nbsp;</th>
                    </tr>
                </thead>
                <tbody>
                {foreach from=$is2or_document_verifications item="row"}
                    <tr>
                        <td><strong>{$row.document_type}</strong></td>
                        
                        <td>
                            <a href="{"is2or_ai_integration.view_document?company_id=`$row.company_id`&field_id=`$row.field_id`"|fn_url}" target="_blank">{$row.filename}</a>
                        </td>
                        
                        <td class="center">
                            {if $row.output.legible === false}
                                <span class="label label-important">{__("no")}</span>
                            {elseif $row.output.legible === true}
                                <span class="label label-success">{__("yes")}</span>
                            {else}
                                <span class="muted">&mdash;</span>
                            {/if}
                        </td>
                        
                        <td>
                            {if !empty($row.output.fields)}
                                <ul class="list-unstyled" style="margin-bottom:0; font-size: 13px;">
                                {foreach from=$row.output.fields key="fk" item="fv"}
                                    <li><strong>{$fk}:</strong> {$fv}</li>
                                {/foreach}
                                </ul>
                            {else}
                                <span class="muted">&mdash;</span>
                            {/if}
                        </td>
                        
                        <td>
                            {if !empty($row.output.mismatches)}
                                <ul class="list-unstyled text-error" style="margin-bottom:0; font-size: 13px;">
                                {foreach from=$row.output.mismatches item="mm"}
                                    <li style="margin-bottom: 6px;">
                                        <strong>{$mm.field}:</strong><br>
                                        <span class="muted small">{__("is2or_ai_integration.expected")}:</span> "{$mm.expected|escape}"<br>
                                        <span class="muted small">{__("is2or_ai_integration.found")}:</span> "{$mm.found|escape}"
                                    </li>
                                {/foreach}
                                </ul>
                            {else}
                                <span class="label label-success">{__("is2or_ai_integration.match")}</span>
                            {/if}
                        </td>
                        
                        <td class="right nowrap">
                            {if $row.status == 'pending_review'}
                                {* Tambahkan cm-post untuk CSRF Token & cm-ajax untuk auto-refresh tabel *}
                                <a class="btn btn-primary cm-post cm-ajax" 
                                   data-ca-target-id="update_document_verifications_list"
                                   href="{"is2or_ai_integration.document_verification_approve?id=`$row.id`"|fn_url}">{__("approve")}</a>
                                   
                                <a class="btn text-error cm-post cm-ajax" 
                                   data-ca-target-id="update_document_verifications_list"
                                   href="{"is2or_ai_integration.document_verification_reject?id=`$row.id`"|fn_url}">{__("reject")}</a>
                            {else}
                                <span class="muted">{$row.status}</span>
                            {/if}
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>

            <p class="muted" style="margin-top: 15px;">{__("is2or_ai_integration.documents_verification_note")}</p>
        {else}
            <p class="no-items">{__("no_data")}</p>
        {/if}
        
    <!--update_document_verifications_list--></div>
{/capture}

{include file="common/mainbox.tpl" title=__("is2or_ai_integration.documents_verification") content=$smarty.capture.mainbox}