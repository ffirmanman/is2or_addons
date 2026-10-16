{assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

<link type="text/css" rel="stylesheet" href="{"design/themes/abt__unitheme2/css/addons/is2or_account_menu/style.css"|fn_url}" />

<div class="ty-vc-profile-layout">

    <div class="ty-vc-profile-layout__menu">
        {include file="addons/is2or_account_menu/components/account_menu.tpl"}
    </div>

    <div class="ty-vc-profile-layout__content">
        {**
        * "My Points" dashboard.
        * Shows: total points balance hero card, 3 "how to earn" info cards, and
        * the points history table (sortable + paginated, same data source as
        * before: $userlog / $search / $settings).
        *
        * NOTE ABOUT "Estimated Value":
        * This addon package does not expose the "currency value per point" setting
        * in the files that were provided, so it is read defensively below via
        * $reward_points_settings.points_value (falls back to 0 / hidden if not
        * set). If your reward_points addon settings use a different variable name,
        * just change the assignment on the line marked TODO.
        *}

        {assign var="c_url" value=$config.current_url|fn_query_remove:"sort_by":"sort_order"}

        {if $search.sort_order == "asc"}
            {include_ext file="common/icon.tpl" class="ty-icon-down-dir" assign=sort_sign}
        {else}
            {include_ext file="common/icon.tpl" class="ty-icon-up-dir" assign=sort_sign}
        {/if}

        {* TODO: point this at your real "value per point" setting if the name differs *}
        {assign var="point_value" value=$reward_points_settings.points_value|default:0}
        {assign var="points_balance" value=$user_info.points|default:0}
        {assign var="estimated_value" value=$points_balance*$point_value}

        <div class="rp-dashboard">

            {* ---------- Hero: total points balance ---------- *}
            <div class="rp-hero">
                <div class="rp-hero__info">
                    <span class="rp-hero__label">{__("total_points_balance")|default:"Total points balance"}</span>
                    <div class="rp-hero__points">{$points_balance} {__("points")}</div>
                    {if $point_value > 0}
                        <span class="rp-hero__value">{__("estimated_value")|default:"Estimated value"}: {$estimated_value|fn_format_price} {$currency_symbol}</span>
                    {/if}
                </div>
            </div>

            {* ---------- How to earn: info cards ---------- *}
            <div class="rp-cards">
                <div class="rp-card">
                    <span class="rp-card__icon rp-card__icon--orange">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
                    </span>
                    <div class="rp-card__title">{__("rp_shop_and_earn")|default:"Shop &amp; Earn"}</div>
                    <div class="rp-card__text">{__("rp_shop_and_earn_text")|default:"Earn 1 point for every SAR 10 spent on any category."}</div>
                </div>

                <div class="rp-card">
                    <span class="rp-card__icon rp-card__icon--orange">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>
                    </span>
                    <div class="rp-card__title">{__("rp_review_products")|default:"Review Products"}</div>
                    <div class="rp-card__text">{__("rp_review_products_text")|default:"Get 50 points for every verified purchase review."}</div>
                </div>

                <div class="rp-card">
                    <span class="rp-card__icon rp-card__icon--orange">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg>
                    </span>
                    <div class="rp-card__title">{__("rp_refer_friends")|default:"Refer Friends"}</div>
                    <div class="rp-card__text">{__("rp_refer_friends_text")|default:"Earn 500 points when a friend makes their first purchase."}</div>
                </div>
            </div>

            {* ---------- Points history ---------- *}
            <div class="rp-history">
                <div class="rp-history__head">
                    <h3 class="rp-history__title">{__("reward_points_log")}</h3>
                </div>

                {include file="common/pagination.tpl"}

                <table class="rp-table">
                    <thead>
                        <tr>
                            <th class="rp-table__date">
                                <a class="cm-ajax" href="{"`$c_url`&sort_by=timestamp&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("date")}</a>{if $search.sort_by === "timestamp"}{$sort_sign nofilter}{/if}
                            </th>
                            <th class="rp-table__activity">{__("reason")}</th>
                            <th class="rp-table__points">
                                <a class="cm-ajax" href="{"`$c_url`&sort_by=amount&sort_order=`$search.sort_order_rev`"|fn_url}" data-ca-target-id="pagination_contents">{__("points")}</a>{if $search.sort_by === "amount"}{$sort_sign nofilter}{/if}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    {foreach from=$userlog item="ul"}
                        {capture name="rp_activity"}
                            {if $ul.action == $smarty.const.CHANGE_DUE_ORDER}
                                {assign var="statuses" value=$smarty.const.STATUSES_ORDER|fn_get_simple_statuses:true:true}
                                {assign var="reason" value=$ul.reason|unserialize}
                                {assign var="order_exist" value=$reason.order_id|fn_get_order_name}
                                {__("order")}&nbsp;{if $order_exist}<a href="{"orders.details?order_id=`$reason.order_id`"|fn_url}" class="underlined">{/if}<strong>#{$reason.order_id}</strong>{if $order_exist}</a>{/if}:&nbsp;{$statuses[$reason.from]}&nbsp;&#8212;&#8250;&nbsp;{$statuses[$reason.to]}{if $reason.text}&nbsp;({__($reason.text) nofilter}){/if}
                            {elseif $ul.action == $smarty.const.CHANGE_DUE_USE}
                                {assign var="order_exist" value=$ul.reason|fn_get_order_name}
                                {__("text_points_used_in_order")}: {if $order_exist}<a href="{"orders.details?order_id=`$ul.reason`"|fn_url}">{/if}<strong>#{$ul.reason}</strong>{if $order_exist}</a>{/if}
                            {elseif $ul.action == $smarty.const.CHANGE_DUE_ORDER_DELETE}
                                {assign var="reason" value=$ul.reason|unserialize}
                                {__("order")} <strong>#{$reason.order_id}</strong>: {__("deleted")}
                            {elseif $ul.action == $smarty.const.CHANGE_DUE_ORDER_PLACE}
                                {assign var="reason" value=$ul.reason|unserialize}
                                {assign var="order_exist" value=$reason.order_id|fn_get_order_name}
                                {__("order")} {if $order_exist}<a href="{"orders.details?order_id=`$reason.order_id`"|fn_url}" class="underlined">{/if}<strong>#{$reason.order_id}</strong>{if $order_exist}</a>{/if}: {__("order_placed")}
                            {else}
                                {hook name="reward_points:userlog"}
                                {$ul.reason}
                                {/hook}
                            {/if}
                        {/capture}
                        <tr>
                            <td class="rp-table__date">{$ul.timestamp|date_format:"`$settings.Appearance.date_format`, `$settings.Appearance.time_format`"}</td>
                            <td class="rp-table__activity">{$smarty.capture.rp_activity nofilter}</td>
                            <td class="rp-table__points {if $ul.amount > 0}rp-points--pos{elseif $ul.amount < 0}rp-points--neg{/if}">{if $ul.amount > 0}+{/if}{$ul.amount}</td>
                        </tr>
                    {foreachelse}
                        <tr class="ty-table__no-items">
                            <td colspan="3"><p class="ty-no-items">{__("no_items")}</p></td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>

                {include file="common/pagination.tpl"}
            </div>
        </div>

        {capture name="mainbox_title"}{__("my_points")|default:__("reward_points_log")}{/capture}



    </div>
</div>
