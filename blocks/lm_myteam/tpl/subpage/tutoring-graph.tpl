{include './view-switcher.tpl'}

    <div id="lm_myteam-tutoring">
        {if empty($users)}
            <div class="alert">
                Нет данных
            </div>
        {else}
            <h1>{$title}</h1>
            {include './user-list.tpl'}
            <div id="myteam-tutoring-diagram">
            </div>
            <div id="myteam-tutoring-diagrams" class="clearfix">
                {foreach $users as $id => $member}
                    <div id="user-diagram-{$id}" class="myteam-user-diagram">
                        {if empty($member->programs)}
                            <div class="alert">
                                Не назначено ни одной программы
                            </div>
                        {else}
                            <div class="chart"
                                 data-programs="{htmlspecialchars(json_encode(array_values($member->programs)))}"
                                 data-total="{json_encode($member->total)}"
                                    >
                                Строится график...
                            </div>
                            <div class="user-name">
                                {$member->fullname}
                            </div>
                        {/if}
                    </div>
                {/foreach}
            </div>
        {/if}
    </div>
