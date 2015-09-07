{if $incity->point || $inregion->point || $incountry->point}
    <div class="my-rating-results clearfix">
        <a href="/blocks/manage/?_p=lm_rating&f=city" class="rating-result">
            <div class="rating-result-lbl">в городе</div>
            <div class="rating-result-val">{$incity->point} | {$incity->total}</div>
        </a>
        <a href="/blocks/manage/?_p=lm_rating&f=region" class="rating-result">
            <div class="rating-result-lbl">в регионе</div>
            <div class="rating-result-val">{$inregion->point} | {$inregion->total}</div>
        </a>
        <a href="/blocks/manage/?_p=lm_rating&f=country" class="rating-result">
            <div class="rating-result-lbl">в стране</div>
            <div class="rating-result-val">{$incountry->point} | {$incountry->total}</div>
        </a>
    </div>

    <div id="my-rating-graff"></div>
{else}
    Нет данных
{/if}