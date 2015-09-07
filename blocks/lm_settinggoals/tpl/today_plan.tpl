<table id="lm_settinggoals_supervisor" class="table table-settinggoals table-bordered">
    <thead>
        <tr>
            <th width="30%"></th>
            {$n = count($kpi_list)}
            {$percent = 50 / $n}
            {foreach $kpi_list as $name}
                <th class="kpi" width="{$percent}%">
                    {if ($name=='ФА')}
                        <i class="kpi-info"
                           data-toggle="tooltip"
                           data-placement="bottom"
                           title="Фокусный Ассортимент"
                                >
                            i
                        </i>
                    {/if}
                    {$name}
                    {if $auto_list[$name]->uom}
                    <br/>{$auto_list[$name]->uom}
                    {/if}
                </th>
            {/foreach}
            <th width="20%">
                {if $is_tp and $status == 1}
                    <a href="#" data-positionid="{$place_list[0]->positionid}" data-time="{$tptime}" class="btn settinggoals_send_agreement">Отправить на согласование</a>
                {/if}
                {if $is_sv and $status == 2}
                    <a href="{$page_url}&do_action=rejectcorrection&posid={$posid}" class="btn">Отправить на корректировку</a>
                {/if}
            </th>
        </tr>
        <tr>
            <th class="level2">Автоматический расчёт на {$plan_date|date_format:"d.m.Y"}</th>
            {foreach $kpi_list as $name}
                <th class="radius0">
                    {if $auto_list[$name]->value}
                        {if $auto_list[$name]->uom == 'шт' or $auto_list[$name]->uom == 'руб'}
                            {$auto_list[$name]->value|number_format:0:",":" "}
                        {else}
                            {$auto_list[$name]->value|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                </th>
            {/foreach}
            <th class="radius0">
                {include './timer.tpl'}
                {if $show_comments or $is_sv}
                    {include './comments.tpl'}
                {/if}
            </th>
        </tr>
        <tr>
            <th class="level2">Итог моей корректировки</th>
            {foreach $kpi_list as $name}
                <th class="radius0
                    {if $correct_list[$name]->direction == 'down'}
                        critical
                    {elseif $correct_list[$name]->direction == 'up'}
                        perfectly
                    {/if}
                ">
                    {if $correct_list[$name]->value}
                        {if $correct_list[$name]->uom == 'шт' or $correct_list[$name]->uom == 'руб'}
                            {$correct_list[$name]->value|number_format:0:",":" "}
                        {else}
                            {$correct_list[$name]->value|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                    {if $correct_list[$name]->direction == 'down'}
                        <div class="rating-down"></div>
                    {elseif $correct_list[$name]->direction == 'up'}
                        <div class="rating-up"></div>
                    {else}
                        <div class="rating-stop"></div>
                    {/if}
                </th>
            {/foreach}
            <th class="radius0">
                {$phase->comment}
            </th>
        </tr>
    </thead>
    {$ncol = 2 + count($kpi_list)}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {$i = 1}
    {foreach $place_list as $place}
        <tr class="{if $i%2}dark{/if}">
            <td class="level2">
                {$place->name}<br />
                <span style="text-align: left; font-size: 12px">{$place->address}</span>
            </td>
            {foreach $kpi_list as $id => $name}
                {if $correct_list[$name]->uom == 'руб'}
                {$round=0}
                {else}
                {$round=1}
                {/if}
                {if $place->kpis[$name]->editable == 1}
                    <td {if $is_tp} data-kpiid="{$place->kpis[$name]->id}" data-planid="{$place->planid}" {/if}class="{if $is_tp}settinggoals_edit_kpi{/if}">
                        {if $place->kpis[$name]->plan != $place->kpis[$name]->current}
                        <span style="text-decoration: line-through;">
                            {if $place->kpis[$name]->old}
                                {$place->kpis[$name]->old|number_format:{$round}:",":" "}
                            {else}
                                {$place->kpis[$name]->plan|number_format:{$round}:",":" "}
                            {/if}
                        </span>
                        {/if}
                        {if $place->kpis[$name]->current}
                            {$place->kpis[$name]->current|number_format:{$round}:",":" "}
                        {else}
                            0
                        {/if}
                    </td>
                {elseif $place->kpis[$name]->editable == 2}
                    <td {if $is_tp} data-kpiid="{$place->kpis[$name]->id}" data-planid="{$place->planid}" class="settinggoals_edit_kpi_count"{/if}>
                        {$place->kpis[$name]->current|default:"0"} SKU
                        <div class="settinggoals_checkbox_kpi_count
                        settinggoals_checkbox_kpi_count_chekbox
                        settinggoals_checkbox_kpi_count_chekbox_{if $place->kpis[$name]->checked}checked{else}unchecked{/if} {if $is_tp}settinggoals_remove_kpi_count {/if}"></div>
                    </td>
                {else}
                    <td>
                        {if $place->kpis[$name]->current}
                            {$place->kpis[$name]->current|number_format:{$round}:",":" "}
                        {else}
                            0
                        {/if}
                    </td>
                {/if}
            {/foreach}
            {if !$place->comment}
                <td></td>
            {else}
                <td data-planid="{$place->planid}" class="settinggoals_show_comment">{$place->comment|truncate:30:"...":true}</td>
            {/if}
        </tr>
        <tr class="space"><td colspan="{$ncol}"></td></tr>
        {$i = $i + 1}
    {/foreach}
</table>
<div class="text-center">
    {include './pager.tpl'}
    {if $status == 1}
    <div class="pagination" style="display: inline-block;">
        <div>
            <ul>
                <li>
                    <a href="{$page_url}&do_action=setoutlets"
                       style="width: 190px;">добавить точку</a>
                </li>
            </ul>
        </div>
    </div>
    {/if}
</div>
{if $is_tp}
    {include './today_plan_status.tpl'}
{/if}
{include './footer.tpl'}