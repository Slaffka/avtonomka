{if count($list) <= 0}
<div class="lm_bestpractices_message info">По данному запросу нет результатов!</div>
{else}
<table id="lm_bestpractices_bank" class="table table-bestpractices">
    <thead>
        <tr>
            <th width="25%">Название</th>
            <th width="10%" class="hand table_order" data-order="{if $order['profit'] == 'ASC'}DESC{else}ASC{/if}" data-field="profit">
                Прибыль
            </th>
            <th width="10%">
                Внедрялась другими
            </th>
            <th width="10%">Получили прибиль</th>
            <th width="10%" class="hand table_order" data-order="{if $order['respects'] == 'ASC'}DESC{else}ASC{/if}" data-field="respects">
                Respect’ов
            </th>
            <th width="35%" colspan="2">Действия</th>
        </tr>
    </thead>
    {$ncol = 4}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {$i=1}
    {foreach $list as $practice}
        <tr>
            <td class="hand show_detailpage{if $i % 2 == 0} dark{/if}" data-detailpage="show_practice_info" data-id="{$practice->id}">{$practice->name}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->profit|number_format:0:",":"‘"|default:0} Руб.</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->introduceOtherCount|number_format:0:",":"‘"|default:0}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->introduceOtherProfit|number_format:0:",":"‘"|default:0}</td>

            <td{if $i % 2 == 0} class="dark"{/if}>{$practice->respects|number_format:0:",":"‘"|default:0}</td>
            <td{if $i % 2 == 0} class="dark"{/if}>
                <a href="#" class="btn foreign_create_practice" data-id="{$practice->id}">Внедрить у себя</a>
            </td>
            <td{if $i % 2 == 0} class="dark"{/if} style="text-align:center;">
                <a href="#" class="btn remove_favorite" data-id="{$practice->id}"
                    data-container="body" data-placement="left"
                    data-content="Удолить из избранных?<br/><div class='lm_bestpractices_center'><a href='#' class='remove_favorite_y' data-id='{$practice->id}'>Да</a> <a href='#'class='btn remove_favorite_n'>Нет</a></div>">
                    <i class="icon-cross"></i>
                </a>
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