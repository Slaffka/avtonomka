{if $kpiitems|default:false}
    <div class="kpi-wrapper clearfix">
    {foreach from=$kpiitems item=kpi}
        <a href="{$url}&kpi={$kpi->id}" class="kpi-container">
            <span class="metric-chart"
                  data-caption="{$kpi->name}" data-fact="{$kpi->fact}" data-plan="{$kpi->plan}"
                  data-predict="{$kpi->predict}"></span>
        </a>
    {/foreach}
    </div>
{else}
    Нет данных
{/if}