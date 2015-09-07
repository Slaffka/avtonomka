<?php

// Get the HTML for the settings bits.
$html = theme_twogis_get_html_for_settings($OUTPUT, $PAGE);

if (right_to_left()) {
    $regionbsid = 'region-bs-main-and-post';
} else {
    $regionbsid = 'region-bs-main-and-pre';
}

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>"/>
    <?php echo $OUTPUT->standard_head_html() ?>
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot;?>/theme/tibibase/fonts/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>
<?php include(dirname(__FILE__) . '/includes/defines.php'); ?>


<?php include(dirname(__FILE__) . '/includes/header.php'); ?>

<div id="page">
    <div id="page-content">
        <div role="main" class="clearfix">
            [MAIN CONTENT GOES HERE]
        </div>
        <?php echo $OUTPUT->blocks('side-pre', 'gridcol1'); ?>
    </div>


    <footer id="page-footer">
        <div id="course-footer"><?php echo $OUTPUT->course_footer(); ?></div>
        <?php
        echo $html->footnote;
        ?>
    </footer>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>
