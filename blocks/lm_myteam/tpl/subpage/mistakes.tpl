{include './view-switcher.tpl'}
<div id="myteam-mistakes">
    <h1>{$title}</h1>

    <div id="lm_myteam-mistakes">
        {if empty($categories) || empty($users)}
            <div class="alert">
                Нет данных
            </div>
        {else}
            <table id="lm_myteam_table-mistakes" class="table table-myteam table-mistakes">
                <thead>
                <tr>
                    <th>ФИО</th>
                    <th colspan="{count($categories)}">Категория</th>
                    <th>Итого кол-во ошибок</th>
                </tr>
                </thead>
                <tr>
                    <td></td>
                    {foreach $categories as $id => $category}
                        <td class="course">
                            {$category}
                        </td>
                    {/foreach}
                    <td></td>
                </tr>

                {foreach $users as $id => $user}

                    <tr>
                        {if $id === 'total'}
                            <td><b>Итого:</b></td>
                        {else}
                            <td>
                                {$user->upic}
                                <a href="/user/view.php?id={$user->id}">
                                    {$user->fullname}
                                </a>
                            </td>
                        {/if}
                        {foreach $categories as $id => $category}
                            <td>
                                {if isset($user->progress[$id])}
                                    {$user->progress[$id]}
                                {else}
                                    —
                                {/if}
                            </td>
                        {/foreach}
                        <td>
                            {if $user->total}
                                {$user->total}
                            {else}
                                —
                            {/if}
                        </td>
                    </tr>
                {/foreach}

            </table>
        {/if}
    </div>
</div>
