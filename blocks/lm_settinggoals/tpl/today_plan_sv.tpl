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
            <th colspan="2" class="level2">Автоматический расчёт на {$plan_date|date_format:"d.m.Y"}</th>
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
            <th class="radius0">{include './timer.tpl'}</th>
        </tr>
        <tr>
            <th colspan="2" class="level2">Итого по сотрудникам</th>
            {foreach $kpi_list as $name}
                <th class="radius0
                    {if $user->kpis[$name]->direction == 'down'}
                        critical
                    {elseif $user->kpis[$name]->direction == 'up'}
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
                    {$correct_list[$name]->uom}
                    {if $correct_list[$name]->direction == 'down'}
                        <div class="rating-down"></div>
                    {elseif $correct_list[$name]->direction == 'up'}
                        <div class="rating-up"></div>
                    {else}
                        <div class="rating-stop"></div>
                    {/if}
                </th>
            {/foreach}
            <th class="level2"></th>
        </tr>
    </thead>
    {$ncol = 2 + count($kpi_list)}
    <tr class="space"><td colspan="{$ncol}"></td></tr>
    {foreach $user_list as $user}
        <tr class="color-transparent">
            <td rowspan="2" colspan="2" class="username dark">
                <div class="blockusername">
                    <div class="top fio" style="margin-left:0">
                        <div class="ava">{$user->ava}</div>
                        <div class="userfio">{$user->lastname} {$user->firstname}</div>
                    </div>
                    <div class="auto" style="float: right; padding: 17px;">Авто:</div>
                </div>
            </td>
            {foreach $kpi_list as $id => $name}
                <td style="background: rgba(255, 255, 255, 0.9);">
                    {if $user->kpis[$name]->plan}
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
            <td rowspan="2" class="dark user_status" data-userid="{$user->id}">
                {include './today_plan_sv_status.tpl'}
            </td>
        </tr>
        <tr class="color-transparent">
            {foreach $kpi_list as $id => $name}
                <td class="dark">
                   {* {if $user->kpis[$name]->old && $user->kpis[$name]->correct != $user->kpis[$name]->plan}
                        <span style="text-decoration: line-through;">
                            {$user->kpis[$name]->old}
                        </span>
                    {/if}*}
                    {if $user->kpis[$name]->correct}
                        {if $user->kpis[$name]->uom == 'шт' or $user->kpis[$name]->uom == 'руб'}
                            {$user->kpis[$name]->correct|number_format:0:",":" "}
                        {else}
                            {$user->kpis[$name]->correct|number_format:1:",":" "}
                        {/if}
                    {else}
                        0
                    {/if}
                    {if $user->kpis[$name]->direction == 'down'}
                        <div class="rating-down"></div>
                    {elseif $user->kpis[$name]->direction == 'up'}
                        <div class="rating-up"></div>
                    {else}
                        <div class="rating-stop"></div>
                    {/if}
                </td>
            {/foreach}
        </tr>
        <tr class="space"><td colspan="{$ncol}"></td></tr>
    {/foreach}
</table>
<div class="text-center">
    {include './pager.tpl'}
</div>
{include './footer.tpl'}
<script type="text/javascript">
$(document).ready(function() {
    var update = function() {
        setTimeout(function() {
            $.ajax({
                dataType: "json",
                url: '/blocks/manage/?__ajc=lm_settinggoals::get_sv_state',
                data: {},
                success: function(data) {
                    for (var id in data) {
                        $('.user_status[data-userid="' + id + '"]').html(data[id]);
                    };
                    update();
                }
            });
        }, 3000);
    }
    update();
});
</script>