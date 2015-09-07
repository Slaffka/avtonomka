<table class="table table-bordered">
    {$n = 1}
    {foreach $payments as $payment}
        <tr>
            <td style="width: 9%; text-align: center;">
                {$n}
            </td>
            <td style="width: 25%; text-align: left;">
                {$payment->userava} &nbsp;
                <a href = "/blocks/manage/?_p=lm_bank&userid={$payment->userid}" target="_blank">
                    {$payment->username}
                </a>
            </td>
            <td width="34%">
                {$payment->comment}
            </td>
            <td style="width: 14%; text-align: center;">
                {$payment->date}
            </td>
            <td style="width: 18%; text-align: left;">
                {if ( $wamount == 'income' ) }
                    <span class="pay-income">+{$payment->amount} {$payment->title_money}</span>
                {else}
                    <span class="pay-expense">{$payment->amount} {$payment->title_money}</span>
                {/if}
            </td>
        </tr>
        {$n = $n + 1}
    {/foreach}
</table>