<section id="region-main" class="span12 lm-bank">
    {if !$isadmin}
        {* START User Interface *}
        <div class="my-statistics">
            <div class="my-balance">
                <h3 class="text-center">
                    Баланс: {$balance}
                    {if $burning_info}
                        <span style="" class="info-burn-payment" data-container="body"
                              data-toggle="popover"
                              data-placement="bottom"
                              title="Информация о сгорании"
                              data-content="">
                            !
                        </span>
                    {/if}
                </h3>

                <div class="filter">
                    <div>
                        с <input type="text" class="startdate" readonly="readonly" /> по <input type="text" class="enddate" readonly="readonly" /><br>
                    </div>
                    <div class="phase">
                        <a href="#" class="period " data-period="month" >месяц</a>
                        <a href="#" class="period " data-period="quarter" >квартал</a>
                        <a href="#" class="period " data-period="year" >год</a>
                        <a href="#" class="period active" data-period="all" >все</a>
                    </div>
                </div>

                <div class="glass"></div>

                <div class="info">
                    <a class="close">Закрыть</a>
                    <h3 class="balance"></h3>
                    <div class="list"><ul class="info-pay"></ul></div>
                </div>
            </div>
            <div class="goal">
                <div class="development">В разработке</div>
                <img src="/blocks/lm_bank/img/goal2.png">
                <div class="background-goal">&nbsp;</div>
            </div>
        </div>
        {* END User Interface *}
    {else}
        {if $userid|default:false}
            {* START Edit User *}

            <div class="header">
                <div class="userava">
                    {$userava}
                </div>
                <div class="username">
                    <a href="/blocks/manage/?_p=lm_personal&id={$userid}">
                        {$username}
                    </a>
                </div>
                <div class="stat">
                    <div class="balance">
                        Баланс: <span class="balance-text">{$balance}</span>
                        <div style="padding:3px;"></div>
                        {if $info_burn}
                            <span class="burn-text">{$info_burn->balance}</span>
                             монет <b>сгорит</b> через <span class="burn-text">{$info_burn->days}</span> дн.
                        {else}
                            Нет монет для сгорания
                        {/if}
                    </div>
                </div>
                <div class="action">
                    <a href="#" class="debit" data-userid = {$userid}>
                        Добавить монет
                    </a>
                    <br>
                    <a href="#" class="credit" data-userid = {$userid}>
                        Отнять монет
                    </a>
                </div>
            </div>
            <div class="clearfix"></div>

            <div class="modal fade hide" id="operation_coins" tabindex="-1" role="dialog" data-backdrop="true" aria-labelledby="" aria-hidden="true"  data-keyboard="true" style="display: none;" >
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <a class="close" data-dismiss="modal" aria-label="Close" >
                                <span aria-hidden="true">Закрыть</span>
                            </a>
                            <h5>&nbsp;</h5>
                        </div>
                        <div class="modal-body">
                            <img id="loadImg" src="/pix/i/loading.gif" />
                            <div class="body"></div>
                        </div>
                        <div class="modal-footer"></div>
                    </div>
                </div>
            </div>

            <div style="margin: 30px;">
                {if $payments|default:false}
                    <table class="table table-bordered" style="margin-top: 10px;  border: 0;">
                        {foreach $payments as $payment}
                            <tr>
                                <td style="width: 120px; text-align: center;">
                                    {if $payment->amount > 0}
                                        <span class="pay-income">+{$payment->amount} {$payment->title_money}</span>
                                    {else}
                                        <span class="pay-expense">{$payment->amount} {$payment->title_money}</span>
                                    {/if}
                                </td>
                                <td class="content">
                                    {$payment->comment}
                                </td>
                                <td style="width: 200px; text-align: center;">
                                    {$payment->date}
                                </td>
                            </tr>
                        {/foreach}
                    </table>
                {/if}

                {if $count_pages > 1}
                    <div class="pagination text-center">
                        <div >
                            <ul>
                                {if $end > 5} {$p = $end - $limit} {else} {$p = 1} {/if}
                                <li class="start"><a href="{$link_navig}&p={$p}"><</a></li>
                                {if $active > 7}
                                    <li class="start"><a href="{$link_navig}&p=1">1</a></li>
                                    <li class=""><a href="{$link_navig}&p={$p}">...</a></li>
                                {/if}
                                {for $i = $start; $i <= $end; $i++}
                                    {if $i == $active}
                                        <li><a href="#" class="active">{$i}</a> </li>
                                    {else}
                                        <li>
                                            {if $i == 1}
                                                {$url = "$link_navig&p=1"}
                                            {else}
                                                {$url = "$link_navig&p=$i"}
                                            {/if}
                                            <a href="{$url}">{$i}</a> </li>
                                    {/if}
                                {/for}
                                {if $end + $limit > $count_pages} {$p = $count_pages} {else} {$p = $end + 1} {/if}
                                {if $active != $count_pages && $active < $count_pages - 2 }
                                    <li><a href="{$link_navig}&p={$p}">...</a></li>
                                    <li><a href="{$link_navig}&p={$count_pages}">{$count_pages}</a></li>
                                {/if}
                                <li class="end"><a href="{$link_navig}&p={$p}">></a></li>
                            </ul>
                        </div>
                    </div>
                {/if}
            </div>
            {* END Edit User *}
        {else}
            {* START Statistics *}
            <div class="clearfix">
                <div class="lm-subnav lm-subnav-stat-payment clearfix">
                    <ul class="clearfix type">
                        <li {if $income_link|default:false}class="current"{/if}><a href="{$link}&stat=income">Приход</a></li>
                        <li {if $expense_link|default:false}class="current"{/if}><a href="{$link}&stat=expense">Расход</a></li>
                        {if !$payments}<li><a href="#" class="testdate">Создать тестовые данные для банка</a></li>{/if}
                    </ul>
                    <ul class="clearfix search-user">
                        <li class="">
                            <div class="form-group has-success has-feedback">
                                <input type="text" class="form-control search_user" placeholder="Найти сотрудника">
                                <i class="icon-magnifier"></i>
                            </div>
                            {*<input type="text" size="80" class="search_user" placeholder="Найти пользователя" />*}
                        </li>
                    </ul>
                </div>
            </div>
            <div class="row all-statistics">
                <div class="span6">
                    <div id="statistics_diagram" data-stat="{$stat}">
                        <svg id="mysvg" viewBox="0 0 350 300"></svg>
                    </div>
                    {*{foreach $diagrams as $value}
                        {$value->month}/{$value->year} -  <span class="sum-amount text-info" data-month = "{$value->month}" data-year = "{$value->year}" data-wamount = "{$whereamount}">сумма: {$value->sum}</span> <br>
                    {/foreach}*}
                </div>
                <div class="span6">
                    <div class="loading"></div>
                    <div id="channels"></div>
                    <ul class="channels"></ul>
                </div>
            </div>

            <h3 class="text-center name_channel"></h3>
            <div class="stats"></div>
            {* END Statistics *}
        {/if}
    {/if}
</section>
<img id="loadImgSmall" src="/pix/i/loading_small.gif" />
<img id="loadImg" src="/pix/i/loading.gif" />
