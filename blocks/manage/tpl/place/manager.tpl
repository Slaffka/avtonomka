<div class="manager manager-{$u->type}"  data-type="{$u->type}">
    {if $u->appointed}
        <div class="userpicture pull-left">{$u->pic}</div>
        <div class="descr pull-left">
            <div>{$u->fullname}</div>
            <div><a href="" class="btn-appoint">Назначить другого</a></div>
        </div><div class="clearer"></div>
    {else}
        <div class="userpicture pull-left">{$u->pic}</div>
        <div class="descr pull-left"><a href="#" class="btn-appoint"">Назначить</a></div><div class="clearer"></div>
    {/if}
</div>