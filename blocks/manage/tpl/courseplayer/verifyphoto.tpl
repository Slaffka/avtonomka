{if $photos|default:false}
    <div class="verifyphoto-container clearfix">
        <div class="actions">
            <a href="#" class="btn btn-verifyphoto" data-loading-text="Подождите..." data-action="apply">
                Подтвердить совпадение всех фото
            </a>
            <a href="#" class="btn btn-link btn-verifyphoto" data-loading-text="Подождите..." data-action="decline">
                Отклонить
            </a>
            <span>Если фото не совпадает, сотруднику приедтся заново пройти весь курс</span>
        </div>
        <div class="verifyphoto-item">
            Фото профиля
            {$profilephoto}
        </div>

        {$n = 1}
        {foreach $photos as $photo}
            <div class="verifyphoto-item" data-id="{$photo->id}">
                Фото №{$n++}
                <img src="{$photo->photo}">
            </div>
        {/foreach}
    </div>
{/if}
