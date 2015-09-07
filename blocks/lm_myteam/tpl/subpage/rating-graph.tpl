{include './view-switcher.tpl'}
<div id="lm_myteam-rating">
    {if empty($dates) || empty($users)}
        <div class="alert">
            Нет данных
        </div>
    {else}
        <h1>{$title}</h1>
        {include './user-list.tpl'}
        <div id="myteam-rating-diagram">
        </div>
        <div id="myteam-rating-diagrams" class="clearfix">
            {foreach $users as $id => $member}
                <div id="user-diagram-{$id}" class="myteam-user-diagram">
                    <div class="my-rating-results clearfix">
                        <a href="/blocks/manage/?_p=lm_rating&f=city" class="rating-result">
                            <div class="rating-result-lbl">в городе</div>
                            <div class="rating-result-val">{$member->incity->point} | {$member->incity->total}</div>
                        </a>
                        <a href="/blocks/manage/?_p=lm_rating&f=region" class="rating-result">
                            <div class="rating-result-lbl">в регионе</div>
                            <div class="rating-result-val">{$member->inregion->point} | {$member->inregion->total}</div>
                        </a>
                        <a href="/blocks/manage/?_p=lm_rating&f=country" class="rating-result">
                            <div class="rating-result-lbl">в стране</div>
                            <div class="rating-result-val">{$member->incountry->point} | {$member->incountry->total}</div>
                        </a>
                    </div>

                    <div
                        class="chart"
                        data-dates="{htmlspecialchars(json_encode(array_values($dates)))}"
                        data-values="{htmlspecialchars(json_encode(array_values($member->avg)))}"
                            >
                            Строится график...
                    </div>
                    <div class="user-name">
                        {$member->fullname}
                    </div>
                </div>
            {/foreach}
        </div>
    {/if}
</div>
