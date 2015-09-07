{include './view-switcher.tpl'}
<div class="table-responsive">
<table class="table table-myteam table-kpi">
    <thead>
    <tr>
        <th>ФИО</th>
        {foreach $user->kpiitems as $kpi}
           <th colspan="3">{$kpi->name}</th>
        {/foreach}
    </tr>
    </thead>
    <tr>
        <td></td>
        {foreach $user->kpiitems as $kpi}
            <td>План</td>
            <td>Факт</td>
            <td class="separator">%</td>
        {/foreach}
    </tr>

    {foreach from=$users item=user}
        <tr>
            <td>
                {$user->upic}
                <a class="table-user" href="/user/view.php?id={$user->id}">
                    {$user->fullname}
                </a>
            </td>
            {if $user->kpiitems|default:false}
                {foreach from=$user->kpiitems item=kpi}
                    <td>{$kpi->plan|number_format:0:" ":" "}</td>
                    <td>{$kpi->fact|number_format:0:" ":" "}</td>
                    <td class="separator">{($kpi->fact*100/$kpi->plan)|ceil}</td>
                {/foreach}
            {else}
                {foreach from=$user->kpiitems item=kpi}
                    <td>нет данных</td>
                    <td>нет данных</td>
                    <td class="separator">нет данных</td>
                {/foreach}
            {/if}

        </tr>
    {/foreach}
</table>
</div>