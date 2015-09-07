<table class="table table-bordered table-settinggoals outlets-list table-tt-list">
    {for $i=0 to $outlets}
        <tr>
            <td colspan="{if $outlet_list[$i+$outlets+1]}1{else}2{/if}"" style="text-align: left; padding-left:10px;"  class="tt {if $outlet_list[$i]->positionid}active{/if}">
                <input type="checkbox" class="cb_outlet" name="outlet_{$i}" id="outlet_{$i}"
                       value="{$outlet_list[$i]->id}"
                        {if $outlet_list[$i]->positionid} checked="checked"{/if}>
                <span style="text-align: left;">{$outlet_list[$i]->name}</span><br />
                <span style="text-align: left;font-size: 12px;">{$outlet_list[$i]->address}</span>
            </td>
            {if $outlet_list[$i+$outlets+1]}
            <td style="text-align: left; padding-left:10px;" class="tt {if $outlet_list[$i+$outlets+1]->positionid}active{/if}">
                <input type="checkbox" class="cb_outlet" name="outlet_{$i+$outlets+1}" id="outlet_{$i+$outlets+1}"
                       value="{$outlet_list[$i+$outlets+1]->id}"
                        {if $outlet_list[$i+$outlets+1]->positionid} checked="checked"{/if} style=" ">
                <span style="text-align: left;">{$outlet_list[$i+$outlets+1]->name}</span><br />
                <span style="text-align: left;font-size: 12px">{$outlet_list[$i+$outlets+1]->address}</span>
            </td>
            {/if}
        </tr>
    {/for}
</table>
<div class="text-center">
    {include './pager.tpl'}
</div>
