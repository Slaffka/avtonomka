<?php


// Get the HTML for the settings bits.
$html = theme_tibibase_get_html_for_settings($OUTPUT, $PAGE);

if (right_to_left()) {
    $regionbsid = 'region-bs-main-and-post';
} else {
    $regionbsid = 'region-bs-main-and-pre';
}

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


<?php include(dirname(__FILE__) . '/includes/header.php'); ?>



<div id="page">
    <header id="page-header" class="clearfix">
        <?php /*echo $html->heading;*/ ?>
        <div id="page-navbar" class="clearfix">
            <nav class="breadcrumb-nav"><?php echo $OUTPUT->navbar(); ?></nav>
            <div class="breadcrumb-button"><?php echo $OUTPUT->page_heading_button(); ?></div>
        </div>
        <div id="course-header">
            <?php echo $OUTPUT->course_header(); ?>
        </div>
    </header>

    <div id="page-content" class="row-fluid">
        <?php if($pagecode != 'index'){ ?>
        <div id="<?php echo $regionbsid ?>" class="span9">
            <div class="row-fluid">
                <section id="region-main" class="span8 pull-right">
                    <?php
                    echo $OUTPUT->course_content_header();
                    echo $OUTPUT->main_content();
                    echo $OUTPUT->course_content_footer();
                    ?>
                </section>
                <?php echo $OUTPUT->blocks('side-pre', 'span4 desktop-first-column'); ?>
            </div>
        </div>
        <?php echo $OUTPUT->blocks('side-post', 'span3'); ?>
        <?php }else{ ?>
            <div role="main" style="display: none">
                [MAIN CONTENT GOES HERE]
            </div>
        <?php } ?>
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
