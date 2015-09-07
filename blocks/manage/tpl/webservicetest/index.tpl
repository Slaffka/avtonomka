{if $errors|default:false}

    {foreach from=$errors key=companyid item=regions}
        {foreach from=$regions key=regionid item=partners}
            {$first = true}
            {foreach from=$partners key=partnerid item=partnername}
                {if $first}
                    <h2><a href="/blocks/manage/?_p=partners&id={$partnerid}" target="_blank">{$partnername}</a> #c{$companyid}, #r{$regionid}:</h2>
                    <ul>
                    {$first = false}
                {else}
                    <li>
                        <a href="/blocks/manage/?_p=partners&id={$partnerid}" target="_blank">{$partnername}</a>
                    </li>
                {/if}
            {/foreach}
            </ul>
        {/foreach}
    {/foreach}
{/if}