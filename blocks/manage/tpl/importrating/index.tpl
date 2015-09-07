<h1>Импорт рейтингов</h1>

{if ! empty($errors)}
    <div class="lm_rating_errors">
    {foreach $errors as $error}
        <div class="lm_rating_error">$error</div>
    {/foreach}
    </div>
{/if}

{if $page}
    {include file="./$page.tpl"}
{else}
    {$form}
{/if}
