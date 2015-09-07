<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/25/2015
 * Time: 2:50 PM
 */

if ($hassiteconfig) { // needs this condition or there is error on login page
    $settings = new admin_settingpage('local_lm_chikagosync', get_string('pluginname', 'local_lm_chikagosync'));
    $ADMIN->add('localplugins', $settings);
    $settings->add(new admin_setting_configtext(
        'lm_chikagosync_path',
        get_string('xml_folder_path', 'local_lm_chikagosync'),
        get_string('xml_folder_path_description', 'local_lm_chikagosync'),
        '/chikagosync'
    ));
}
