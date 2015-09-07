<table class="table table-settinggoals">
    <thead>
        <tr>
            <th colspan="2" width="50%"></th>
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
        </tr>
    </thead>
    {$ncol = 2 + count($kpi_list)}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    <tr class="less_details color-transparent">
        <td rowspan="2" colspan="2" class="user more border-right-silver"
            {$user_direction = '-'}
            {foreach $kpi_list as $id => $name}
                {if $user_direction == '-'}
                    {$user_direction = $total_kpis[$name]->direction}
                {else}
                    {if $user_direction != $total_kpis[$name]->direction}
                        {$user_direction = ''}
                    {/if}
                {/if}
                {/foreach}
                {if $user_direction == 'down'}
                 style="background: rgba(253, 203, 204, 0.9)"
                {elseif $user_direction == 'up'}
                 style="background: rgba(190, 232, 146, 0.9)"
                {/if}>
                <div class="blockusername">
                    <div class="top fio">
                        <a href="{$back_page_url}">Менее подробно</a>
                        <div class="auto">Цель:</div>
                        <div class="auto" style="top: 75px;">Факт:</div>
                    </div>
                </div>
        </td>
        {foreach $kpi_list as $id => $name}
            <td style="background: rgba(255, 255, 255, 0.9);">
                {if $total_kpis[$name]->plan}
                    {if $total_kpis[$name]->uom == 'шт' or $total_kpis[$name]->uom == 'руб'}
                        {$total_kpis[$name]->plan|number_format:0:",":" "}
                    {else}
                        {$total_kpis[$name]->plan|number_format:1:",":" "}
                    {/if}
                {else}
                    0
                {/if}
            </td>
        {/foreach}
    </tr>
    <tr>
        {foreach $kpi_list as $id => $name}
            <td class="dark" {if $total_kpis[$name]->direction == 'down'}
                 style="background: rgba(253, 203, 204, 0.9)"
                {elseif $total_kpis[$name]->direction == 'up'}
                 style="background: rgba(190, 232, 146, 0.9)"
                {/if}>
                {if $total_kpis[$name]->fact}
                    {if $total_kpis[$name]->uom == 'шт' or $total_kpis[$name]->uom == 'руб'}
                        {$total_kpis[$name]->fact|number_format:0:",":" "}
                    {else}
                        {$total_kpis[$name]->fact|number_format:1:",":" "}
                    {/if}
                {else}
                    0
                {/if}
                {if $total_kpis[$name]->direction == 'down'}
                    <div class="rating-down"></div>
                {elseif $total_kpis[$name]->direction == 'up'}
                    <div class="rating-up"></div>
                {else}
                    <div class="rating-stop"></div>
                {/if}
            </td>
        {/foreach}
    </tr>
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {foreach $tp_list as $tp}
        <tr class="color-transparent">
            <td rowspan="2" colspan="2" class="username user"
            {$user_direction = '-'}
            {foreach $kpi_list as $id => $name}
                {if $user_direction == '-'}
                    {$user_direction = $tp->kpis[$name]->direction}
                {else}
                    {if $user_direction != $tp->kpis[$name]->direction}
                        {$user_direction = ''}
                    {/if}
                {/if}
                {/foreach}
                {if $user_direction == 'down'}
                 style="background: rgba(253, 203, 204, 0.9)"
                {elseif $user_direction == 'up'}
                 style="background: rgba(190, 232, 146, 0.9)"
                {/if}>
                <span class="top">
                    {$tp->name}<br />
                    <span style="text-align: left; font-size: 12px">{$tp->address}</span>
                </span>
                <div class="auto">Цель:</div>
                <div class="auto" style="top: 75px;">Факт:</div>
            </td>
            {foreach $kpi_list as $id => $name}
                <td class="border-right-silver" style="background: rgba(255, 255, 255, 0.9);">
                    {if $tp->kpis[$name]->correct}
                        {if $tp->kpis[$name]->uom == 'шт' or $tp->kpis[$name]->uom == 'руб'}
                            {$tp->kpis[$name]->correct|number_format:0:",":" "}
                        {else}
                            {$tp->kpis[$name]->correct|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                </td>
            {/foreach}
        </tr>
        <tr>
            {foreach $kpi_list as $id => $name}
                <td class="border-right-silver dark" {if $tp->kpis[$name]->direction == 'down'}
                 style="background: rgba(253, 203, 204, 0.9)"
                {elseif $tp->kpis[$name]->direction == 'up'}
                 style="background: rgba(190, 232, 146, 0.9)"
                {/if}>
                {if $tp->kpis[$name]->fact}
                    {if $tp->kpis[$name]->uom == 'шт' or $tp->kpis[$name]->uom == 'руб'}
                        {$tp->kpis[$name]->fact|number_format:0:",":" "}
                    {else}
                        {$tp->kpis[$name]->fact|number_format:1:",":" "}
                    {/if}
                {else}
                    0
                {/if}
                {if $tp->kpis[$name]->direction == 'down'}
                    <div class="rating-down"></div>
                {elseif $tp->kpis[$name]->direction == 'up'}
                    <div class="rating-up"></div>
                {else}
                    <div class="rating-stop"></div>
                {/if}
                </td>
            {/foreach}
        </tr>
        <tr class="space"><td colspan="{$ncol}"></td></tr>
    {foreachelse}
    <tr>
        <td colspan="6">нет даных</td>
    </tr>
    {/foreach}
</table>