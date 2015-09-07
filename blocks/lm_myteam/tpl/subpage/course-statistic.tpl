<div class="modal-header">
    <a class="close" data-dismiss="modal">Закрыть</a>
    <h4 class="modal-title">{$title}</h4>
</div>

<table>
    <thead>
    <tr>
        <th>ФИО</th>
        <th>Время</th>
        <th>Монеты</th>
        <th>Ошибки <br /><a class="course-errors" href="{$detail_url}">Подробнее</a></th>
    </tr>
    </thead>
    <tbody>
        {foreach $members as $member}
            <tr>
                <td class="member">
                    {$member->upic}
                    <a href="/user/view.php?id={$member->id}">
                        {$member->fullname}
                    </a>
                </td>
                <td class="duration value">
                    {if is_null($member->attempt->duration)}
                        —
                    {else}
                        {floor($member->attempt->duration/3600)}:{floor($member->attempt->duration/60)%60}:{$member->attempt->duration%60}
                    {/if}
                </td>
                <td class="coins value">
                    {if empty($member->attempt->coins)}
                        —
                    {else}
                        {$member->attempt->coins}
                    {/if}
                </td>
                <td class="mistakes value">
                    {if is_null($member->attempt->mistakes)}
                        —
                    {else}
                        {$member->attempt->mistakes}
                    {/if}
                </td>
            </tr>
        {/foreach}
    </tbody>
    <tfoot>
        <tr class="region-avg">
            <th>В среднем по городу</th>
            <td class="duration value">
                {floor($city_duration_avg/3600)}:{floor($city_duration_avg/60)%60}:{$city_duration_avg%60}
            </td>
            <td class="coins value">{$city_coins_avg|round:2}</td>
            <td class="mistakes value">{$city_mistakes_avg}</td>
        </tr>
        <tr class="region-avg">
            <th>В среднем по региону</th>
            <td class="duration value">
                {floor($region_duration_avg/3600)}:{floor($region_duration_avg/60)%60}:{$region_duration_avg%60}
            </td>
            <td class="coins value">{$region_coins_avg|round:2}</td>
            <td class="mistakes value">{$region_mistakes_avg}</td>
        </tr>
        <tr class="country-avg">
            <th>В среднем по России</th>
            <td class="duration value">
                {floor($total_duration_avg/3600)}:{floor($total_duration_avg/60)%60}:{$total_duration_avg%60}
            </td>
            <td class="coins value">{$total_coins_avg|round:2}</td>
            <td class="mistakes value">{$total_mistakes_avg}</td>
        </tr>
    </tfoot>
</table>