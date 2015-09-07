<div class="manager manager-tm">
    {if $rp->appointed}
        <div class="userpicture pull-left">{$rp->pic}</div>
        <div class="descr pull-left">
            <div>{$rp->fullname}</div>
            <div><a href="" class="btn-appoint">Назначить другого</a></div>
        </div><div class="clearer"></div>
    {else}
        <div class="userpicture pull-left">{$rp->pic}</div>
        <div class="descr pull-left"><a href="#" class="btn-appoint"">Назначить</a></div><div class="clearer"></div>
    {/if}
</div>