{if $groups|default:false}
    {foreach from=$groups item=group}
        <h4>{$group->name}</h4>
        <ul>
        {foreach from=$group->items item=item}
            <li><a href="{$item->url}">{$item->name}</a></li>
        {/foreach}
        </ul>
    {/foreach}
{/if}