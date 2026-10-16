{if $runtime.controller === 'profiles'}
    {if $runtime.mode === 'add'}
    <div class="ty-account-benefits">
        {__("text_profile_benefits") nofilter}
    </div>

    {elseif $runtime.mode == 'update'}
        <div class="ty-account-detail">
            <div class="ty-account-detail__description">
                <p>{__("profile_update_description")}</p>
                <br>
                <strong>{__("security_recommendations")}:</strong>
                <ul>
                    <li>{__("security_rec_dictionary_words")}</li>
                    <li>{__("security_rec_spelled_backwards")}</li>
                    <li>{__("security_rec_sequences")}</li>
                    <li>{__("security_rec_personal_info")}</li>
                </ul>
            </div>

            <div class="ty-account-detail__image-wrapper">
                <div class="ty-profile-image-container">
                    <div class="ty-profile-image" id="profile_image_preview">
                        {* Jika ada data URL gambar user, tampilkan img. Jika tidak, tampilkan placeholder 'img' *}
                        {if $user_data.profile_image_url}
                            <img src="{$user_data.profile_image_url}" alt="{__("profile_picture")}">
                        {else}
                            <span class="ty-profile-image__text">{__("image_abbr")|default:"img"}</span>
                        {/if}
                    </div>
                    <label for="profile_picture_upload" class="ty-profile-image__camera-btn" style="display: flex; justify-content: center; align-items: center; font-size: 18px;">
                        <i class="ty-icon-image"></i>
                    </label>
                </div>
                
                <div class="ty-profile-image__upload-action">
                    <label for="profile_picture_upload">
                        <i class="ty-icon-upload"></i> {__("change_profile_picture")}
                    </label>
                    <input type="file" id="profile_picture_upload" name="profile_picture" form="profile_form" accept="image/*" onchange="previewProfileImage(event)" style="display: none;">
                </div>
            </div>

            <div class="ty-account-detail__help">
                <div class="ty-account-detail__help-title">
                    <i class="ty-icon-help-circle"></i> {__("need_help")}
                </div>
                <p>{__("profile_update_help_text")}</p>
                <a href="{"contact_us"|fn_url}" class="ty-btn ty-btn__secondary ty-btn-support">{__("contact_support")}</a>
            </div>
            
            <style>
                .ty-profile-image__upload-action label {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    color: #ee4d3f;
                    font-weight: 600;
                    font-size: 14px;
                    cursor: pointer;
                    background: none;
                    border: none;
                    padding: 0;
                }

                .ty-profile-image__upload-action i {
                    font-size: 14px;
                }

                /* ==== Need Help box ==== */
                .ty-account-detail__help {
                    background-color: #f5f5f5;
                    border-radius: 8px;
                    padding: 16px;
                    margin-top: 24px;
                }

                .ty-account-detail__help-title {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    color: #ee4d3f;
                    font-weight: 700;
                    font-size: 13px;
                    text-transform: uppercase;
                    margin-bottom: 8px;
                }

                .ty-account-detail__help-title i {
                    font-size: 14px;
                }

                .ty-account-detail__help p {
                    color: #4f4f4f;
                    font-size: 13px;
                    margin-bottom: 14px;
                }

                .ty-btn-support {
                    display: block;
                    width: 100%;
                    text-align: center;
                    background-color: #fff;
                    border: 1px solid #ee4d3f;
                    color: #ee4d3f;
                    border-radius: 6px;
                    padding: 10px;
                    font-weight: 600;
                    text-decoration: none;
                }

                .ty-btn-support:hover {
                    background-color: #ee4d3f;
                    color: #fff;
                }
            </style>

            <script>
                (function($) {
                    var is2orPhotoChanged = false;

                    window.previewProfileImage = function (event) {
                        var reader = new FileReader();
                        reader.onload = function () {
                            var output = document.getElementById('profile_image_preview');
                            output.innerHTML = '<img src="' + reader.result + '" alt="{__("profile_picture")}">';
                        };
                        if (event.target.files[0]) {
                            reader.readAsDataURL(event.target.files[0]);
                            is2orPhotoChanged = true; // tandai bahwa user mengganti foto
                        }
                    };

                    $(document).on('ajaxComplete', function (event, xhr, settings) {
                        if (
                            is2orPhotoChanged &&
                            settings.url &&
                            settings.url.indexOf('dispatch=profiles.update') !== -1
                        ) {
                            is2orPhotoChanged = false;
                            window.location.reload();
                        }
                    });
                })(Tygh.$);
            </script>
        </div>
    {/if}
{/if}