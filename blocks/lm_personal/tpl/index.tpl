<div class="clearfix">
    <div class="upic-container">
        <a href="{$upiclink}" class="upic-wrapper">{$upic}</a>
        {if $loginas|default:false}
            <a href="{$loginas}">Войти под этим именем</a>
        {/if}
        <a class="ufullname" href="{$upiclink}">{$ufullname}</a>
    </div>
    <div class="udescr-container">

        <div class="udescr-inner">
            <div class="ufield clearfix">
                <div class="ufield-name">Должность:</div>
                <div class="ufield-val">{$post.value}</div>
            </div>
            <div class="ufield clearfix">
                <div class="ufield-name">С нами:</div>
                <div class="ufield-val">{$since.value}</div>
            </div>
            <div class="ufield clearfix">
                <div class="ufield-name">Моб. тел:</div>
                <div class="ufield-val">{$phone.value}</div>
            </div>
            <div class="ufield clearfix">
                <div class="ufield-name">e-mail:</div>
                <div class="ufield-val"><a href="{$email.uri}">{$email.value}</a></div>
            </div>
            <div class="ufield clearfix">
                <div class="ufield-name">Рук адм:</div>
                <div class="ufield-val"><a href="{$chief.uri}">{$chief.value}</a></div>
            </div>
            <div class="ufield clearfix">
                <div class="ufield-name">Рук функ:</div>
                <div class="ufield-val"><a href="{$fchief.uri}">{$fchief.value}</a></div>
            </div>
        </div>
    </div>
</div>