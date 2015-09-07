<table id="lm_settinggoals_supervisor" class="table table-settinggoals">
    <thead>
        <tr>
            <th colspan="2">{include './timer.tpl'}</th>
            {foreach $kpi_list as $name}
                <th class="kpi">
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
                </th>
            {/foreach}
        </tr>
    </thead>
    <tr class="less_details">
        <td class="more border-right-silver">
            <a href="{$back_page_url}" class="less-details">Менее подробно</a>
        </td>
        <td class="border-right-silver">Всего:</td>
        {foreach $kpi_list as $id => $name}
            <td class="border-right-silver">{$total_kpis[$name]->value|number_format:1:",":" "|default:"0"} {$total_kpis[$name]->uom}</td>
        {/foreach}
    </tr>
    {$ncol = 2 + count($kpi_list)}
    {$width = 75 / (count($kpi_list)+1)}
    <tr class="space"><td colspan="{$ncol}" style="height: 2px;"></td></tr>
    {foreach $tp_list as $tp}
        <tr>
            <td rowspan=3 class="username">
                <span class="top" {*style="background-color: gray;"*}>
                   {$tp->ava}  {$tp->name}
                </span>
            </td>
            <td class="border-right-silver" style="width: {$width}%">План:</td>
            {foreach $kpi_list as $id => $name}
                <td class="border-right-silver" style="width: {$width}%">
                    {if $tp->kpis[$name]->uom == 'шт'}
                        {$tp->kpis[$name]->plan|number_format:0:",":" "|default:"0"}
                    {else}
                        {$tp->kpis[$name]->plan|number_format:1:",":" "|default:"0"}
                    {/if}
                    {$tp->kpis[$name]->uom}
                </td>
            {/foreach}
        </tr>
        <tr class="fact">
            <td class="border-right-silver">Факт:</td>
            {foreach $kpi_list as $id => $name}
                <td class="border-right-silver">
                    {if $tp->kpis[$name]->uom == 'шт'}
                        {$tp->kpis[$name]->fact|number_format:0:",":" "|default:"0"}
                    {else}
                        {$tp->kpis[$name]->fact|number_format:1:",":" "|default:"0"}
                    {/if}
                    {$tp->kpis[$name]->uom}
                    &nbsp; {$tp->kpis[$name]->fact_percent|default:"0"}%</td>
            {/foreach}
        </tr>
        <tr class="predict">
            <td class="border-right-silver">Прогноз:</td>
            {foreach $kpi_list as $id => $name}
                <td class="predicted-value border-right-silver
                {if $tp->kpis[$name]->predict_percent < 90}
                    critical_gradient
                {elseif $tp->kpis[$name]->predict_percent >= 90 && $tp->kpis[$name]->predict_percent <= 99}
                    normal_gradient
                {elseif $tp->kpis[$name]->predict_percent >= 100}
                    perfectly_gradient
                {/if}
                " data-percent="{$tp->kpis[$name]->predict_percent}">
                    {if $tp->kpis[$name]->uom == 'шт'}
                        {$tp->kpis[$name]->predict|number_format:0:",":" "|default:"0"}
                    {else}
                        {$tp->kpis[$name]->predict|number_format:1:",":" "|default:"0"}
                    {/if}
                    {$tp->kpis[$name]->uom} &nbsp; {$tp->kpis[$name]->predict_percent|default:"0"}%
                </td>
            {/foreach}
        </tr>
        <tr class="space"><td colspan="{$ncol}" style="height: 2px;"></td></tr>
    {foreachelse}
    <tr>
        <td colspan="6">нет даных</td>
    </tr>
    {/foreach}
</table>
<div class="text-center">
{include './pager.tpl'}
</div>
{include './footer.tpl'}


