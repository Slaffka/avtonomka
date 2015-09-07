<div class="lm-subnav lm-{$pagetype}-nav">
    {if $headname}
        <ul class="lm-subnav-back">
            <li><a href="/blocks/manage/?_p=profile">
                    <i class="icon-chevron-left"></i> {$headname}</a>
            </li>
        </ul>
    {/if}

    {if $subpagemenu|default:false}
        <ul class="clearfix lm-subnav-items">
            {foreach from=$subpagemenu item=item}
                {if $item->current}
                    <li class="item">
                        <a class="item-current">{$item->name}</a>
                        {if $item->alerts}<div class="alert-menu">{$item->alerts}</div>{/if}


                    </li>
                {else}
                    <li class="item">
                        <a href="{$item->url}" class="{$item->class}">{$item->name}</a>
                        {if $item->alerts}<div class="alert-menu">{$item->alerts}</div>{/if}



                    </li>
                {/if}
            {/foreach}
            <li class="item-more dropdown hide" style="display:none">
                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
                    Еще <span class="caret"></span>
                </a>
                <ul class="dropdown-menu">

                </ul>
            </li>
            {*<li>
                <a href="" class="more-item">Еще</a>
                <ul class="menu-responsive">
                    <li><a href="">Моя команда</a></li>
                    <li><a href="">Моя команда</a></li>
                </ul>

            </li>*}
        </ul>
    {/if}
</div>