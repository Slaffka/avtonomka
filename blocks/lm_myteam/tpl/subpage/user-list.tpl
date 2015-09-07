<ul id="myteam-user-list" class="unstyled">
    {foreach $users as $id => $member}
        {if $id == 'total'}<hr/>{/if}
        <li class="myteam-user{if $id == $memberid} selected{/if}{if $id == 'total'} list-total{/if}" data-user-id="{$id}">
            {$member->upic}
            <a href="{if $member->url}{$member->url}{else}#user-{$id}{/if}" class="myteam-user-name">
                {$member->fullname}
            </a>
        </li>
    {/foreach}
</ul>
