{* Fragment ini dirender via ajax ke dalam #chat_panel_container di threads.tpl (is2or_account_menu).
   PENTING: id di bawah harus SAMA PERSIS dengan id container di threads.tpl, karena cm-ajax
   menukar elemen berdasarkan kecocokan id ini. *}
<div class="ty-vc-layout__chat" id="chat_panel_container" data-active-thread="{$thread_id}">
<div class="ty-vc-chat-panel__inner" data-ca-vendor-communication="post-chat">

    <div class="ty-vc-chat-panel__header">
        <strong>
            {$counterpart_name = ""}

            {* Langkah 1: Cari dari riwayat pesan ($messages) *}
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
        </strong>
    </div>

    <div class="ty-vc-chat-panel__scroll-to-top-bg"></div>
    <a href="#tygh_container" class="ty-vendor-communication-post__scroll-to-top">
        {__("vendor_communication.scroll_to_top")}
    </a>

    <div class="ty-vendor-communication-post__wrapper" id="messages_list_{$thread_id}">
        {if $messages}
            {foreach $messages as $message}
                <div class="ty-vendor-communication-post__content vendor-communication-post-item {if $message.user_type == $auth.user_type}ty-vendor-communication-post__you {/if}ty-mtb-s">
                    {hook name="vendor_communication:items_list_row"}
                    <div class="ty-vendor-communication-post__date">{$message.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</div>
                    <div class="ty-vendor-communication-post__img">
                        {if $message.user_type == "V"}
                            <a href="{"companies.products?company_id=`$message.vendor_info.logos.theme.company_id`"|fn_url}">
                                {include file="common/image.tpl" images=$message.vendor_info.logos.theme.image image_width="60" image_height="60" class="ty-vendor-communication-post__logo"}
                            </a>
                        {/if}
                        {if $message.user_type === "A"}
                            {include_ext file="common/icon.tpl" class="ty-icon-user"}
                        {/if}
                    </div>
                    <div class="ty-vendor-communication-post__info">
                        <div class="ty-vendor-communication-post {cycle values=", ty-vendor-communication-post_even"}" id="post_{$message.message_id}">
                            <div class="ty-vendor-communication-post__message">{$message.message|nl2br nofilter}</div>
                            <span class="ty-caret"> <span class="ty-caret-outer"></span> <span class="ty-caret-inner"></span></span>
                        </div>
                        <div class="ty-vendor-communication-post__author">{if $message.user_type == "C"}{__("vendor_communication.you")}{else}{$message.firstname} {$message.lastname}{/if}</div>
                    </div>
                    {/hook}
                </div>
            {/foreach}
        {else}
            <p class="ty-no-items">{__("vendor_communication.no_messages_found")}</p>
        {/if}
    <!--messages_list_{$thread_id}--></div>

    {include
        file="addons/vendor_communication/views/vendor_communication/components/new_message_form.tpl"
        object_id=$thread_id
    }
</div>
<!--chat_panel_container--></div>

{script src="js/addons/vendor_communication/vendor_communication.js"}
