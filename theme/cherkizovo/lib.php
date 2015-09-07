<?php


/**
 * Returns an object containing HTML for the areas affected by settings.
 *
 * Do not add Clean specific logic in here, child themes should be able to
 * rely on that function just by declaring settings with similar names.
 *
 * @param renderer_base $output Pass in $OUTPUT.
 * @param moodle_page $page Pass in $PAGE.
 * @return stdClass An object with the following properties:
 *      - navbarclass A CSS class to use on the navbar. By default ''.
 *      - heading HTML to use for the heading. A logo if one is selected or the default heading.
 *      - footnote HTML to use as a footnote. By default ''.
 */
function theme_cherkizovo_get_html_for_settings(renderer_base $output, moodle_page $page) {
    global $CFG;
    $return = new stdClass;

    $return->heading = $output->page_heading();


    return $return;
}