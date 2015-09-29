console.log('test')

$().ready(function () {
    $('.child').hide();
    $('.parent').css('cursor', 'pointer');
    $('.parent').click(function () {
        if ($(this).attr('src') == 'images/minus.png') {
            $(this).attr('src','images/plus.png');
            $('.child' + $(this).attr('value')).hide();
        } else {
            $('.child' + $(this).attr('value')).show();
            $(this).attr('src', 'images/minus.png');
        }
    });
});