{if !empty($vendors)}
{$is2or_vendor_list_title=__("is2or_vendor_list.selected_sellers")}

<div class="is2or-vendor-list is2or-vendor-list--scroller" data-ca-is2or-vendor-list>

    <div class="is2or-vl__header">
        {if $use_block_title|default:"N" == "Y" && $title|trim}
            {$is2or_vendor_list_title=$title}
            <h2 class="is2or-vl__title">{$is2or_vendor_list_title nofilter}</h2>
        {else}
            <h2 class="is2or-vl__title">{$is2or_vendor_list_title nofilter}</h2>
            <a href="{"companies.catalog"|fn_url}" class="is2or-vl__see-all">{__("is2or_vendor_list.see_all")}</a>
        {/if}
    </div>

    <div class="is2or-vl__slider-wrap">

        <button type="button" class="is2or-vl__arrow ut2-icon-arrow_back_black" aria-label="{__("previous")}" data-ca-is2or-vendor-prev></button>

        <div class="is2or-vl__track-outer">
            <div class="is2or-vl__track">

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

        <button type="button" class="is2or-vl__arrow ut2-icon-arrow_forward_black" aria-label="{__("next")}" data-ca-is2or-vendor-next></button>

    </div>
</div>

<style>
.is2or-vendor-list { padding: 28px 0 36px; }
.is2or-vl__header { display: flex; align-items: baseline; gap: 12px; margin-bottom: 20px; justify-content: space-between; }
.is2or-vl__title { font-size: 24px; font-weight: 700; margin: 0; color: #1a1a1a; }
.is2or-vl__see-all { font-size: 14px; color: #ea5920; text-decoration: none; }
.is2or-vl__see-all:hover { text-decoration: underline; }

.is2or-vendor-list--scroller .is2or-vl__slider-wrap { display: flex; align-items: center; gap: 8px; }
.is2or-vendor-list--scroller .is2or-vl__track-outer { flex: 1; overflow: hidden; }
.is2or-vendor-list--scroller .is2or-vl__track { display: flex; gap: 16px; transition: transform 0.35s cubic-bezier(.4,0,.2,1); }

.is2or-vendor-list--scroller .is2or-vl__arrow {
    flex-shrink: 0; width: 38px; height: 38px; border-radius: 50%;
    border: 1px solid #e0e0e0; background: #fff; font-size: 24px;
    cursor: pointer; color: #444; display: flex; align-items: center;
    justify-content: center; box-shadow: 0 1px 4px rgba(0,0,0,.08);
    transition: background .15s; padding: 0 0 2px 0; line-height: 1;
}
.is2or-vendor-list--scroller .is2or-vl__arrow:hover { background: #f5f5f5; color: #ea5920; }
.is2or-vendor-list--scroller .is2or-vl__arrow:disabled { opacity: 0.35; cursor: default; }

.is2or-vendor-list--scroller .is2or-vl__card {
    flex: 0 0 calc(25% - 12px); min-width: 200px;
    border-radius: 14px; overflow: hidden;
    border: 1px solid #ebebeb; background: #fff;
    box-shadow: 0 1px 6px rgba(0,0,0,.06);
    transition: box-shadow .2s;
}
.is2or-vendor-list--scroller .is2or-vl__card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.11); }

.is2or-vl__banner-link { display: block; }
.is2or-vendor-list--scroller .is2or-vl__banner { width: 100%; height: 118px; overflow: hidden; background: #e0e8d8; }
.is2or-vl__banner-img { width: 100%; height: 100%; object-fit: cover; }
.is2or-vendor-list--scroller .is2or-vl__banner-placeholder {
    width: 100%; height: 100%;
    background: linear-gradient(135deg, #d4e8c2 0%, #a8cc85 100%);
}

.is2or-vl__footer { display: flex; align-items: center; gap: 10px; padding: 12px 14px; }

.is2or-vl__logo-wrap {
    flex-shrink: 0; width: 46px; height: 46px; border-radius: 10px;
    border: 1px solid #eee; background: #f8f8f8; overflow: hidden;
    display: flex; align-items: center; justify-content: center;
}
.is2or-vl__logo { width: 100%; height: 100%; object-fit: contain; }
.is2or-vl__logo-fallback { font-size: 20px; font-weight: 700; color: #999; }

.is2or-vl__info { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 3px; }
.is2or-vl__name {
    font-size: 14px; font-weight: 600; color: #1a1a1a; text-decoration: none;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block;
}
.is2or-vl__name:hover { color: #ea5920; }
.is2or-vl__location { font-size: 12px; color: #999; display: block; }
</style>

<script>
(function () {
    var lists = document.querySelectorAll('[data-ca-is2or-vendor-list]');

    for (var i = 0; i < lists.length; i++) {
        init(lists[i]);
    }

    function init(list) {
        if (!list || list.getAttribute('data-ca-is2or-vendor-ready') === 'Y') {
            return;
        }

        var track = list.querySelector('.is2or-vl__track');
        var outer = list.querySelector('.is2or-vl__track-outer');
        var btnPrev = list.querySelector('[data-ca-is2or-vendor-prev]');
        var btnNext = list.querySelector('[data-ca-is2or-vendor-next]');

        if (!track || !outer || !btnPrev || !btnNext) {
            return;
        }

        list.setAttribute('data-ca-is2or-vendor-ready', 'Y');

        var cards = track.querySelectorAll('.is2or-vl__card');
        var idx = 0;
        var isRtl = document.dir === 'rtl' || document.documentElement.dir === 'rtl';

        function cardWidth() { return cards[0] ? cards[0].offsetWidth + 16 : 220; }
        function visible() { return Math.max(1, Math.floor(outer.offsetWidth / cardWidth())); }
        function maxIdx() { return Math.max(0, cards.length - visible()); }

        function update() {
            var offset = idx * cardWidth();
            track.style.transform = isRtl
                ? 'translateX(' + offset + 'px)'
                : 'translateX(-' + offset + 'px)';
            btnPrev.disabled = idx === 0;
            btnNext.disabled = idx >= maxIdx();
        }

        btnNext.addEventListener('click', function () { if (idx < maxIdx()) { idx++; update(); } });
        btnPrev.addEventListener('click', function () { if (idx > 0) { idx--; update(); } });
        window.addEventListener('resize', function () { if (idx > maxIdx()) { idx = maxIdx(); } update(); });
        update();
    }
}());
</script>

{/if}
