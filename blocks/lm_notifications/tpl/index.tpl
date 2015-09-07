<div class="{$prefix}_notification_list">
    {if $notifications}
        {foreach $notifications as $notification}
            <div class="{$prefix}_notification {$prefix}_type_{$notification.type}">
                <a href="{$notification.url}" class="{$prefix}_message">{$notification.message}</a>
            </div>
        {/foreach}
    {/if}
</div>
