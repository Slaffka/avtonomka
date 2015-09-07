<table class="table table-bordered tickets" >
    <tr>
        <th class="left">Автор</th>
        <th class="subjects">
            {if $moder}
                <div class="select-subjects">
                    {*<input type="text" placeholder="Содержание" class="select" />*}
                    <select name = "filter-subject" class="form-control filter-subject" style="margin-bottom: 0;">
                        <option value="0" data-skip="1" {if !$subj}selected="selected"{/if}>Содержание</option>
                        <option value="1" data-html-text = "Предложение нового функционала" {if $subj == 1}selected="selected"{/if}>Предложение нового функционала</option>
                        <option value="2" data-html-text = "Изменить существующий функционал" {if $subj == 2}selected="selected"{/if}>Изменить существующий функционал</option>
                        <option value="3" data-html-text = "Рассказать об ошибке" {if $subj == 3}selected="selected"{/if}>Рассказать об ошибке</option>
                        <option value="4" data-html-text = "Пожаловаться" {if $subj == 4}selected="selected"{/if}>Пожаловаться</option>
                    </select>
                </div>
            {/if}
        </th>
        <th class="right">Поступило</th>
    </tr>
    {if $tickets|default:false}
        {foreach $tickets as $ticket}
            <tr id="ticket{$ticket->id}">
                <td>
                    {$ticket->userava}&nbsp;
                    <a href="/user/profile.php?id={$ticket->userid}" target="_blank">{$ticket->username}</a>
                </td>
                <td data-ticketid="{$ticket->id}" class="message">
                    {$ticket->message|truncate:30:"...":true}
                </td>
                <td class="center">
                    {$ticket->time}
                </td>
            </tr>
        {/foreach}
    {else}
        <tr>
            <td colspan="3" class="nodata">Нет данных</td>
        </tr>
    {/if}
</table>

{if $count_pages > 1}
    <div class="pagination text-center" style="">
        <div >
            <ul>
                {if $end > 5} {$p = $end - $limit} {else} {$p = 1} {/if}
                <li class="start"><a href="{$link_navig}&p={$p}" style="margin-right: 17px;"><</a></li>
                {if $active > 7}
                    <li class="start"><a href="{$link_navig}&p=1" style="">1</a></li>
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
                <li class="end"><a href="{$link_navig}&p={$p}" style="margin-left: 17px;">></a></li>

            </ul>
        </div>
    </div>
{/if}

<div class="modal fade hide" id="OneTicket" tabindex="-1" role="dialog" data-backdrop="true" aria-labelledby="" aria-hidden="true"  data-keyboard="true" style="  min-width: 650px;  max-width: 100%;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <a class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">Закрыть</span>
                </a>
                <h4>&nbsp;</h4>
            </div>
            <div class="modal-body">
                <div class="row feedback" style="margin-left: 0;">
                    <div class="span2" style="margin-top:40px; text-align: center; width: 10%; margin-left: 20px;  margin-bottom: 10px;">
                        <span class="avatar"></span><br>
                        <span class="username"></span>
                    </div>
                    <div class="span7" style="   min-width: 350px;   max-width: 83%;   width: 100%; margin-left: 20px;">
                        <div style="margin-left: 0;">
                            <div style="float: left;"><p class="modal-title"></p></div>
                            <div style="float:right;"><span class="time"></span></div>
                        </div>
                        <div style="clear: both;"></div>
                        <div class="popover right" style="width: 100%;   margin-bottom: 10px; max-width: 100%;">
                            <div class="arrow"></div>
                            <div class="popover-content">
                                <p><span class="message" style="color:#7c7c7c; font-size: 14px; text-align: justify;"></span></p>
                                <br><br>
                                <div id = "files" class = "hide">
                                    <i class="fa fa-paperclip" style="margin-left:0; margin-top:26px; float: left;   margin-right: 4px;"></i>
                                    <ul class="files" style="   margin-left: 0;"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="all_messages"></div>
                <div style="margin-left: 20px;   width: 97%;">
                {if $moder}
                    <p class="message-touser">Сообщение пользователю:</p>
                    <textarea class="form-control form-message" style="width: 97%; height: 80px;"></textarea>
                {/if}
                <div class="alert-message"></div>
                </div>
            </div>
            {if $moder}
                <div class="modal-footer">
                    <a class="status btn-cnage-status"
                            data-loading-text="Переносим"
                            data-status="{if !$arhive}arhive{else}new{/if}">
                        {if !$arhive}Перенести в архив{else}Перенести в свежие{/if}
                    </a>
                    <input type="submit" class="btn-send-message" data-loading-text="Отправляем..." value="Отправить сообщение" style="  margin-top: 8px;" />
                </div>
            {/if}
        </div>
    </div>
</div>

<img id="loadImg" src="/pix/i/loading.gif" />
<style>
    #loadImg {
        position:absolute;
        z-index:1000;
        display:none;
    }

</style>
