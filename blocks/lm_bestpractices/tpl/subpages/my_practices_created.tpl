{if count($list) <= 0}
<div class="lm_bestpractices_message info">По данному запросу нет результатов!</div>
{else}
<table id="lm_bestpractices_bank" class="table table-bestpractices">
    <thead>
        <tr>
            <th width="25%">Название</th>
            <th width="10%" class="hand table_order" data-order="{if $order['profit'] == 'ASC'}DESC{else}ASC{/if}" data-field="profit">
                Moя прибыль
            </th>
            <th width="25%">
                Тип проекта
            </th>
            <th width="10%">Получили прибиль</th>
            <th width="15%" class="hand table_order" data-order="{if $order['respects'] == 'ASC'}DESC{else}ASC{/if}" data-field="respects">
                Respect’ов
            </th>
            <th width="15%">Статус</th>
        </tr>
    </thead>
    {$ncol = 4}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {$i=1}
    {foreach $list as $practice}
        <tr>
            <td class="hand show_detailpage{if $i % 2 == 0} dark{/if}" data-detailpage="show_practice_info" data-id="{$practice->id}">{$practice->name}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->profit|number_format:0:",":"‘"|default:0} Руб.</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->typeStr}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->introduceOtherProfit}</td>



            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->respects|number_format:0:",":"‘"|default:0}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>
            {if $practice->state == 0}
            На согласовании.
            {elseif $practice->state == 1}
            Согласована.
            {elseif $practice->state == 2}
            Отклонена.
            <span>(посмотреть комментарий)</span>
            {/if}

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