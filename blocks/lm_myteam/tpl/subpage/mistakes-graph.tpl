{include './view-switcher.tpl'}
<div id="myteam-mistakes" class="clearfix">
    <h1>{$title}</h1>
    {include './user-list.tpl'}
    <div id="myteam-mistakes-diagrams">
        {foreach $users as $id => $member}
            <div id="user-diagram-{$id}" class="myteam-user-diagram">
                {if $member->progress}
                    <div class="data"
                         data-progress="{htmlentities(json_encode($member->progress))}"
                         data-total="{htmlentities(json_encode($member->total))}"
                            >
                        Строится график...
                    </div>
                {else}
                    <div class="alert">
                        Нет данных
                    </div>
                {/if}
            </div>
        {/foreach}
    </div>
</div>