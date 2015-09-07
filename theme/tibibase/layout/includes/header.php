<?php
?>
<header role="banner" class="navbar moodle-has-zindex">
    <nav role="navigation" class="navbar-inner">
        <?php if($islogin){ ?>
        <a class="brand" href="<?php echo $CFG->wwwroot; ?>"></a>
        <?php } ?>

        <?php echo $OUTPUT->lm_navigation(); ?>

            <ul class="right-menu">
                <li class="personal">
                    <?php echo $OUTPUT->user_menu(); ?>
                </li>

                <?php if($islogin){ ?>
                <li class="bell">
                    <div class="icon-bell"></div>

                    <?php $notifycount = lm_notification::get_count("manage:verifyphoto"); ?>
                    <?php if($notifycount){ ?>
                        <div class="alert-menu"><?php echo $notifycount; ?></div>
                    <?php } ?>
                </li>
                <?php } ?>

                <?php include(dirname(__FILE__) . '/bnav.php'); ?>
            </ul>


    </nav>
</header>