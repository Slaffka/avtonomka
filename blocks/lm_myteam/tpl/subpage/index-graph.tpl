{include './view-switcher.tpl'}
<link rel="stylesheet"  type="text/css" href="/blocks/lm_kpi/js/rangeinput/rangeinput.css">

    <div id="myteam-kpi" class="clearfix">
        {*<h1>{$title}</h1>*}
        {include './user-list.tpl'}
        <div id="user-diagram-{$id}" class="myteam-user-diagram">
            {if $kpiitems|default:false}
                <div class="metric-details clearfix">
                    <div class="metric-chart" data-size="big" data-caption="{$activekpi->name}" data-fact="{$activekpi->fact}" data-plan="{$activekpi->plan}"
                         data-predict="{$activekpi->predict}"></div>
                    <div class="metric-info">
                        <div class="metric-info-line clearfix">
                            <div class="metric-info-label">План</div>
                            <div class="metric-info-val">
                                <div>{$activekpi->plan|number_format:0:" ":" "} {$activekpi->uom}</div>
                            </div>
                        </div>
                        <div class="metric-info-line clearfix">
                            <div class="metric-info-label">Факт</div>
                            <div class="metric-info-val">
                                <div>
                                    {$activekpi->fact|number_format:0:" ":" "} {$activekpi->uom}
                                    ({($activekpi->fact*100/$activekpi->plan)|ceil}%)
                                </div>
                            </div>
                        </div>
                        <div class="metric-info-line clearfix">
                            <div class="metric-info-label">Прогноз</div>
                            <div class="metric-info-val">
                                <div>
                                    {$activekpi->predict|number_format:0:" ":" "} {$activekpi->uom}
                                    ({($activekpi->predict*100/$activekpi->plan)|round}%)
                                </div>
                            </div>
                        </div>
                        <div class="metric-info-line clearfix">
                            <div class="metric-info-label">По плану в день</div>
                            <div class="metric-info-val">
                                <div>
                                    {$activekpi->dailyplan|number_format:0:" ":" "} {$activekpi->uom}
                                </div>
                            </div>
                        </div>
                        <div class="metric-info-line clearfix">
                            <div class="metric-info-label">Сейчас на 100% надо в день</div>
                            <div class="metric-info-val">
                                <div>{$activekpi->dailyplan_to_fit|number_format:0:" ":" "} {$activekpi->uom}</div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="metric-history-chart"
                             {if $memberid === 'total'}
                                data-userid="{', '|implode:array_keys($users)}"
                            {else}
                                data-userid="{$memberid}"
                            {/if}
                             data-kpiid="{$activekpi->id}"
                                >
                            <svg id="mysvg" height="280" width="350"></svg>
                        </div>
                        <div class="metrics-list clearfix">
                            {foreach from=$kpiitems item=kpiitem}
                                <a href="{$url}&kpi={$kpiitem->id}" class="metric-container">
                                    <span
                                        class="metric-chart
                                        {if $kpiitem->isactive|default:false}metric-active{/if}"
                                        data-caption="{$kpiitem->name}"
                                        data-fact="{$kpiitem->fact}"
                                        data-plan="{$kpiitem->plan}"
                                        data-predict="{$kpiitem->predict}"
                                            >
                                    </span>
                                </a>
                            {/foreach}
                        </div>
                    </div>
                </div>

            {else}
                Для этой должности не предусмотрены KPI
            {/if}
        </div>
    </div>
