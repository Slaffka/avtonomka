{if $pager->count > 1}
    {$pages_around = 3}
    {$visible_pages = $pages_around * 2 + 1}
    <div class="pagination" style="display: inline-block;">
        <div style="">
            <ul>
                {if $pager->count <= $visible_pages}
                    {$pager_start = 1}
                {else}
                    {$pager_start = $pager->current - $pages_around}
                    {if $pager_start < 1}
                        {$pager_start = 1}
                    {/if}
                    {if $pager_start > ($pager->count - $visible_pages + 1)}
                        {$pager_start = $pager->count - $visible_pages + 1}
                    {/if}
                    {if $pager_start > 1}
                        {if $pager_start > 2}
                            <li class="start">
                                <a href="{$pager->url}&page=1" style="margin-right: 17px;"><<</a>
                            </li>
                        {/if}
                        <li>
                            <a href="{$pager->url}&page={$pager_start - 1}"><</a>
                        </li>
                    {/if}
                {/if}
                {$pager_end = $pager_start + $visible_pages - 1}
                {if $pager_end > $pager->count}
                    {$pager_end = $pager->count}
                {/if}
                {for $i = $pager_start ; $i <= $pager_end; $i++}
                    {if $i == $pager->current}
                        <li>
                            <a href="#" class="active">{$i}</a>
                        </li>
                    {else}
                        <li>
                            <a href="{$pager->url}&page={$i}">{$i}</a>
                        </li>
                    {/if}
                {/for}
                {if $pager_end < $pager->count}
                    <li>
                        <a href="{$pager->url}&page={$pager_end + 1}">></a>
                    </li>
                    <li class="end">
                        <a href="{$pager->url}&page={$pager->count}" style="margin-left: 17px;">>></a>
                    </li>
                {/if}
            </ul>
        </div>
    </div>
{/if}