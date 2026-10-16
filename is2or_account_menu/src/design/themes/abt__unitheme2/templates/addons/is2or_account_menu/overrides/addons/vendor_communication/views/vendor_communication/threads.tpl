{$c_url = $config.current_url|fn_query_remove:"sort_by":"sort_order"}
{if $search.sort_order == "asc"}
    {include_ext file="common/icon.tpl" class="ty-icon-down-dir" assign=sort_sign}
{else}
    {include_ext file="common/icon.tpl" class="ty-icon-up-dir" assign=sort_sign}
{/if}
{if !$config.tweaks.disable_dhtml}
    {$ajax_class = "cm-ajax"}
{/if}

{$rev = $smarty.request.content_id|default:"pagination_contents"}
{$show_subject_image_column = false}

{foreach $threads as $thread}
    {if $thread.object_type === $smarty.const.VC_OBJECT_TYPE_PRODUCT
        || $thread.object_type === $smarty.const.VC_OBJECT_TYPE_COMPANY
    }
        {$show_subject_image_column = true}
    {/if}
{/foreach}

<link type="text/css" rel="stylesheet" href="{"design/themes/abt__unitheme2/css/addons/is2or_account_menu/style.css"|fn_url}" />

<div class="ty-vc-layout">

    {* Kolom 1: menu akun *}
    <div class="ty-vc-layout__menu">
        {include file="addons/is2or_account_menu/components/account_menu.tpl"}
    </div>

    {* Kolom 2: list thread *}
    <div class="ty-vc-layout__threads" id="threads_container">

        <div class="ty-vc-layout__threads-header">
            <h1 class="ty-mainbox-title">{__("vendor_communication.messages")}</h1>
        </div>

        {if "ULTIMATE"|fn_allowed_for}
            {if $addons.vendor_communication.show_on_messages == "Y"}
                {include file="addons/vendor_communication/views/vendor_communication/components/new_thread_button.tpl" object_id=$company_id show_form=true}

                {include
                    file="addons/vendor_communication/views/vendor_communication/components/new_thread_form.tpl"
                    object_type=$smarty.const.VC_OBJECT_TYPE_COMPANY
                    object_id=$company_id
                    company_id=$company_id
                    vendor_name=$company_id|fn_get_company_name
                }
            {/if}
        {/if}

        {include file="common/pagination.tpl"}

        <div class="ty-vc-thread-list">
            {foreach $threads as $thread}
                {$has_new_message = $auth.user_id != $thread.last_message_user_id && $thread.user_status == $smarty.const.VC_THREAD_STATUS_HAS_NEW_MESSAGE}

                <div class="ty-vc-thread-item {if $thread.thread_id == $active_thread}active{/if}">
                    {if $has_new_message}
                        <span class="ty-vc-thread-item__new-dot"></span>
                    {/if}

                    {if $show_subject_image_column}
                        <span class="ty-vc-thread-item__avatar">
                            {include file="addons/vendor_communication/views/vendor_communication/components/subject_image.tpl"
                                thread=$thread
                            }
                        </span>
                    {/if}

                    <a class="ty-vc-thread-item__body ty-vc-thread-open"
                       data-ca-thread-id="{$thread.thread_id}"
                       href="{"vendor_communication.view?thread_id=`$thread.thread_id`"|fn_url}"
                    >
                        <span class="ty-vc-thread-item__top">
                            <span class="ty-vc-thread-item__name">
                                {$counterpart_name = ""}

                                {if $messages}
                                    {foreach $messages as $message}
                                        {if !$counterpart_name && $message.user_type != $auth.user_type}
                                            {if $message.user_type == "V" && !empty($message.vendor_info.company)}
                                                {$counterpart_name = $message.vendor_info.company}
                                            {elseif !empty($message.firstname) || !empty($message.lastname)}
                                                {$counterpart_name = "`$message.firstname` `$message.lastname`"|trim}
                                            {/if}
                                        {/if}
                                    {/foreach}
                                {/if}

                                {if !$counterpart_name && !empty($thread)}
                                    {if !empty($thread.company)}
                                        {$counterpart_name = $thread.company}
                                    {elseif !empty($thread.firstname) || !empty($thread.lastname)}
                                        {$counterpart_name = "`$thread.firstname` `$thread.lastname`"|trim}
                                    {/if}
                                {/if}

                                {if $counterpart_name}
                                    {$counterpart_name}
                                {else}
                                    {__("vendor_communication.ticket")} &lrm;#{$thread_id}
                                {/if}
                            </span>
                            <span class="ty-vc-thread-item__time">{$thread.last_updated|date_format:"`$settings.Appearance.date_format`"}</span>
                        </span>

                        <span class="ty-vc-thread-item__msg {if $thread.new_message}ty-new__text{/if}">
                            {$thread.last_message|truncate:80:"...":true}
                        </span>
                    </a>
                </div>
            {foreachelse}
                <p class="ty-no-items">{__("vendor_communication.no_threads_found")}</p>
            {/foreach}
        </div>

        {include file="common/pagination.tpl"}
    <!--threads_container--></div>

    <div class="ty-vc-layout__chat" id="chat_panel_container" data-active-thread="{$active_thread}">
        {if $active_thread}
            <div class="ty-vc-chat-panel__empty">{__("loading")}...</div>
        {else}
            <div class="ty-vc-chat-panel__empty">Choose a conversation</div>
        {/if}
    </div>

</div>

{capture name="mainbox_title"}{__("vendor_communication.messages")}{/capture}

{script src="js/addons/vendor_communication/vendor_communication.js"}
{script src="js/addons/is2or_account_menu/is2or_account_menu.js"}
