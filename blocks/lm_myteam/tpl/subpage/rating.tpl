{include './view-switcher.tpl'}
<div class="table-responsive">
<table bgcolor=black border=0 cellpadding=2 cellspacing=1 class="table table-myteam table-rating">
    <thead>
        <tr>
            <th bgcolor=white >ФИО</th>
            <th colspan="6">Рейтинг</th>
            <th colspan="3" clаss="separator-left">Текущее место</th>
        </tr>
    </thead>
    <tr>
        <td></td>
        {foreach $dates as $date}
            <td>{$date}</td>
        {/foreach}
        <td class="separator-left">В городе</td>
        <td>В регионе</td>
        <td>В стране</td>
    </tr>
    {foreach from=$users item=user}
        <tr>
            <td>
                {$user->upic}
                <a class="table-user" href="/user/view.php?id={$user->id}">
                    {$user->fullname}
                </a>
            </td>
            {$m = 0}
            {foreach $user->avg as $avg}
                {if !$m}
                    {$old_avg = $user->avg_old}
                {/if}
                {$avg = $avg|round:1}
                {if !$avg} {$avg = 0} {/if}
                <td>
                    {$avg}
                    {if $avg > $old_avg && $avg != 0 }
                        <span class="rating-dynamic rating-dynamic-up"></span><div class="rating-up"></div>
                    {elseif $avg == $old_avg && $avg != 0}
                        <span class="rating-dynamic rating-dynamic-stop"></span><div class="rating-stop"></div>
                    {elseif $avg < $old_avg && $avg != 0}
                        <span class="rating-dynamic rating-dynamic-down"></span><div class="rating-down"></div>
                    {/if}
                </td>
                {$old_avg = $avg}
                {$m = $m + 1}
                {* или class="rating-down", class="rating-stop"*}
            {/foreach}
            {if ($user->id)}
                {if $user->incity}
                    <td class="separator-left">{$user->incity->point} из {$user->incity->total}</td>
                {else}
                    <td class="separator-left">—</td>
                {/if}
                {if $user->inregion}
                    <td>{$user->inregion->point} из {$user->inregion->total}</td>
                {else}
                    <td>—</td>
                {/if}
                {if $user->incountry}
                    <td>{$user->incountry->point} из {$user->incountry->total}</td>
                {else}
                    <td>—</td>
                {/if}
            {else}
                <td class="separator-left">—</td>
                <td>—</td>
                <td>—</td>
            {/if}
        </tr>
    {/foreach}


</table>
</div>