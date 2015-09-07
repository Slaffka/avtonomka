<table id="lm_settinggoals_supervisor" class="table table-settinggoals">
    <thead>
        <tr>
            <th colspan="2" width="30%"></th>
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
            <th width="20%"></th>
        </tr>
        <tr>
            <th colspan="2" class="level2">Выставленная на {$plan_date|date_format:"d.m.Y"} цель</th>
            {foreach $kpi_list as $name}
                <th>
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
            <th class="radius0"></th>
        </tr>
        <tr>
            <th colspan="2" class="level2">Итого по сотрудникам</th>
            {foreach $kpi_list as $name}
                <th
                {if $fact_list[$name]->direction == 'down'}
                 style="background: rgba(253, 203, 204, 0.9)"
                {elseif $fact_list[$name]->direction == 'up'}
                 style="background: rgba(190, 232, 146, 0.9)"
                {/if}>
                    {if $fact_list[$name]->value}
                        {if $fact_list[$name]->uom == 'шт' or $fact_list[$name]->uom == 'руб'}
                            {$fact_list[$name]->value|number_format:0:",":" "}
                        {else}
                            {$fact_list[$name]->value|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                    {if $fact_list[$name]->direction == 'down'}
                        <div class="rating-down"></div>
                    {elseif $fact_list[$name]->direction == 'up'}
                        <div class="rating-up"></div>
                    {else}
                        <div class="rating-stop"></div>
                    {/if}
                </th>
            {/foreach}
            <th class="radius0"></th>
        </tr>
    </thead>
    {$ncol = 2 + count($kpi_list)}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {foreach $user_list as $user}
        <tr class="color-transparent">
            <td rowspan="2" colspan="2" class="username dark"
            {$user_direction = '-'}
            {foreach $kpi_list as $id => $name}
                {if $user->state == 4}
                    {if $user_direction == '-'}
                        {$user_direction = $user->kpis[$name]->direction}
                    {else}
                        {if $user_direction != $user->kpis[$name]->direction}
                            {$user_direction = ''}
                        {/if}
                    {/if}
                {/if}
             {/foreach}
                    {if $user_direction == 'down'}
                     style="background: rgba(253, 203, 204, 0.9)"
                    {elseif $user_direction == 'up'}
                     style="background: rgba(190, 232, 146, 0.9)"
                    {/if}>
                <div class="blockusername">
                    <div class="top fio" style="margin-left:0">
                        <div class="ava">{$user->ava}</div>
                        <div class="userfio">{$user->lastname} {$user->firstname}</div>
                    </div>
                    <div style="float: right;">
                        <div class="auto" style="padding: 17px;">Цель:</div>
                        <div class="auto" style="top: 75px; padding: 17px;">Факт:</div>
                    </div>
                </div>
            </td>
            {foreach $kpi_list as $id => $name}
                <td style="background: rgba(255, 255, 255, 0.9);">
                    {if $user->kpis[$name]->plan and $user->state == 4}
                        {if $user->kpis[$name]->uom == 'шт' or $user->kpis[$name]->uom == 'руб'}
                            {$user->kpis[$name]->plan|number_format:0:",":" "}
                        {else}
                            {$user->kpis[$name]->plan|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                </td>
            {/foreach}
            <td rowspan="2" class="dark">
                {if $user->state == 4}
                <a class="btn" href="{$page_url}&showdetails={$user->posid}">Подробнее</a>
                {else}
                Oжидается выгрузка сотрудника.<br />
                Следующая синхронизация портала и<br />
                Чикаго через {$next_update_m} минут {$next_update_s} секунд
                {/if}
            </td>
        </tr>
        <tr class="color-transparent">
            {foreach $kpi_list as $id => $name}
                <td class="dark" {if $user->kpis[$name]->direction == 'down' and $user->state == 4}
                 style="background: rgba(253, 203, 204, 0.9)"
                {elseif $user->kpis[$name]->direction == 'up' and $user->state == 4}
                 style="background: rgba(190, 232, 146, 0.9)"
                {/if}>
                    {if $user->kpis[$name]->fact and $user->state == 4}
                        {if $user->kpis[$name]->uom == 'шт' or $user->kpis[$name]->uom == 'руб'}
                            {$user->kpis[$name]->fact|number_format:0:",":" "}
                        {else}
                            {$user->kpis[$name]->fact|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                    {if $user->state == 4}
                        {if $user->kpis[$name]->direction == 'down'}
                            <div class="rating-down"></div>
                        {elseif $user->kpis[$name]->direction == 'up'}
                            <div class="rating-up"></div>
                        {else}
                            <div class="rating-stop"></div>
                        {/if}
                    {/if}
                </td>
            {/foreach}
        </tr>
        <tr class="space"><td colspan="{$ncol}"></td></tr>
    {/foreach}

</table>