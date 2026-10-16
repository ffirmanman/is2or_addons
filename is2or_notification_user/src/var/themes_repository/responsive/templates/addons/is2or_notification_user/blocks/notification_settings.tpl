{**
 * IS2OR Notification User Block
 * Lets the logged-in user manage which email notifications they receive.
 * notification_user_data is injected by fn_is2or_notification_user_get_block_data().
 *}

{$user_id = $notification_user_data.user_id|default:0}
{$groups  = $notification_user_data.groups|default:[]}
{$prefs   = $notification_user_data.prefs|default:[]}

{if $user_id > 0}

<div class="is2or-nu" id="is2or-notification-user">

    {** ── Header ──────────────────────────────────────────────── **}
    <div class="is2or-nu__header">
        <h2 class="is2or-nu__title">{__("is2or_notification_user.title")}</h2>
        <p class="is2or-nu__subtitle">{__("is2or_notification_user.subtitle")}</p>
    </div>

    {** ── Form ────────────────────────────────────────────────── **}
    <form method="post"
          action="{"is2or_notification_user.save"|fn_url}"
          id="is2or-nu-form">

        <input type="hidden" name="csrf_token" value="{$smarty.session.security_hash}" />

        {** ── Channel header row ──────────────────────────────── **}
        <div class="is2or-nu__table">
            <div class="is2or-nu__table-header">
                <span class="is2or-nu__col-label">{__("is2or_notification_user.col_notification")}</span>
                <span class="is2or-nu__col-channel">{__("is2or_notification_user.col_email")}</span>
            </div>

            {foreach $groups as $group_key => $group_types}

                {** ── Group header ────────────────────────────── **}
                <div class="is2or-nu__group-header">
                    {if $group_key == 'is2or_notification_user.group_purchase'}
                        <svg class="is2or-nu__group-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96C5 16.1 6.1 17 7 17h10v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63H17c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0021.46 4H5.21L4.27 2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>
                        </svg>
                    {elseif $group_key == 'is2or_notification_user.group_promo'}
                        <svg class="is2or-nu__group-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M21.41 11.58l-9-9A2 2 0 0011 2H4a2 2 0 00-2 2v7a2 2 0 00.59 1.42l9 9A2 2 0 0013 22a2 2 0 001.41-.59l7-7A2 2 0 0022 13a2 2 0 00-.59-1.42zM5.5 7A1.5 1.5 0 117 5.5 1.5 1.5 0 015.5 7z"/>
                        </svg>
                    {else}
                        <svg class="is2or-nu__group-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="16" height="16">
                            <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                        </svg>
                    {/if}
                    <span>{__($group_key)}</span>
                </div>

                {** ── Rows within group ────────────────────────── **}
                {foreach $group_types as $type_key => $type_def}
                    {foreach $type_def.channels as $channel}
                        {$field_name = "is2or_notif_`$type_key`_`$channel`"}
                        {$pref_key   = "`$type_key`:`$channel`"}
                        {$is_checked = $prefs[$pref_key]|default:$type_def.default}

                        <div class="is2or-nu__row">
                            <label class="is2or-nu__row-label" for="{$field_name}">
                                {__($type_def.label)}
                            </label>
                            <div class="is2or-nu__col-channel">
                                <label class="is2or-nu__toggle" for="{$field_name}">
                                    <input
                                        type="checkbox"
                                        id="{$field_name}"
                                        name="{$field_name}"
                                        value="1"
                                        {if $is_checked}checked{/if}
                                    />
                                    <span class="is2or-nu__toggle-track">
                                        <span class="is2or-nu__toggle-thumb"></span>
                                    </span>
                                </label>
                            </div>
                        </div>

                    {/foreach}
                {/foreach}

            {/foreach}

        </div>{** /.is2or-nu__table **}

        {** ── Save button ─────────────────────────────────────── **}
        <div class="is2or-nu__footer">
            <button type="submit" class="is2or-nu__btn-save">
                {__("is2or_notification_user.save")}
            </button>
        </div>

    </form>

