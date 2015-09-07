<div class="table-responsive">
<table class="table table-myteam table-practics">
    <thead>
        <tr>
            <th>ФИО</th>
            <th colspan="9">Личные проекты</th>
            <th colspan="3">Проекты из банка практик</th>
            <th>Респектов</th>
        </tr>
    </thead>
    <tr>
        <td></td>
        <td colspan="3">Отправлено</td>
        <td colspan="3">Принято</td>
        <td colspan="3">Внедрено</td>
        <td colspan="3">Внедрено</td>
        <td></td>
    </tr>
    {foreach from=$users item=user}
        <tr>
            <td>
                {$user->upic}
                <a class="table-user" href="/user/view.php?id={$user->id}">
                    {$user->fullname}
                </a>
            </td>
            <td>6</td>
            <td>527</td>
            <td>31</td>
            <td>5</td>
            <td>384</td>
            <td>27</td>
            <td>2</td>
            <td>692</td>
            <td>15</td>
            <td>3</td>
            <td>138</td>
            <td>46</td>
            <td>573</td>
        </tr>
    {/foreach}

</table>
</div>
