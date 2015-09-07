<?php
/**
 * Created by PhpStorm.
 * User: FullZero
 * Date: 6/25/2015
 * Time: 4:13 PM
 */

define('CLI_SCRIPT', TRUE);
$br  = "\n";

require_once(__DIR__.'/../../config.php');

require_once($CFG->dirroot.'/blocks/manage/lib.php');


/**
 * Файлы будут импортироваться в порядке указанном в этом массиве
 * @var string[] $importTypes
 */
$importTypes = array(
    'region',
    'nomenclature',
    'position',
    'user',
    'kpi',
    'rating',
    'area',
    'promo',
    'promoresults',
    'goalsettingplan',
    'goalsettingresult'
);

echo 'Started at ', date('d.m.Y H:i:s'), $br;
$path = realpath($CFG->dataroot.$CFG->lm_chikagosync_path) or die('Fail! Incorrect path.'.$br);

// Пример экспорта корректировок планов на день
/*
$date = time();
$date = strtotime('2015-07-24');
$fileName = 'settingplancorrect';
$filePath = $path.'/'.date('Y-m-d', $date).'_'.$fileName.'.xml';
$exported = (new lm_settinggoals_plan_export($filePath, TRUE, TRUE))->export($date);
die();
/**/

echo 'Scanning "', $path, '" folder', $br;
if (file_exists($path) && is_dir($path)) {
    $files = scandir($path);
    $date = '';
    $dateFiles = array();
    while(list(,$file) = each($files)) {
        if (preg_match('#(\d{4}-\d{2}-\d{2})_('.implode('|', $importTypes).').xml#', $file, $params)) {
            list($file, $fileDate, $fileType) = $params;
            if ($fileDate !== $date) {
                importFiles($dateFiles);
                $dateFiles = array();
            }
            $dateFiles[$fileType] = $file;
            $date = $fileDate;
        }
    }
    if ( ! empty($dateFiles)) importFiles($dateFiles);
}
echo 'Finished at ', date('d.m.Y H:i:s'), $br;

function importFiles($files){
    global $importTypes, $path, $br;

    if ( ! file_exists($path.'/archive')) mkdir($path.'/archive');

    foreach ($importTypes as $type) if (isset($files[$type])) {
        $filePath = $path.'/'.$files[$type];
        $time = time();
        echo "importing {$files[$type]}... ";
        switch ($type) {
            case 'region':
                $imported = (new lm_region_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done done in $time sec ({$imported} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'nomenclature':
                $imported = (new lm_nomenclature_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done done in $time sec ({$imported} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'position':
                list($errors) = (new lm_org_import($filePath))->import();
                if (empty($errors)) {
                    $time = time() - $time;
                    echo "done in $time sec";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                } else {
                    foreach ($errors as $error) echo $br, "\terror: {$error}";
                }
                break;
            case 'user':
                list($errors) = (new lm_staff_import($filePath))->import();
                if (empty($errors)) {
                    $time = time() - $time;
                    echo "done in $time sec";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                } else {
                    foreach ($errors as $error) echo $br, "\terror: {$error}", $br;
                }
                break;
            case 'kpi':
                $imported = (new lm_kpi_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done in $time sec ({$imported} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'rating':
                $imported = (new lm_rating_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done in $time sec (metric: {$imported['metric']} rows, param: {$imported['param']} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'area':
                $imported = (new lm_area_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done in $time sec (area: {$imported['area']} rows, trade outlet: {$imported['trade-outlet']} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'promo':
                $imported = (new lm_tma_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done in $time sec ({$imported} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'promoresults':
                $imported = (new lm_tma_results_import($filePath))->import();
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done in $time sec ({$imported} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            case 'goalsettingplan':
            case 'goalsettingresult':
                $imported = (new lm_settinggoals_plan_import($filePath))->import($type === 'goalsettingresult' ? 'fact' : 'plan');
                if ($imported !== FALSE) {
                    $time = time() - $time;
                    echo "done in $time sec ({$imported} rows)";
                    //unlink($filePath);
                    rename($filePath, $path.'/archive/'.$files[$type]);
                    break;
                }
            default:
                echo 'fail';
        }
        echo $br;
    }
}