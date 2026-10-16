{**
 * IS2OR Login Activity Block
 * Displays all active sessions for the logged-in user.
 * login_activity_data is injected by fn_is2or_login_activity_get_block_data().
 *}

{$sessions = $login_activity_data.sessions|default:[]}
{$user_id  = $login_activity_data.user_id|default:0}

{if $user_id > 0}

<div class="is2or-la__header" style="padding-bottom: 20px">
    <h2 class="is2or-la__title">{__("security_settings")}</h2>
</div>

<div class="is2or-la" id="is2or-login-activity" style="padding: 20px">

    {** ── Header ─────────────────────────────────────────────── **}
    <div class="is2or-la__header">
        <h2 class="is2or-la__title">{__("is2or_login_activity.title")}</h2>
    </div>

    {** ── Notice ─────────────────────────────────────────────── **}
    <div class="is2or-la__notice">
        <span class="is2or-la__notice-icon">&#9888;</span>
        {__("is2or_login_activity.notice")}
    </div>

    {** ── Session list ───────────────────────────────────────── **}
    {if !empty($sessions)}
    <p class="is2or-la__section-label">{__("is2or_login_activity.current_activity")}</p>

    <ul class="is2or-la__list">
        {foreach $sessions as $session}
        <li class="is2or-la__item{if $session.is_current} is2or-la__item--current{/if}">

            {** Device icon **}
            <span class="is2or-la__icon">
                {if $session.device_type === 'mobile'}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                        <path d="M15.5 1h-8A2.5 2.5 0 0 0 5 3.5v17A2.5 2.5 0 0 0 7.5 23h8a2.5 2.5 0 0 0 2.5-2.5v-17A2.5 2.5 0 0 0 15.5 1zm-4 21c-.83 0-1.5-.67-1.5-1.5S10.67 19 11.5 19s1.5.67 1.5 1.5S12.33 22 11.5 22zm4.5-4H7V4h9v14z"/>
                    </svg>
                {elseif $session.device_type === 'tablet'}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                        <path d="M18.5 0h-14C3.12 0 2 1.12 2 2.5v19C2 22.88 3.12 24 4.5 24h14c1.38 0 2.5-1.12 2.5-2.5v-19C21 1.12 19.88 0 18.5 0zm-7 23c-.83 0-1.5-.67-1.5-1.5S10.67 20 11.5 20s1.5.67 1.5 1.5S12.33 23 11.5 23zM19 19H4V3h15v16z"/>
                    </svg>
                {else}
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="28" height="28">
                        <path d="M20 3H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h6v2H8v2h8v-2h-2v-2h6c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 13H4V5h16v11z"/>
                    </svg>
                {/if}
            </span>

            {** Device info **}
            <div class="is2or-la__info">
                <span class="is2or-la__device-name">{$session.device_name|escape}</span>
                {if $session.is_current}
                    <span class="is2or-la__badge">{__("is2or_login_activity.active_now")}</span>
                {else}
                    <span class="is2or-la__last-active">
                        {__("is2or_login_activity.last_active")}: {$session.last_active|date_format:"%d %b %Y, %H.%M"}
                    </span>
                {/if}
            </div>

            {** Logout button (only for non-current sessions) **}
            {if !$session.is_current}
            <form method="post" action="{"is2or_login_activity.logout_session"|fn_url}" class="is2or-la__logout-form">
                <input type="hidden" name="csrf_token" value="{$smarty.session.security_hash}" />
                <input type="hidden" name="id" value="{$session.id|escape}" />
                <button type="submit" class="is2or-la__btn-logout">
                    {__("is2or_login_activity.logout")}
                </button>
            </form>
            {/if}

        </li>
        {/foreach}
    </ul>

    {** ── Logout all button ───────────────────────────────────── **}
    {$non_current_count = 0}
    {foreach $sessions as $s}{if !$s.is_current}{$non_current_count = $non_current_count + 1}{/if}{/foreach}

    {if $non_current_count > 0}
    <div class="is2or-la__footer">
        <form method="post" action="{"is2or_login_activity.logout_all"|fn_url}" class="is2or-la__logout-all-form">
            <input type="hidden" name="csrf_token" value="{$smarty.session.security_hash}" />
            <button type="submit" class="is2or-la__btn-logout-all">
                {__("is2or_login_activity.logout_all")}
            </button>
        </form>
    </div>
    {/if}

    {else}
    <p class="is2or-la__empty">{__("is2or_login_activity.no_sessions")}</p>
    {/if}

</div>


    {capture name="delete_my_account"}
        {include file="views/profiles/components/user_action_popup.tpl"
            action="anonymization_request"
            description=__("delete_my_account_description")
            title=__("delete_my_account")
            id=$block.block_id
        }
    {/capture}

    <div class="danger-zone" style="margin-top: 30px">
        <div class="danger-zone__header">
            <svg class="danger-zone__icon" viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                <line x1="12" y1="9" x2="12" y2="13"/>
                <line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            <span class="danger-zone__title">{__("danger_zone")|default:"Danger Zone"}</span>
        </div>

        <p class="danger-zone__subtitle">
            {__("danger_zone_description")|default:"Once you delete your account, there is no going back. Please be certain."}
        </p>

        <div class="danger-zone__card">
            <div class="danger-zone__info">
                <div class="danger-zone__action-title">{__("delete_my_account")}</div>
                <div class="danger-zone__action-desc">
                    {__("delete_my_account_short_description")|default:"Permanently remove your account and all data."}
                </div>
            </div>

            {include file="common/popupbox.tpl"
                link_text=__("delete_my_account")
                title=__("delete_my_account")
                id="anonymization_request_dialog_`$block.block_id`"
                content=$smarty.capture.delete_my_account
                link_meta="danger-zone__btn"
            }
        </div>
    </div>

