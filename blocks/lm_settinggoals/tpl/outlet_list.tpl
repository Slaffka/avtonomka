<table class="table table-bordered ">
    <tr>
        <th style="text-align: left; padding-left:10px; line-height: 43px;">
            <div class="input-append" style="margin:0; float:left;">
                <input type="text"
                   name="search_outlet"
                   id="search_outlet"
                   data-posid="{$posid}"
                   data-tpid="{$tpid}"
                   data-tptime="{$tptime}"
                   placeholder="Введите название ТТ"
                   value="{$search}"
                   style="margin-top:8px; width: 450px;  "
                   class="form-control">
                <a href="#" class="search-btn">
                    <span class="icon-magnifier add-on" style="  margin-top: 8px;"></span>
                </a>
            </div>
            <a href="#" class="btn save_outlets" style="float:right; padding: 10px;" data-posid="{$posid}" data-tptime="{$tptime}">добавить список</a>
        </th>
    </tr>
</table>
<div class="content-tt-list">
{include './tt_list.tpl'}
</div>
<img id="loadImg" src="/pix/i/loading.gif" />
<style>
    #loadImg{
        position:absolute;
        z-index:10000;
        display:none;
    }
</style>