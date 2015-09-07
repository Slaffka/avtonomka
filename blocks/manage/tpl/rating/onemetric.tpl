{$percent = 100 / $count_titles}
<table cellpadding="5" class="table-rating" style="width: 100%">
    <tr>
        <th class="text-center th_main" style="width: 100%" colspan="{$count_titles}">
            <table style="width: 100%">
                <tr>
                    {foreach $titles as $title}
                        <th class="text-center th_title" style="width:{$percent}%">{$title}</th>
                    {/foreach}
                </tr>
            </table>
        </th>
    </tr>
    <tr>
        {foreach $values as $value}
            <td class="text-center param" style="width:{$percent}%">{$value}</td>
        {/foreach}
    </tr>
</table>

