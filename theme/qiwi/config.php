<?php

$THEME->name = 'qiwi';
$THEME->doctype = 'html5';
$THEME->parents = array('tibibase');
$THEME->sheets = array('custom');
$THEME->supportscssoptimisation = false;
$THEME->yuicssmodules = array();
$THEME->enable_dock = false;
$THEME->editor_sheets = array();

$THEME->rendererfactory = 'theme_overridden_renderer_factory';

$THEME->blockrtlmanipulations = array(
    'side-pre' => 'side-post',
    'side-post' => 'side-pre'
);