{** ═══════════════════════════════ STYLES ═══════════════════════════════ **}
<style>
.is2or-la {
    font-family: inherit;
    color: #1a1a1a;
    padding: 0 0 24px;
    border: 1px solid #eceef1;
    border-radius: 12px;
}

.is2or-la__header {
    margin-bottom: 12px;
}

.is2or-la__title {
    font-size: 20px;
    font-weight: 700;
    margin: 0 0 12px;
    color: #1a1a1a;
}

.is2or-la__notice {
    display: flex;
    align-items: center; 
    gap: 8px;
    background: #fffbe6;
    border: 1px solid #ffe58f;
    border-radius: 8px;
    padding: 10px 14px;
    font-size: 13px;
    color: #7c5c00;
    margin-bottom: 20px;
    line-height: 1.5;
}

.is2or-la__notice-icon {
    font-size: 16px;
    flex-shrink: 0;
    margin-top: 0; 
    color: #f5a623;
}
.is2or-la__section-label {
    font-size: 13px;
    color: #888;
    margin: 0 0 10px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.is2or-la__list {
    list-style: none;
    margin: 0 0 20px;
    padding: 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.is2or-la__item {
    display: flex;
    align-items: center;
    gap: 14px;
    background: #fff;
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    padding: 14px 16px;
    transition: box-shadow 0.15s;
}

.is2or-la__item:hover {
    box-shadow: 0 2px 10px rgba(0,0,0,.07);
}

.is2or-la__item--current {
    border-color: #4caf50;
    background: #f6fff6;
}

.is2or-la__icon {
    flex-shrink: 0;
    color: #555;
    display: flex;
    align-items: center;
}

.is2or-la__item--current .is2or-la__icon {
    color: #4caf50;
}

.is2or-la__info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 4px;
    min-width: 0;
}

.is2or-la__device-name {
    font-size: 14px;
    font-weight: 600;
    color: #1a1a1a;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.is2or-la__badge {
    display: inline-block;
    background: #4caf50;
    color: #fff;
    font-size: 11px;
    font-weight: 600;
    border-radius: 4px;
    padding: 2px 7px;
    letter-spacing: 0.02em;
    width: fit-content;
}

.is2or-la__last-active {
    font-size: 12px;
    color: #999;
}

.is2or-la__logout-form {
    flex-shrink: 0;
}

.is2or-la__btn-logout {
    background: none;
    border: 1px solid #d0d0d0;
    border-radius: 6px;
    padding: 6px 14px;
    font-size: 13px;
    color: #555;
    cursor: pointer;
    transition: border-color 0.15s, color 0.15s, background 0.15s;
    white-space: nowrap;
}

.is2or-la__btn-logout:hover {
    border-color: #e53935;
    color: #e53935;
    background: #fff5f5;
}

.is2or-la__footer {
    display: flex;
    justify-content: flex-end;
    margin-top: 4px;
}

.is2or-la__btn-logout-all {
    background: none;
    border: 1px solid #e53935;
    border-radius: 7px;
    padding: 8px 18px;
    font-size: 13px;
    font-weight: 600;
    color: #e53935;
    cursor: pointer;
    transition: background 0.15s, color 0.15s;
}

.is2or-la__btn-logout-all:hover {
    background: #e53935;
    color: #fff;
}

.is2or-la__logout-all-form {
    margin: 0;
}

.is2or-la__empty {
    color: #999;
    font-size: 14px;
    margin: 0;
}

@media (max-width: 600px) {
    .is2or-la__item {
        flex-wrap: wrap;
        gap: 10px;
    }
    .is2or-la__device-name {
        white-space: normal;
    }
    .is2or-la__footer {
        justify-content: stretch;
    }
    .is2or-la__btn-logout-all {
        width: 100%;
        text-align: center;
    }
}

.danger-zone {
    border: 1px solid #fecaca;
    background: #fff5f5;
    border-radius: 12px;
    padding: 20px;
}

.danger-zone__header {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

.danger-zone__icon {
    color: #ef4444;
    flex-shrink: 0;
}

.danger-zone__title {
    font-size: 16px;
    font-weight: 700;
    color: #ef4444;
}

.danger-zone__subtitle {
    font-size: 13px;
    color: #6b7280;
    margin: 0 0 16px;
}

.danger-zone__card {
    background: #fff;
    border: 1px solid #eceef1;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
}

.danger-zone__info {
    display: flex;
    flex-direction: column;
    gap: 2px;
}

.danger-zone__action-title {
    font-size: 14px;
    font-weight: 700;
    color: #1a1a1a;
}

.danger-zone__action-desc {
    font-size: 12px;
    color: #9ca3af;
}

/* ---- Tombol Delete Account (override link_meta popupbox) ---- */
.danger-zone__btn {
    background: #ef4444 !important;
    color: #fff !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 10px 20px !important;
    font-size: 13px !important;
    font-weight: 600 !important;
    text-decoration: none !important;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: background 0.15s ease-in-out;
}

.danger-zone__btn:hover {
    background: #dc2626 !important;
    color: #fff !important;
    text-decoration: none !important;
}

/* ---- Mobile ---- */
@media (max-width: 600px) {
    .danger-zone__card {
        flex-direction: column;
        align-items: stretch;
        text-align: left;
    }
    .danger-zone__btn {
        width: 100%;
    }
}
</style>

{/if}
