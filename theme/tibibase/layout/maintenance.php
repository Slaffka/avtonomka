<?php


// Get the HTML for the settings bits.
$html = theme_tibibase_get_html_for_settings($OUTPUT, $PAGE);

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <link rel="stylesheet" href="<?php echo $CFG->wwwroot;?>/theme/tibibase/fonts/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1.0, user-scalable=no">
</head>

<body <?php echo $OUTPUT->body_attributes(); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>
<?php include(dirname(__FILE__).'/includes/defines.php'); ?>
<?php include(dirname(__FILE__).'/includes/bnav.php'); ?>

<div id="page">

    <header id="page-header" class="clearfix">
        <?php echo $html->heading; ?>
    </header>

    <div id="page-content" class="row-fluid">
        <section id="region-main" class="span12">
            <?php echo $OUTPUT->main_content(); ?>
        </section>
    </div>

    <footer id="page-footer">
        <?php
        /*echo $OUTPUT->standard_footer_html();*/
        ?>
    </footer>

    <?php echo $OUTPUT->standard_end_of_body_html() ?>

</div>
</body>
</html>
