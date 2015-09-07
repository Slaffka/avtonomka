
<div class="snapshot-master">
    <div class="snapshot-normal">
        <div class="clearfix">
            <div id="takeSnapshot"><i class="icon-photo"></i></div>
            <span class="save-button hide" id="saveButton" data-action="/mod/userverifier/complete.php?id=<?php echo $id; ?>"><i class="icon-checkmark"></i></span>
        </div>
        <div id="webcam"></div>
        <canvas id="canvas" class="hide" width="320" height="240" ></canvas>
    </div>

    <!-- Для мобильных устройств, которые не поддерживают getUserMedia -->
    <form class="snapshot-imitation hide" action="/camera/index.html" method="POST" enctype="multipart/form-data">
			<span class="fileinput-button">
				<i class="icon-photo"></i>
				<!-- The file input field used as target for the file upload widget -->
				<input type="file" name="file" id="file-input">
			</span>
        <span class="save-button hide" data-action="/mod/userverifier/complete.php?id=<?php echo $id; ?>"><i class="icon-checkmark"></i></span>

        <div class="snapshot-hint">Нажмите на камеру</div>
        <div class="preloader hide"></div>
        <div class="snapshot-preview">
        </div>
        <canvas class="hide" width="320" height="240"></canvas>
    </form>
</div>

<link rel="stylesheet" href="/mod/userverifier/camera/demo.css">
<!--[if IE]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
<!--[if IE]><script src="/mod/userverifier/camera/js/excanvas.js"></script><![endif]-->
<!--<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>-->
<script src="/mod/userverifier/camera/js/load-image.all.min.js"></script>
<script type="text/javascript" src="/mod/userverifier/camera/js/jquery.webcam.js"></script>
<script type="text/javascript" src="/mod/userverifier/camera/js/imagepreview.js"></script>
<script type="text/javascript" src="/mod/userverifier/camera/js/app.js"></script>

