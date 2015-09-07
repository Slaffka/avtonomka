{$info}
<hr><div class="form-actions form-actions-importsales">
    <a href="/blocks/manage/?_p=importsales">Отменить  </a>
    {if $prevstephref|default:false}
        <button class="btn btn-prev" data-href="{$prevstephref}">Назад</button>
    {/if}

    {if $nextstephref|default:false}
        <button class="btn btn-primary btn-next" data-href="{$nextstephref}" {if $nextstepdisabled}disabled="disabled" {/if}>
            {$nextstepname}
        </button>
    {/if}

</div>