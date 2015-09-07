<?php
$access = array('tp' => array('profile'=>1, 'mycourses'=>1, 'mytrack'=>1, 'calendar'=>1, 'rank'=>1,
                     'rating'=>1, 'faq'=>1, 'lm_bestpractices'=>1, 'assesment_center'=>0, 'report'=>0, 'mystaff'=>0,
                     'activities'=>0, 'admin'=>0),
                'merch' => array('profile'=>1, 'mycourses'=>1, 'mytrack'=>1, 'calendar'=>1, 'rank'=>1,
                    'rating'=>1, 'faq'=>1, 'lm_bestpractices'=>1, 'assesment_center'=>0, 'report'=>0, 'mystaff'=>0,
                    'activities'=>0, 'admin'=>0),
                'svsales' => array('profile'=>1, 'mycourses'=>1, 'mytrack'=>1, 'calendar'=>1, 'rank'=>1,
                    'rating'=>1, 'faq'=>1, 'lm_bestpractices'=>1, 'assesment_center'=>1, 'report'=>1, 'mystaff'=>1,
                    'activities'=>1, 'admin'=>0),
                'trainer' => array('profile'=>1, 'mycourses'=>1, 'mytrack'=>1, 'calendar'=>1, 'rank'=>1,
                    'rating'=>1, 'faq'=>1, 'lm_bestpractices'=>1, 'assesment_center'=>1, 'report'=>1, 'mystaff'=>1,
                    'activities'=>1, 'admin'=>0),
                'hr' => array('profile'=>1, 'mycourses'=>0, 'mytrack'=>0, 'calendar'=>0, 'rank'=>0,
                    'rating'=>0, 'faq'=>1, 'lm_bestpractices'=>1, 'assesment_center'=>1, 'report'=>1, 'mystaff'=>0,
                    'activities'=>0, 'admin'=>0),
                'all' => array('profile'=>1, 'mycourses'=>1),
);

function bnav_has_access($item, $roles, $access){
    global $CFG, $USER;

    if($CFG->siteadmins){
        $admins = explode(',', $CFG->siteadmins);
        if(in_array($USER->id, $admins)){
            return true;
        }
    }
    foreach($roles as $rolename){
        if(isset($access[$rolename][$item]) && $access[$rolename][$item]){
            return true;
        }
    }

    if(isset($access['all'][$item]) && $access['all'][$item]){
        return true;
    }
    return false;
}

if($islogin){
?>
    <?php $devicetype = core_useragent::get_user_device_type();
    if( $devicetype == core_useragent::DEVICETYPE_TABLET || $devicetype == core_useragent::DEVICETYPE_MOBILE ){ ?>
        <link rel="stylesheet" type="text/css" href="/theme/clean/js/tibimenu/theme/shift.css" />
    <?php
    }else{ ?>
        <link rel="stylesheet" type="text/css" href="/theme/clean/js/tibimenu/theme/default.css" />
    <?php
    } ?>



<div class="bt-menu">
    <div class="bt-menu-trigger"><span>Menu</span></div>
    <ul class="clearfix">

        <?php if(bnav_has_access('profile', $myroles, $access)){ ?>
        <li>
            <a href="/user/profile.php?id=<?php echo $USER->id; ?>">
                <i class="bt-icon icon-user"></i>
                <span>Профиль</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('mycourses', $myroles, $access)){ ?>
        <li>
            <a href="/my" >
                <i class="bt-icon icon-books"></i>
                <span>Мои курсы</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('mytrack', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=profile&details=lm_ipe">
                <i class="bt-icon icon-rocket"></i>
                <span>Мой ИПР</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('calendar', $myroles, $access)){ ?>
        <li>
            <a href="/calendar/view.php">
                <i class="bt-icon icon-calendar3"></i>
                <span>Календарь</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('rank', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=profile&details=lm_rank">
                <i class="bt-icon icon-medal"></i>
                <span>Ранги</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('rating', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=lm_rating">
                <i class="bt-icon icon-podium"></i>
                <span>Рейтинги</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('faq', $myroles, $access)){ ?>
        <li>
            <a href="/course/view.php?id=5">
                <i class="bt-icon icon-question"></i>
                <span>FAQ</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('lm_bestpractices', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=lm_bestpractices">
                <i class="bt-icon icon-diamond3"></i>
                <span>Передовой опыт</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('assesment_center', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=assesment_center">
                <i class="bt-icon icon-clipboard-check"></i>
                <span>Ассессмент центр</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('report', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=report">
                <i class="bt-icon icon-pie-chart"></i>
                <span>Отчетность</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('mystaff', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=profile&subpage=myteam">
                <i class="bt-icon icon-users2"></i>
                <span>Мои сотрудники</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('activities', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=activities">
                <i class="bt-icon icon-briefcase"></i>
                <span>Активности</span>
            </a>
        </li>
        <?php } ?>

        <?php if(bnav_has_access('admin', $myroles, $access)){ ?>
        <li>
            <a href="/blocks/manage/?_p=admin" >
                <i class="bt-icon icon-tools"></i>
                <span>Администрирование</span>
            </a>
        </li>
        <?php } ?>
    </ul>
</div>



<script src="/theme/clean/js/tibimenu/tibiMenu.js"></script>

<?php
}
?>