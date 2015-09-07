{if $user->state == 0 || $user->state == 1}
    Ожидается предложение от сотрудника
{elseif $user->state == 2}
    <a class="btn" href="{$page_url}&do_action=commentcorrection&tpid={$user->id}">комментировать</a>
    <a class="btn" href="{$page_url}&do_action=acceptcorrection&posid={$user->posid}">согласовать</a>
{elseif $user->state == 3}
    Oжидается ответ сотрудника
{elseif $user->state == 4}
    Согласовано
{/if}