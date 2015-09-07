<div class="modal-window">
    <div class="header">
        <div class="title">
            {$kpi_data->place_name}
        </div>
        <div class="section">
            {$kpi_data->kpi_name}
        </div>
    </div>

    <div class="content">
        <table align="center" class="edit-kpi">
            <tr>
                <td class="left">
                    Рекомендованный:
                </td>
                <td>&nbsp;</td>
                <td >
                    {$kpi_data->value|default:'0'|number_format:1:",":" "} {$kpi_data->uom}
                </td>
            </tr>
            <tr>
                <td class="left">
                    Изменить на:
                </td>
                <td>&nbsp;</td>
                <td>
                    <input type="text" name="new_value" value="{$kpi_data->correct_value|number_format:1:",":" "}"> {$kpi_data->uom}
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        <a href="#" class="btn submit">Сохранить</a>
    </div>
</div>