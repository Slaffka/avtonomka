<strong class="lm_rating_warning">Внимание: вы соревнуетесь среди новичков</strong>

{$subpages}

{$filter}

<table id="lm_rating_table" cellspacing="10">
    <tr>
        <th colspan="2" class="empty"> </th>
        <th colspan="5">Параметры</th>
        <th class="empty"> </th>
    </tr>
    <tr>
        <th>Место</th>
        <th>ФИО</th>
        <th>ТТ с полным MML</th>
        <th>АКБ</th>
        <th>Эффективность визитов</th>
        <th>Соблюдение маршрута</th>
        <th>% возвратов</th>
        <th>Средневзвешенный балл</th>
    </tr>
    {foreach $rating_table as $i => $row}
    <tr {if !$i}class="selected"{/if}>
        <td>{$row.place}</td>
        <td>{$row.name}</td>
        <td>{$row.tt}</td>
        <td>{$row.akb}</td>
        <td>{$row.efficiency}</td>
        <td>{$row.sm}</td>
        <td>{$row.percent}</td>
        <td>{$row.avg}</td>
    </tr>
    {/foreach}
</table>