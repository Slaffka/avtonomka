<?php

function xmldb_block_lm_feedback_install() {
    global $DB, $CFG;

    $subjects = array();
    $subjects[] = (object) array("name" => "Предложение нового функционала");
    $subjects[] = (object) array("name" => "Изменить существующий функционал");
    $subjects[] = (object) array("name" => "Рассказать об ошибке");
    $subjects[] = (object) array("name" => "Пожаловаться");

    if (is_array($subjects) && count($subjects) > 0) {
        foreach ($subjects as $subject) {
            try {
                $DB->insert_record("lm_feedback_subjects", $subject);
            } catch (Exception $e) {
                echo 'Error: ' .  $e->getMessage() . "\n";
            }
        }
    }

}