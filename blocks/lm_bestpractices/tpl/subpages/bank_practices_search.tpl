<div class="lm_bestpractices-search lm_bestpractices_block" id="lm_bestpractices-search">
    <div class="lm_bestpractices-search_line">
        <input type="text" name="search_term" id="search_term" class="form-control"
            placeholder="Всего практик: 1`122. Что ищем?" value="{$search_term}">
        <input type="text" name="search_profit_from" id="search_profit_from" class="form-control"
            placeholder="выгода от" value="{$search_profit_from}">
        <span>-</span>
        <input type="text" name="search_profit_to" id="search_profit_to" class="form-control"
            placeholder="выгода до" value="{$search_profit_to}">
        <span>Руб.</span>
        <a href="#" id="lm_bestpractices-search-btn" class="lm_bestpractices-search-btn btn">Подобрать</a>
    </div>
    <div class="lm_bestpractices-search-detail">
        <div class="lm_bestpractices-search-detail-details">
            <div class="lm_bestpractices-search-detail-details-last-days">
                Новинки за последние:
                <a href="#" class="btn" data-days="7">7 дней</a>
                <a href="#" class="btn" data-days="30">30 дней</a>
                <a href="#" class="btn" data-days="90">90 дней</a>
            </div>
            <a href="#" class="lm_bestpractices-search-detail-btn btn">Расширенный поиск</a>
        </div>
        <div class="lm_bestpractices-filters">

            <div class="lm_bestpractices-filter" data-filter="type">
                <span class="lm_bestpractices-filter-label lm_bestpractices-block-label">Тип проекта</span>
                <div class="lm_bestpractices-filter-values lm_bestpractices-block">
                    {foreach $types as $type}
                    <span class="lm_bestpractices-filter-value lm_bestpractices-block-value full-row" data-value="{$type->id}">
                        {$type->name}
                    </span>
                    {/foreach}
                </div>
            </div>
            <div class="lm_bestpractices-filter" data-filter="position">
                <span class="lm_bestpractices-filter-label lm_bestpractices-block-label">Должность</span>
                <div class="lm_bestpractices-filter-values lm_bestpractices-block">
                    {foreach $positions as $position}
                    <span class="lm_bestpractices-filter-value lm_bestpractices-block-value full-row" data-value="{$position->id}">
                        {$position->name}
                    </span>
                    {/foreach}
                </div>
            </div>
            <div class="lm_bestpractices-filter" data-filter="area">
                <span class="lm_bestpractices-filter-label lm_bestpractices-block-label">Регион</span>
                <div class="lm_bestpractices-filter-values lm_bestpractices-block">
                    {foreach $areas as $area}
                    <span class="lm_bestpractices-filter-value lm_bestpractices-block-value half-row" data-value="{$area->id}">
                        {$area->name}
                    </span>
                    {/foreach}
                </div>
            </div>

            <div class="lm_bestpractices-filter">
                <span class="lm_bestpractices-filter-label lm_bestpractices-block-label">Создан</span>
                <div class="lm_bestpractices-filter-values">
                    <label>С</label>
                    <input type="text" name="search_data_from" id="search_data_from" class="form-control"
                        placeholder="выбрать..." value="{$search_data_from}">
                    <label>По</label>
                    <input type="text" name="search_data_do" id="search_data_do" class="form-control"
                        placeholder="выбрать..." value="{$search_data_do}">
                </div>
            </div>

        </div>
        <div class="lm_bestpractices-selected-filters">
            <!-- <span class="btn" data-value="9">
                КФО
                <i class="icon-cross"></i>
            </span>
            <span class="btn" data-value="9">
                КФО
                <i class="icon-cross"></i>
            </span>
            <span class="btn" data-value="9">
                КФО
                <i class="icon-cross"></i>
            </span> -->
        </div>
    </div>
</div>
