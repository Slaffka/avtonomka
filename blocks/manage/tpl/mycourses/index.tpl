<h3>Мои курсы</h3>
<div class="lm-mycourses-standard clearfix">
    {if $courses}
        {foreach from=$courses item=course}
            <div class="lm-mycourse clearfix">
                <div class="lm-course-image">
                    <a href="/course/view.php?id={$course->id}" target="_blank">
                        {$course->image}
                    </a>
                </div>
                <div class="lm-course-name">
                    <span class="valign-middle">
                        <a href="/course/view.php?id={$course->id}" target="_blank">{$course->fullname}</a>
                    </span>
                </div>
                <div class="lm-course-progress">
                    <div class="lm-indicator-wrapper">
                        {$progress = 0}
                        {if $course->progress >= 100}
                            {$progress = 100}
                        {elseif $course->progress >= 90}
                            {$progress = 75}
                        {elseif $course->progress >= 80}
                            {$progress = 50}
                        {elseif $program->progress > 0}
                            {$progress = 25}
                        {/if}
                        <div class="lm-indicator lm-indicator{$progress}"></div>
                    </div>
                </div>
            </div>
        {/foreach}
    {else}
        <div class="lm-mycourses-empty">
            У вас нет назначенных курсов
        </div>
    {/if}
</div>