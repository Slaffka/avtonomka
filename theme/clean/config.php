<?php
$THEME->name = 'clean';

/////////////////////////////////
// The only thing you need to change in this file when copying it to
// create a new theme is the name above. You also need to change the name
// in version.php and lang/en/theme_clean.php as well.
//////////////////////////////////
//
$THEME->doctype = 'html5';
$THEME->parents = array('bootstrapbase');
$THEME->sheets = array('custom');
$THEME->supportscssoptimisation = false;
$THEME->yuicssmodules = array();
$THEME->enable_dock = true;
$THEME->editor_sheets = array();

$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->csspostprocess = 'theme_clean_process_css';

$THEME->blockrtlmanipulations = array(
    'side-pre' => 'side-post',
    'side-post' => 'side-pre'
);
