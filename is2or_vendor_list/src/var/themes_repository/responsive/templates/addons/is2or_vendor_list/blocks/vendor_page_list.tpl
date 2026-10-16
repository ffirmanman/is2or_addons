{if !empty($vendors)}

<div class="is2or-vendor-list is2or-vendor-list--grid">

    <div class="is2or-vl__grid">
        {foreach $vendors as $vendor}
        <div class="is2or-vl__card">

            <a href="{"companies.products?company_id=`$vendor.company_id`"|fn_url}" class="is2or-vl__banner-link">
                <div class="is2or-vl__banner">
                    {if $vendor.banner_path}
                        <img src="{$vendor.banner_path}" alt="" class="is2or-vl__banner-img" />
                    {elseif $vendor.logo_path}
                        <img src="{$vendor.logo_path}" alt="" class="is2or-vl__banner-img" />
                    {else}
                        <div class="is2or-vl__banner-placeholder"></div>
                    {/if}
                </div>
            </a>

            <div class="is2or-vl__footer">
                <div class="is2or-vl__logo-wrap">
                    {if $vendor.logo_path}
                        <img src="{$vendor.logo_path}" alt="{$vendor.company|escape}" class="is2or-vl__logo" />
                    {else}
                        <span class="is2or-vl__logo-fallback">{$vendor.company|substr:0:1|upper}</span>
                    {/if}
                </div>

                <div class="is2or-vl__info">
                    <a href="{"companies.products?company_id=`$vendor.company_id`"|fn_url}" class="is2or-vl__name">
                        {$vendor.company|escape}
                    </a>
                    {if $vendor.city}
                        <span class="is2or-vl__location">{$vendor.city|escape}</span>
                    {/if}
                </div>

            </div>
        </div>
        {/foreach}
    </div>
</div>

<style>
.is2or-vendor-list { padding: 28px 0 36px; }
.is2or-vl__header { display: flex; align-items: baseline; gap: 12px; margin-bottom: 24px; }
.is2or-vl__title { font-size: 22px; font-weight: 700; margin: 0; color: #1a1a1a; }
.is2or-vl__see-all { font-size: 14px; color: #5b8c3e; text-decoration: none; font-weight: 600; }

.is2or-vendor-list--grid .is2or-vl__grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
}

.is2or-vendor-list--grid .is2or-vl__card {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #ebebeb;
    background: #fff;
    box-shadow: 0 2px 8px rgba(0,0,0,.05);
    transition: transform 0.2s, box-shadow 0.2s;
}
.is2or-vendor-list--grid .is2or-vl__card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,.12);
}

.is2or-vl__banner-link { display: block; }
.is2or-vendor-list--grid .is2or-vl__banner { width: 100%; height: 140px; overflow: hidden; background: #f0f0f0; }
.is2or-vl__banner-img {
    width: 100%; height: 100%; object-fit: cover;
    transition: transform 0.5s;
}
.is2or-vendor-list--grid .is2or-vl__card:hover .is2or-vl__banner-img { transform: scale(1.05); }
.is2or-vendor-list--grid .is2or-vl__banner-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
}

.is2or-vl__footer { display: flex; align-items: center; gap: 12px; padding: 14px; }

.is2or-vl__logo-wrap {
    flex-shrink: 0; width: 44px; height: 44px; border-radius: 8px;
    border: 1px solid #f0f0f0; background: #fff; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.03);
}
.is2or-vl__logo { width: 100%; height: 100%; object-fit: contain; }
.is2or-vl__logo-fallback { font-size: 20px; font-weight: 700; color: #999; }

.is2or-vl__info { flex: 1; min-width: 0; }
.is2or-vl__name {
    font-size: 14px; font-weight: 700; color: #1a1a1a; text-decoration: none;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
    margin-bottom: 2px;
}
.is2or-vl__name:hover { color: #ea5920; }
.is2or-vl__location { font-size: 12px; color: #777; display: block; }

@media (max-width: 1024px) {
    .is2or-vendor-list--grid .is2or-vl__grid { grid-template-columns: repeat(3, 1fr); }
}
@media (max-width: 768px) {
    .is2or-vendor-list--grid .is2or-vl__grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
}
@media (max-width: 480px) {
    .is2or-vendor-list--grid .is2or-vl__grid { grid-template-columns: repeat(1, 1fr); }
}
</style>

{/if}
