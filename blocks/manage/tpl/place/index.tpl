<div class="instancelist-wrapper" {if $place_details}style="display: none"{/if}>
    <ul class="nav nav-pills nav-placetypes pull-left">
        <li {if $type == 'class'}class="active"{/if} data-type="class">
            <a href="/blocks/manage/?_p=places&type=class">Классы</a>
        </li>
        <li {if $type == 'tt'}class="active"{/if} data-type="tt">
            <a href="/blocks/manage/?_p=places&type=tt">Торговые точки</a>
        </li>
    </ul>

    <div class="pull-right">
        {if $type == 'class'}
            <button class="btn btn-addplace"><i class="icon icon-plus"></i> Добавить класс</button>
        {elseif $type == 'tt'}
            <button class="btn btn-addplace"><i class="icon icon-plus"></i> Добавить торговую точку</button>
        {/if}
    </div>

    <div style="clear:both">
        <div class="controls controls-row">
            <div class="input-append places-search">
                {if $type == 'class'}
                    <input id="search-places" class="input-xxlarge" type="text" placeholder="Введите название класса или партнера" >
                {elseif $type == 'tt'}
                    <input id="search-places" class="input-xxlarge" type="text" placeholder="Введите код ТТ или название партнера" >
                {/if}
                <span class="add-on"><i class="icon icon-search"></i> </span>
            </div>
        </div>
    </div><div id="calendar" class="hide"></div>

    <div class="placestable-wrapper" style="clear:both;">{$places_list}</div>
</div>
<div id="placeinfo" class="placeinfo instanceinfo">{$place_details}</div>


{* В этой штуке будем хранить переменные, которые будем использовать в js *}
<div id="vars" data-placeid="{$placeid}"></div>