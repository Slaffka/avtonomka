<h1>Импорт KPI</h1>

{if ! empty($errors)}
    <div class="lm_kpi_errors">
    {foreach $errors as $error}
        <div class="lm_kpi_error">$error</div>
    {/foreach}
    </div>
{/if}

{if $page}
    {include file="./$page.tpl"}
{else}
    {$form}
{/if}
