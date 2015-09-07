<?php
$items = array(
    "manage_profile"=>(object)array(
        "url"=>"/user/profile.php?id={$USER->id}",
        "icon"=>"user",
        "name"=>"Профиль"
    ),
    "manage_mycourses"=>(object)array(
        "url"=>"/my",
        "icon"=>"books",
        "name"=>"Мои курсы"
    ),
    "lm_settinggoals"=>(object)array(
        "url"=>"/blocks/manage/?_p=lm_settinggoals",
        "icon"=>"aim",
        "name"=>"Целеполагание"
    ),
    "lm_mytrack"=>(object)array(
        "url"=>"/blocks/manage/?_p=lm_ipe",
        "icon"=>"rocket",
        "name"=>"Мой ИПР"
    ),
    "lm_rank"=>(object)array(
        "url"=>"/blocks/manage/?_p=lm_rank",
        "icon"=>"medal",
        "name"=>"Ранги"
    ),
    "lm_rating"=>(object)array(
        "url"=>"/blocks/manage/?_p=lm_rating",
        "icon"=>"podium",
        "name"=>"Рейтинги"
    ),
    "faq"=>(object)array(
        "url"=>"/course/view.php?id=5",
        "icon"=>"question",
        "name"=>"База знаний"
    ),
    "lm_bestpractices"=>(object)array(
        "url"=>"/blocks/manage/?_p=lm_bestpractices",
        "icon"=>"diamond3",
        "name"=>"Передовой опыт"
    ),
    "manage_assesment_center"=>(object)array(
        "url"=>"/blocks/manage/?_p=assesment_center",
        "icon"=>"clipboard-check",
        "name"=>"Ассессмент центр"
    ),
    "manage_report"=>(object)array(
        "url"=>"/blocks/manage/?_p=report",
        "icon"=>"pie-chart",
        "name"=>"Отчетность"
    ),
    "lm_mystaff"=>(object)array(
        "url"=>"/blocks/manage/?_p=profile&subpage=myteam",
        "icon"=>"users2",
        "name"=>"Мои сотрудники"
    ),
    "manage_activities"=>(object)array(
        "url"=>"/blocks/manage/?_p=activities",
        "icon"=>"briefcase",
        "name"=>"Активности"
    ),
    "lm_feedback"=>(object)array(
        "url"=>"/blocks/manage/?_p=lm_feedback",
        "icon"=>"comments",
        "name"=>"Обратная связь"
    ),
    "manage_admin"=>(object)array(
        "url"=>"/blocks/manage/?_p=admin",
        "icon"=>"tools",
        "name"=>"Администрирование"
    ),
);
if($islogin){
    ?>
    <div class="bt-menu">
        <div class="bt-menu-trigger"><span>Menu</span></div>
        <ul class="clearfix">
            <?php foreach($items as $type=>$item){ ?>

                <?php if(lm_access::has($type)){ ?>
                    <li>
                        <a href="<?php echo $item->url; ?>">
                            <i class="bt-icon icon-<?php echo $item->icon; ?>"></i>
                            <span><?php echo $item->name; ?></span>
                        </a>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    </div>
    <?php
}
?>