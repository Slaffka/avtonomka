<?php

defined('MOODLE_INTERNAL') || die;
global $DB;

if ($ADMIN->fulltree) {

    $options = array(0=>'Не выбрано...');
    if( $posts = $DB->get_records('lm_post') ) {
        foreach ( $posts as $postid => $postname ) {
            $options[$postid] = $postname->name;
        }
    }

    $settings->add(new admin_setting_configmultiselect('block_lm_rating_posts_filter', 'Посты для фильтра модератора',
        '', 'all', $options));


}


