<h2>
    Информация о партнере #{$partnerid}
    {if $iscapartnersview}
        <div class="pull-right close-instance">
            <button class="btn"><i class="icon icon-checkmark"></i> OK</button>
        </div>
    {/if}

    {if $iscapedit}
        <div class="pull-right delete-instance">
            <a class="btn btn-link">удалить</a>
        </div>
    {/if}
</h2>

<div class="span6 pull-left">
    {$select_companies}
    {$line}
    {*{$select_region}*}
    <div style="margin-bottom: 5px">
        <span class="lbl"><b>{$field_region->lbl}:</b></span>
        <span class="val" style="margin-left:20px">
        <a href="#" id="field-region" class="editable-text" data-title=""
           data-emptytext="Выберите регион..." data-source="/blocks/manage/?__ajc=partners::avail_regions"
           data-type="select" {if $field_region->val}data-value="{$field_region->val}"{/if}></a>
        </span>
    </div>

    {$programs_panel}
</div>


<div class="span5 pull-right">
    {foreach from=$managers item=manager}
        {$manager}
    {/foreach}
    {$comment}
</div>
<div class="clearer"></div>
<hr>

<div class="active-staff staff-wrapper">
    <div class="lm-section-header">
        <div class="clearfix">
            <div class="lm-section-name pull-left">Сотрудники</div>
            <div class="lm-section-actions pull-right">
                {if $iscapedit}
                    <button class="btn btn-default pull-left" data-panel="lm-staff-settings">
                        <i class="icon icon-gear"></i>
                    </button>
                    <button class="btn btn-default pull-left" data-panel="lm-staff-filter">
                        <i class="fa fa-filter"></i>
                    </button>
                {/if}
            </div>
        </div>
        <div class="lm-section-panels">
            <div class="lm-section-panel lm-staff-settings clearfix hide" id="lm-staff-settings">
                <label for="menufield-cohortid">
                    Записывать в группу {$cohorts_list}
                </label>
            </div>
            <div class="lm-section-panel lm-staff-filter clearfix hide" id="lm-staff-filter">
                <div class="staff-filter-selects clearfix">
                    <label for="menufield-cohortid">
                        ТМ {$tm_list}
                    </label>

                    <label for="menufield-cohortid">
                        Тренер {$trainer_list}
                    </label>

                    <label for="menufield-cohortid">
                        ТТ {$tt_list}
                    </label>
                </div>
                <label class="checkbox">
                    <input type="checkbox" class="filter-element" data-type="archive"> Только из архива
                </label>
            </div>
        </div>
    </div>

    <div class="staff-panel">
        <div class="muted-placeholder {if $stafferlist}hide{/if}">Нет сотрудников</div>
        <div class="staff-list clearfix">
            {$stafferlist}
        </div>

        {if $iscapedit}
            <a href="#addstaff-modal" class="btn btn-link btn-addstaff" data-toggle="modal">
                <i class="icon icon-plus"></i> Добавить сотрудника
            </a>
        {/if}
    </div>
    <div class="clearer"></div>
</div>


<div class="">
    <div class="lm-section-header">
        <div class="clearfix">
            <div class="lm-section-name pull-left">Торговые точки</div>
        </div>
    </div>

    <div class="ttlist">
        {foreach from=$ttlist item=tt}
            {$tt}
        {/foreach}
    </div>
    <div class="clearer"></div>

    <a href="#addtt-modal" class="btn btn-link btn-addtt" data-toggle="modal">
        <i class="icon icon-plus"></i> Добавить торговую точку
    </a>
</div>

<div>
    <div class="lm-section-header">
        <div class="clearfix">
            <div class="lm-section-name pull-left">Результаты по тренингу</div>
            <div class="lm-section-actions pull-right">
                {$programs_list}
            </div>
        </div>
    </div>
    <div class="result-panel">{$resultpanel}</div>
</div>