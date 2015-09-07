<div class="modal-window">
    <div class="header">
        <div class="title">
            {$kpi_data->place_name}
        </div>
        <div class="section">
            <a class="close-modal" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">Закрыть</span>
            </a>
        </div>
    </div>

    <div class="content">
        <ul>
            {foreach $kpi_data->list as $value}
            <li>{$value}</li>
            {/foreach}
        </ul>
    </div>

    <div class="footer">
        <a href="#" class="btn close-modal">Ok</a>
    </div>
</div>