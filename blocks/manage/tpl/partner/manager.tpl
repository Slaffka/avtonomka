<div class="manager manager-{$manager->point} {if !$manager->appointed}no-manager{/if}" data-point="{$manager->point}">
    <div class="pointname">{$manager->pointname}</div>
    {if $manager->appointed}
        <div class="userpicture pull-left">{$manager->pic}</div>
        <div class="descr pull-left">
            <div>{$manager->fullname}</div>
            <div><a href="" class="btn-appoint">Назначить другого</a></div>
        </div><div class="clearer"></div>
    {else}
        <div class="userpicture pull-left">{$manager->pic}</div>
        <div class="descr pull-left"><a href="#" class="btn-appoint"">Назначить</a></div><div class="clearer"></div>
    {/if}
</div>