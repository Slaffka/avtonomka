{include './view-switcher.tpl'}
<div id="lm_myteam-tutoring">
    {if empty($programs) || empty($users)}
        <div class="alert">
            Нет данных
        </div>
    {else}
    <div class="table-responsive">
        <table id="lm_myteam_table-tutoring" class="table table-myteam table-tutoring">
        <thead>
        <tr>
            <th>ФИО</th>
            <th colspan="{count($programs)}">Дистанционные курсы</th>
            <th>Обученность</th>
        </tr>
        </thead>
        <tr>
            <td></td>
            {foreach $programs as $id => $program}
                <td class="course">
                    <a href="/blocks/manage/?__ajc=lm_myteam::course_statistic&program={$id}">
                        {$program->name}
                    </a>
                </td>
            {/foreach}
            <td></td>
        </tr>

        {*<tr>
            <td>Вес</td>
            <td>15%</td>
            <td>20%</td>
            <td>30%</td>
            <td>20%</td>
            <td>15%</td>
            <td></td>
        </tr>*}

        {foreach $users as $id => $user}

                <tr>
                    {if $id === 'total'}
                        <td class="table-user-total">Всего</td>
                    {else}
                        <td>
                            {$user->upic}
                            <a class="table-user" href="/user/view.php?id={$user->id}">
                                {$user->fullname}
                            </a>
                        </td>
                    {/if}
                    {foreach $programs as $id => $program}
                        <td>
                            {if isset($user->programs[$id]->progress)}
                                {round($user->programs[$id]->progress, 1)}%
                            {else}
                                —
                            {/if}
                        </td>
                    {/foreach}
                    <td>{round($user->total, 1)}% <span class="tutoring-level level-normal"></span></td>
                </tr>
        {/foreach}

        </table>
        </div>
    {/if}
</div>