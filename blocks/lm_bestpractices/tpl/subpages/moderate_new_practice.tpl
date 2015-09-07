{if count($list) <= 0}
<div class="lm_bestpractices_message info">По данному запросу нет результатов!</div>
{else}
<table id="lm_bestpractices_bank" class="table table-bestpractices">
    <thead>
        <tr>
            <th width="15%">Автор</th>
            <th width="15%">Название</th>
            <th width="15%">Тип проекта</th>
            <th width="15%" class="hand table_order" data-order="{if $order['profit'] == 'ASC'}DESC{else}ASC{/if}" data-field="profit">
                Прибыль
            </th>
            <th width="20%">Согласование</th>
            <th width="20%">Действия</th>
        </tr>
    </thead>
    {$ncol = 4}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {$i=1}
    {foreach $list as $practice}
        <tr>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->authorName}</td>
            <td class="hand show_detailpage{if $i % 2 == 0} dark{/if}" data-detailpage="show_practice_info" data-id="{$practice->id}">{$practice->name}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->typeStr}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>
                {$practice->profit|number_format:0:",":"‘"|default:0} Руб.<br />
                <a href="#" class="btn">Проверить</a>
            </td>
            <td{if $i % 2 == 0} class="dark"{/if}>
                <a href="#" class="btn show_detailpage" data-detailpage="show_practice_agreement" data-id="{$practice->id}">Попытка ({$practice->moderateCount|default:0})</a>
            </td>
            <td{if $i % 2 == 0} class="dark"{/if}>
                <a href="#" class="btn">Редактировать</a>
            </td>
        </tr>
        <tr class="space"><td colspan="{$ncol}"></td></tr>
    {$i=$i+1}
    {/foreach}
</table>
<div class="text-center">
    {include '../pager.tpl'}
</div>
{/if}