</div>

{** ═══════════════════════════════ STYLES ═══════════════════════════════ **}
<style>
.is2or-nu {
    font-family: inherit;
    color: #1a1a1a;
    padding: 0 0 12px;
}

.is2or-nu__header {
    margin-bottom: 10px;
}

.is2or-nu__title {
    font-size: 18px;
    font-weight: 700;
    margin: 0 0 2px;
    color: #1a1a1a;
}

.is2or-nu__subtitle {
    font-size: 12px;
    color: #888;
    margin: 0;
}

/* ── Table shell ──────────────────────────────────────────── */
.is2or-nu__table {
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    overflow: hidden;
    margin-bottom: 12px;
}

/* ── Column header ────────────────────────────────────────── */
.is2or-nu__table-header {
    display: flex;
    align-items: center;
    background: #f5f5f5;
    padding: 6px 16px;
    border-bottom: 1px solid #e8e8e8;
}

.is2or-nu__col-label {
    flex: 1;
    font-size: 11px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.is2or-nu__col-channel {
    width: 72px;
    text-align: center;
    font-size: 11px;
    font-weight: 600;
    color: #666;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

/* ── Group header ─────────────────────────────────────────── */
.is2or-nu__group-header {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 6px 16px;
    background: #fafafa;
    border-bottom: 1px solid #e8e8e8;
    font-size: 12px;
    font-weight: 700;
    color: #333;
}

.is2or-nu__group-icon {
    color: #555;
    flex-shrink: 0;
}

/* ── Row ──────────────────────────────────────────────────── */
.is2or-nu__row {
    display: flex;
    align-items: center;
    padding: 7px 16px;
    border-bottom: 1px solid #f0f0f0;
    transition: background 0.12s;
    max-height: 36px;
}

.is2or-nu__row:last-child {
    border-bottom: none;
}

.is2or-nu__row:hover {
    background: #fafafa;
}

.is2or-nu__row-label {
    flex: 1;
    font-size: 13px;
    color: #1a1a1a;
    cursor: default;
    margin-top: 8px;
    font-weight: 500 !important;
}

/* ── Toggle switch ────────────────────────────────────────── */
.is2or-nu__toggle {
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    position: relative;
}

.is2or-nu__toggle input[type="checkbox"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}

.is2or-nu__toggle-track {
    display: inline-block;
    width: 40px;
    height: 22px;
    background: #ccc;
    border-radius: 24px;
    position: relative;
    transition: background 0.2s;
    margin-top: -11px;
}

.is2or-nu__toggle input:checked + .is2or-nu__toggle-track {
    background: #ff5f2e; /* Theme color */
}

.is2or-nu__toggle-thumb {
    position: absolute;
    top: 3px;
    left: 3px;
    width: 16px;
    height: 16px;
    background: #fff;
    border-radius: 50%;
    box-shadow: 0 1px 3px rgba(0,0,0,.25);
    transition: left 0.2s;
}

.is2or-nu__toggle input:checked + .is2or-nu__toggle-track .is2or-nu__toggle-thumb {
    left: 21px;
}

/* ── Footer / save ────────────────────────────────────────── */
.is2or-nu__footer {
    display: flex;
    justify-content: flex-end;
}

.is2or-nu__btn-save {
    background: #ff5f2e;
    color: #fff;
    border: none;
    border-radius: 8px;
    padding: 8px 24px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.15s, box-shadow 0.15s;
}

.is2or-nu__btn-save:hover {
    background: #e6531f;
    box-shadow: 0 2px 8px rgba(255,95,46,.25);
}

/* ── Mobile ───────────────────────────────────────────────── */
@media (max-width: 600px) {
    .is2or-nu__footer {
        justify-content: stretch;
    }
    .is2or-nu__btn-save {
        width: 100%;
        text-align: center;
    }
}
</style>

{/if}