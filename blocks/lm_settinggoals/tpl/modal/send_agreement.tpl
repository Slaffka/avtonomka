<div class="modal-window">
    <div class="header">
        <div class="title">
            ОТПРАВЛЕНИЕ НА СОГЛАСОВАНИЕ
        </div>
        <div class="section">
            <a class="close-modal" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">Закрыть</span>
            </a>
        </div>
    </div>

    {if $break}
        <div class="content">
            <div style="font-size: 22px; text-align: center; line-height: 28px;">
                Внимание!<br>
                У вас есть нераспределенный остаток:
            </div>

            <table class="table table-bordered edited-kpi">
                <thead>
                    <tr>
                        {foreach $kpi_names as $name}
                            <th>{$name}</th>
                        {/foreach}
                    </tr>
                </thead>
                <tr>
                    {foreach $kpi_names as $name}
                        <td>
                             {if $list[$name]->value}
                                {if $list[$name]->uom == 'шт'}
                                    {$list[$name]->value|number_format:0:",":" "}
                                {else}
                                    {$list[$name]->value|number_format:1:",":" "}
                                {/if}
                            {else}
                                0
                            {/if}
                            {$list[$name]->uom}
                        </td>
                    {/foreach}
                </tr>
            </table>
        </div>
        <div class="footer">
            <a href="#" class="submit text">Все равно продолжить</a> &nbsp; &nbsp;
            <a href="#" class="btn close-modal">Вернуться к редактированию</a>
        </div>
    {else}
        <div class="content">
            <span>Данные отправлены на согласование</span><br>
        </div>
        <div class="footer">
            <a href="#" class="btn reload">ОК</a>
        </div>
    {/if}
</div>
