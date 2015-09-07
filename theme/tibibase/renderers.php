<?php

global $PAGE, $CFG;

$PAGE->requires->jquery();
$PAGE->requires->jquery_plugin('ui');
$PAGE->requires->jquery_plugin('ui-css');
$PAGE->requires->jquery_plugin('bootstraporigin', 'theme_tibibase');
$PAGE->requires->jquery_plugin('modernizr', 'theme_tibibase');
$PAGE->requires->jquery_plugin('tibimenu', 'theme_tibibase');
$PAGE->requires->jquery_plugin('grid', 'theme_tibibase');
$PAGE->requires->jquery_plugin('print', 'theme_tibibase');
$PAGE->requires->jquery_plugin('notifications', 'theme_tibibase');
$PAGE->requires->jquery_plugin('app', 'theme_tibibase');

require_once($CFG->dirroot . '/theme/clean/renderers/block_course_overview.php');
require_once($CFG->dirroot . '/theme/clean/renderers/core_course_renderer.php');

class theme_tibibase_core_renderer extends core_renderer
{
    public function __construct(moodle_page $page, $target){
        parent::__construct($page, $target);

        $this->page->requires->jquery();

    }

    public function lm_navigation(){
        if( !class_exists('lm_renderer') ) return false;

        if( $renderer = lm_renderer::get() ){
            return $renderer->navigation();
        }

        return '';
    }

