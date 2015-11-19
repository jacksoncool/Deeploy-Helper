$(document).ready(function(){

    $('form input').change(function(){
        $(this).css('color', '#red');
    });

    $('.promotion').change(function(){
        $(this).css('color', '#red');
    });

    $('#quick_replace').click(function(){
        $('form input').each(function(){
            var old = $(this).val();
            $(this).val($(this).val().replace($('#find').val(), $('#replace').val()));
            if ($(this).val() != old)
            {
                $(this).change();
            }
        });
    });
});
