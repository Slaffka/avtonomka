{if $list|default:false}
    <ul class="matrix-list">
    {foreach from=$list item=post}
        <li class="post-item {if !$post->evolution_stages_enabled}post-item-stages-off{/if}">

            {if $post->evolution_stages_enabled}
                <div class="post-item-header clearfix">
                    <a class="post-name-wrapper collapsed"  data-toggle="collapse" href="#collapse-{$post->id}" aria-expanded="false" aria-controls="collapse-{$post->id}">
                        <i class="fa fa-plus-square-o"></i><i class="fa fa-minus-square-o"></i>
                        <span class="post-name">{$post->name}</span>
                    </a>
                    <a href="/blocks/manage/?__ajc=matrix::switch_stages&postid={$post->id}" class="stages-switcher">Отключить этапы развития</a>
                </div>


                <ul class="stages-list collapse" id="collapse-{$post->id}">
                    {foreach from=$post->stages item=stage}
                        <li>
                            <span class="stage-name">{$stage->name}</span>
                            <form class="form-programs form-program_{$post->id}-{$stage->id}" action="">
                                <ul class="matrix-program-list">
                                    {foreach from=$stage->programs item=program name=programs}
                                        <li class="program-item {if $smarty.foreach.programs.last}program-item-tpl{/if}">
                                            <i class="fa fa-arrows program-move"></i>
                                            {$program->select}

                                            <a href="#" class="btn-del-program {if $smarty.foreach.programs.last} hide{/if}"> удалить</a>
                                        </li>
                                    {/foreach}
                                </ul>
                            </form>
                        </li>
                    {/foreach}
                </ul>
            {else}
                <div class="post-item-header clearfix">
                    <span class="post-name">{$post->name}</span>
                    <a href="/blocks/manage/?__ajc=matrix::switch_stages&postid={$post->id}" class="stages-switcher">Включить этапы развития</a>
                </div>
            {/if}

        </li>
    {/foreach}
    </ul>
{/if}