    /**
     * Construct a user menu, returning HTML that can be echoed out by a
     * layout file.
     *
     * @param stdClass $user A user object, usually $USER.
     * @param bool $withlinks true if a dropdown should be built.
     * @return string HTML fragment.
     */
    public function user_menu($user = null, $withlinks = null)
    {
        global $USER, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');

        if (is_null($user)) {
            $user = $USER;
        }

        // Note: this behaviour is intended to match that of core_renderer::login_info,
        // but should not be considered to be good practice; layout options are
        // intended to be theme-specific. Please don't copy this snippet anywhere else.
        if (is_null($withlinks)) {
            $withlinks = empty($this->page->layout_options['nologinlinks']);
        }

        // Add a class for when $withlinks is false.
        $usermenuclasses = 'usermenu';
        if (!$withlinks) {
            $usermenuclasses .= ' withoutlinks';
        }

        $returnstr = "";

        // If during initial install, return the empty return string.
        if (during_initial_install()) {
            return $returnstr;
        }

        $loginpage = ((string)$this->page->url === get_login_url());
        $loginurl = get_login_url();
        // If not logged in, show the typical not-logged-in string.
        if (!isloggedin() || isguestuser()) {
            //$returnstr = get_string('loggedinnot', 'moodle');
            if (!$loginpage) {
                $returnstr .= "<a href=\"$loginurl\" class=\"loginurl\"><span class='icon-lock'></span>" . get_string('login') . '</a>';
            }
            return html_writer::div(
                html_writer::span(
                    $returnstr,
                    'login'
                ),
                $usermenuclasses
            );

        }

        // Get some navigation opts.
        $opts = user_get_user_navigation_info($user, $this->page, $this->page->course);



        // Оставляем только пункт "выход"
        $logoutitem = array_pop($opts->navitems);
        $opts->navitems = array();

        if($this->page->user_allowed_editing()) {
            $item = new StdClass();
            $sesskey = sesskey();

            if($this->page->user_is_editing()){
                $item->title = "Завершить редактирование";
                $edit = 0;
            }else {
                $item->title = "Редактировать";
                $edit = 1;
            }

            $redirect = "";
            if(isset($_SERVER['REQUEST_URI'])) $redirect = "&redirect=".$_SERVER['REQUEST_URI'];

            $item->url = new moodle_url("/blocks/manage/?__ajc=base::turn_edition&sesskey={$sesskey}&edit={$edit}{$redirect}");
            $item->pix = "t/pencil";

            $opts->navitems[] = $item;
        }


        if(strpos($this->page->pagetype, "lm-profile") !== false) {
            $item = new StdClass();
            $item->url = new moodle_url("");
            $item->pix = "t/printer";
            $item->title = "Распечатать";
            $item->attributes = array('class'=>'btn-print');

            $opts->navitems[] = $item;
        }


        $opts->navitems[] = $logoutitem;

        // Делаем ФИО в две строчки
        if(isset($opts->metadata['userfullname'])){
            $tmp = explode(" ", $opts->metadata['userfullname']);
            $lastname = array_pop($tmp);
            $firstname = implode(" ", $tmp);
            $opts->metadata['userfullname'] = $lastname."<br>".$firstname;
        }


        $avatarclasses = "avatars";
        $opts->metadata["useravatar"] = preg_replace('/title="[^"]*"/', '', $opts->metadata["useravatar"]);
        $avatarcontents = html_writer::span($opts->metadata['useravatar'], 'avatar current');
        $usertextcontents = $opts->metadata['userfullname'];

        // Other user.
        if (!empty($opts->metadata['asotheruser'])) {
            $avatarcontents = html_writer::span(
                $opts->metadata['useravatar'],
                'avatar realuser'
            );
            //$usertextcontents = $opts->metadata['realuserfullname'];
            $usertextcontents = html_writer::span($opts->metadata['userfullname'], 'value');
        }

        // Role.
        if (!empty($opts->metadata['asotherrole'])) {
            $role = core_text::strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['rolename'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['rolename'],
                'meta role role-' . $role
            );
        }

        // User login failures.
        if (!empty($opts->metadata['userloginfail'])) {
            $usertextcontents .= html_writer::span(
                $opts->metadata['userloginfail'],
                'meta loginfailures'
            );
        }

        // MNet.
        if (!empty($opts->metadata['asmnetuser'])) {
            $mnet = strtolower(preg_replace('#[ ]+#', '-', trim($opts->metadata['mnetidprovidername'])));
            $usertextcontents .= html_writer::span(
                $opts->metadata['mnetidprovidername'],
                'meta mnet mnet-' . $mnet
            );
        }

        $returnstr .= html_writer::span(
            html_writer::span($usertextcontents, 'usertext') .
            html_writer::span($avatarcontents, $avatarclasses),
            'userbutton'
        );

        // Create a divider (well, a filler).
        $divider = new action_menu_filler();
        $divider->primary = false;

        $am = new action_menu();
        $am->initialise_js($this->page);
        $am->set_menu_trigger(
            $returnstr
        );
        $am->set_alignment(action_menu::TR, action_menu::BR);
        $am->set_nowrap_on_items();
        if ($withlinks) {
            $navitemcount = count($opts->navitems);
            $idx = 0;
            foreach ($opts->navitems as $key => $value) {
                $pix = null;
                if (isset($value->pix) && !empty($value->pix)) {
                    $pix = new pix_icon($value->pix, $value->title, null, array('class' => 'iconsmall'));
                } else if (isset($value->imgsrc) && !empty($value->imgsrc)) {
                    $value->title = html_writer::img(
                            $value->imgsrc,
                            $value->title,
                            array('class' => 'iconsmall')
                        ) . $value->title;
                }

                $attributes = array('class' => 'icon');
                if(isset($value->attributes)){
                    foreach($value->attributes as $attrname=>$attrval){
                        $attributes[$attrname] = $attrval;
                    }
                }

                $al = new action_menu_link_secondary(
                    $value->url,
                    $pix,
                    $value->title,
                    $attributes
                );
                $am->add($al);

                // Add dividers after the first item and before the
                // last item.
                if (/*$idx == 0 ||*/ $idx == $navitemcount - 2) {
                    $am->add($divider);
                }

                $idx++;
            }
        }

        return html_writer::div(
            $this->render($am),
            $usermenuclasses
        );
    }
}


