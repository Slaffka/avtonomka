<?php

function block_lm_feedback_pluginfile($course, $birecord_or_cm, $context, $filearea, $args, $forcedownload, array $options=array())
{

    if ($context->contextlevel != CONTEXT_BLOCK) {
        send_file_not_found();
    }

    if ($filearea !== 'files') {
        send_file_not_found();
    }

    $fs = get_file_storage();

    $filename = array_pop($args);
    $filepath = "/";
    if (!$file = $fs->get_file($context->id, 'block_lm_feedback', 'files', $args[0], $filepath, $filename) or $file->is_directory()) {
        send_file_not_found();
    }

    if ($parentcontext = context::instance_by_id($birecord_or_cm->parentcontextid, IGNORE_MISSING)) {
        if ($parentcontext->contextlevel == CONTEXT_USER) {
            $forcedownload = true;
        }
    } else {
        $forcedownload = true;
    }

    \core\session\manager::write_close();
    send_stored_file($file, null, 0, $forcedownload, $options);

}