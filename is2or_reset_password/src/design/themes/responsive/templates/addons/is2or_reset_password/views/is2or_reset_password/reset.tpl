<div class="ty-recover-password">
    <form name="is2or_reset_password_form"
          action="{"is2or_reset_password.reset"|fn_url}"
          method="post"
    >
        <div class="ty-control-group">
            <label class="ty-login__filed-label ty-control-group__label cm-required"
                   for="password1"
            >{__("password")}</label>
            <input type="password"
                   id="password1"
                   name="user_data[password1]"
                   class="ty-input-text cm-trim"
                   autocomplete="new-password"
                   value=""
                   style="width:100%"
            />
        </div>

        <div class="ty-control-group">
            <label class="ty-login__filed-label ty-control-group__label cm-required"
                   for="password2"
            >{__("confirm_password")}</label>
            <input type="password"
                   id="password2"
                   name="user_data[password2]"
                   class="ty-input-text cm-trim"
                   autocomplete="new-password"
                   value=""
                   style="width:100%"
            />
        </div>

        <div class="buttons-container">
            {include file="buttons/button.tpl"
                     but_text=__("is2or_reset_password.save_new_password")
                     but_meta="ty-btn__primary cm-submit"
                     but_name="dispatch[is2or_reset_password.reset]"
            }
        </div>

    </form>
</div>

{capture name="mainbox_title"}{__("is2or_reset_password.page_title")}{/capture}