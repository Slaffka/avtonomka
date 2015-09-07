<table id="lm_settinggoals_supervisor" class="table table-bordered table-settinggoals">
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
    {$ncol = 2 + count($kpi_list)}
    {$width = 75 / (count($kpi_list)+1)}
    <tr class="space"><td colspan="{$ncol}" style="height: 2px;"></td></tr>
    {foreach $superviser_list as $sv}
        <tr>
            <td rowspan=3 class="username" >
                <div class="blockusername">
                    <div class="settinggoals-pennant{if $sv->is_top} top{/if}">
                        <span>{$sv->top_count}</span>
                    </div>
                    <div class="top fio">
                        <div class="ava">{$sv->ava}</div>
                        <div class="userfio">{$sv->name}</div>
                    </div>
                    {if $sv->lead}
                        <div class="more">
                            <a href="{$page_url}&showdetails={$sv->posid}" >Подробнее</a>
                        </div>
                    {/if}
                </div>
            </td>
            <td style="width: {$width}%">План:</td>
            {foreach $kpi_list as $id => $name}
                <td style="width: {$width}%">
                    {if $sv->kpis[$name]->uom == 'шт'}
                        {$sv->kpis[$name]->plan|number_format:0:",":" "|default:"0"}
                    {else}
                        {$sv->kpis[$name]->plan|number_format:1:",":" "|default:"0"}
                    {/if}
                    {$sv->kpis[$name]->uom}
                </td>
            {/foreach}
        </tr>

        <tr class="fact">
            <td>Факт:</td>
            {foreach $kpi_list as $id => $name}
                <td>
                    {if $sv->kpis[$name]->uom == 'шт'}
                        {$sv->kpis[$name]->fact|number_format:0:",":" "|default:"0"}
                    {else}
                        {$sv->kpis[$name]->fact|number_format:1:",":" "|default:"0"}
                    {/if}

                    {$sv->kpis[$name]->uom} &nbsp; {$sv->kpis[$name]->fact_percent|default:"0"}%
                </td>
            {/foreach}
        </tr>
        <tr class="predict">
            <td>Прогноз:</td>
            {foreach $kpi_list as $id => $name}
                <td  class="predicted-value
                    {if $sv->kpis[$name]->predict_percent < 90}
                        critical_gradient
                    {elseif $sv->kpis[$name]->predict_percent >= 90 && $sv->kpis[$name]->predict_percent <= 99}
                        normal_gradient
                    {elseif $sv->kpis[$name]->predict_percent >= 100}
                        perfectly_gradient
                    {/if}
                " data-percent="{$sv->kpis[$name]->predict_percent}">
                    {if $sv->kpis[$name]->uom == 'шт'}
                        {$sv->kpis[$name]->predict|number_format:0:",":" "|default:"0"}
                    {else}
                        {$sv->kpis[$name]->predict|number_format:1:",":" "|default:"0"}
                    {/if}

                    {$sv->kpis[$name]->uom} &nbsp; {$sv->kpis[$name]->predict_percent|default:"0"}%
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
