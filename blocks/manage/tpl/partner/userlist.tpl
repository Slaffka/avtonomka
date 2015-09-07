<ul class="picker__list" role="listbox">
    {foreach from=$users item=user}
        <li class="picker__list-item" data-pick="0" role="option">{$user->pic} {$user->fullname}</li>
    {/foreach}
</ul>