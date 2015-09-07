<?php

class block_manage extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_manage');
    }

    public function applicable_formats() {
        return array('site' => true);
    }

    public function has_config() {
        return true;
    }

    public function get_content () {
        global $CFG;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';
        $this->content->text = '';

        if (isloggedin() && !isguestuser()) {   // Show the block

            $this->content->text .= '<a href="'.$CFG->wwwroot.'/blocks/manage/?_p=activities">Управление обучением</a>';
        }

        return $this->content;
    }

    public function cron(){
        global $DB, $CFG;

        require_once($CFG->dirroot.'/blocks/manage/lib.php');

        $starttime =  microtime();
        $counter = 0;

        if($partners = $DB->get_records('lm_partner')){
            foreach($partners as $pr){
                $opartner = lm_partner::i($pr);
                $opartner->recalculate_appointed_programs();

                if( $staffers = $opartner->get_staffers() ){
                    foreach($staffers as $staffer){
                        // Пересчет прогресса по каждой из назначенных программ
                        $opartner->recalculate_staffer_progress_all_programs($staffer->id);

                        // Пересчет прогресса по всем программам
                        $opartner->recalculate_staffer_progress($staffer->id);
                    }
                }

                // Важно запустить это после подсчета прогресса по сотрудникам
                $opartner->recalculateall_trained_percent();

                $counter ++;
            }
        }

        mtrace($counter . ' partners refreshed (took ' . microtime_diff($starttime, microtime()) . ' seconds)');

        if($activities = $DB->get_records('lm_activity')){
            foreach($activities as $activ){
                $activity = lm_activity::i($activ);

                $activity->recalculate_dates();
            }
        }
    }
}
