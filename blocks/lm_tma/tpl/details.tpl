<table class="table table-bordered tma">
    <tr class="title">
        <td style="width: 32%;">Описание</td>
        <td style="width: 28%;">Время</td>
        {if !$isadmin}
            <td style="width: 20%;">Прогресс</td>
            <td style="width: 20%;">Награда</td>
        {else}
            <td style="width: 20%;">Награда</td>
            <td style="width: 20%;">Действия</td>
        {/if}
    </tr>
    {$i = 1}
    {foreach $tmas as $tma}
        <tr class="{if $i%2}odd {else}even {/if}">
            <td class="action" >
                <div class="spoiler_body_hide">
                    {$tma->title|truncate:50}
                </div>
                <div class="spoiler_body">
                    <div class="editing title_action" data-pk="{$tma->id}" data-name="title">
                        {$tma->title|truncate:50}
                    </div>
                    <div class="editing descr_action" data-pk="{$tma->id}" data-name="descr">
                        {$tma->descr}
                    </div>
                </div>
            </td>
            <td class="time">
                <div class="spoiler_body_hide">
                    <p style="">{$tma->start} - {$tma->end}</p>
                </div>
                <div class="spoiler_body">
                    <p class="title">{$tma->start} - {$tma->end}</p>
                    <br>
                    <p class="descr">Осталось:</p>
                    {*ДНИ*}
                    <div class="timer">
                        {if $tma->days[1]}{$tma->days[0]}{else}0{/if}
                    </div>
                    <div class="timer">
                        {if $tma->days[1]}{$tma->days[1]}{else}{$tma->days[0]}{/if}
                    </div>
                    &nbsp;&nbsp;
                    {*ЧАСЫ*}
                    <div class="timer">
                        {if $tma->hour[1]}{$tma->hour[0]}{else}0{/if}
                    </div>
                    <div class="timer">
                        {if $tma->hour[1]}{$tma->hour[1]}{else}{$tma->hour[0]}{/if}
                    </div>
                    <div class="clearer"></div>
                    <div class="caption">Дней <span>&nbsp;</span> Часов</div>
                </div>
            </td>

            <td  {if $isadmin}style="display: none;"{/if}>
                <div class="spoiler_body_hide">
                    {if !$tma->plan}
                        Нет данных
                    {else}
                        {$result = $tma->fact / $tma->plan * 100}

                        <div class="meter">
                            <span style="width: {$result}%"></span>
                        </div>
                        <div class="result-percent animated_result"> {$result}%</div>
                    {/if}
                </div>
                <div class="spoiler_body">
                    <div class="svg radial-progress" data-fact="{$tma->fact|default:0}" data-plan="{$tma->plan|default:0}">
                        <a href="{$url}&kpi={$kpi->id}" class="kpi-container">
                            <span class="metric-chart"
                                  data-caption="{$kpi->name}" data-fact="{$kpi->fact}" data-plan="{$kpi->plan}"
                                  data-predict="{$kpi->predict}">
                            </span>
                        </a>
                        <svg height="200px" width="200px">
                            <circle class="radial-progress-background" r="" cx="100px" cy="100px" fill="transparent" stroke-dasharray="0" stroke-dashoffset="0"></circle>
                            <circle class="radial-progress-cover" r="" cx="100px" cy="100px" fill="transparent" stroke-dasharray="0"  stroke-dashoffset="0"></circle>
                            <circle class="radial-progress-center" r="" cx="100px" cy="100px" fill="transparent" stroke-dasharray="0" stroke-dashoffset="0"></circle>
                        </svg>
                        <div style="font-size: 26px; font-weight: bold;   margin-top: -120px;">
                            {$tma->fact|default:0} из {$tma->plan|default:0}
                        </div>
                    </div>

                    {$ost = $tma->plan - $tma->fact}
                    <p style=" font-size: 18px;   margin-top: -49px;   font-weight: bold;   position: relative;     padding: 6px;">Осталось: {if $ost < 0}0{else}{$ost}{/if}</p>
                </div>
            </td>

            <td>
                <div class="no_money_tma" >
                    <span class="editing reward_action" data-pk="{$tma->id}" data-name="reward">{$tma->reward}</span>
                    <a class="spoiler_link"><i class="icon-chevron-down"></i></a>
                </div>
            </td>

            {if $isadmin}
                <td class="act">
                    <div class="spoiler_body">
                        <p><a href="#" class="btn edit" data-tmaid="{$tma->id}">&nbsp;&nbsp; Редактировать &nbsp;</a></p>
                        <p><a href="/blocks/manage/?__ajc=lm_tma::get_list_users&id={$tma->id}" class="btn all_users all_users{$i}" data-tmaid="{$tma->id}">Все пользователи</a></p>
                        <p><a href="/blocks/manage/?__ajc=lm_tma::get_list_all_tt&id={$tma->id}" class="btn all_tts all_tts{$i}" data-tmaid="{$tma->id}" >&nbsp; Торговые точки &nbsp;</a></p>
                    </div>
                </td>
            {/if}
        </tr>
        {$i = $i + 1}
    {/foreach}
</table>
