<div class="feedback_widget">
    {$form_addticket}
    {$form_fileupload}
    <div class="info_files">
        <span class="icon-paperclip"></span>
        <div class="upload-alert {if !$files}hide{/if}">
           Прикреплено файлов <span class="count-files"> {$files} </span>
        </div>
    </div>
    {*<div id="files" class="files">
        {if $files}
            {foreach $files as $file}
                {$file->ext}
            {/foreach}
        {/if}
    </div>*}
    <div id="progress" class="progress">
        <div class="progress-bar progress-bar-success"></div>
    </div>
</div>
