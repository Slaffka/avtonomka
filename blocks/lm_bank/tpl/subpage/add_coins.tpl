<form class="form-inline">
    <div class="form-group control-group ">
        <input type="text" class="form-control money" id="money" placeholder="Количество монет">
    </div>
    <div class="form-group control-group ">
        <select class="form-control channel">
            <option  data-channelid = "0">Канал зачисления</option>
            {foreach $channels as $channel}
                <option data-channelid = "{$channel->id}">{$channel->code}</option>
            {/foreach}
        </select><br>
        <a class="search-instance hide picker-instance" style="text-decoration: none;   border-bottom: 1px dashed #000080;" href="#" data-userid="{$userid}"></a>
    </div>
    <div class="form-group control-group ">
        <textarea style="width:350px; height:100px;" class="comment" id = "comment" placeholder="Комментарий к платежу"></textarea>
    </div>
    <br>
    <div class="alert hide" role="alert" style="margin: 6px 0; "></div>
    <br>
    <button type="submit" class="btn btn-primary btn-submit add_coins" data-userid = "{$userid}">Зачислить монет</button>
</form>