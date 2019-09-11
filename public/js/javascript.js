$(document).ready(function() {
	$('[data-toggle="tooltip"]').tooltip();
    $('.carousel').carousel();

    $('#toTop').on('click',function (e) {
        e.preventDefault();
        var target = this.hash;
        var $target = $(target);
        $('html, body').stop().animate({
            'scrollTop': 0
        }, 900, 'swing');
    });

    $(document).on("click", 'button', function(e) {
        if ($(this).hasClass("disabled")) {
            e.preventDefault();
            return true;
        }
    });

    var windowTop = $(window).scrollTop();

    //$("header").css("background-position-y", (windowTop / 3) - 180);

    /*$(window).scroll(function() {
        windowTop = $(window).scrollTop();
        $("header").css("background-position-y", (windowTop / 3) - 180);
    });*/

    $('ul.navbar-nav li.dropdown').hover(function() {
        $(this).find('.dropdown-menu').stop(true, true).fadeIn(100);
    }, function() {
        $(this).find('.dropdown-menu').stop(true, true).fadeOut(100);
    });

    var toggled = false;
    var themebox = $('.themebox');

    $('.theme').click(function(event) {
        if (!toggled) {
            themebox.stop(true, true).slideDown();
            toggled = true;
        } else {
            themebox.stop(true, true).slideUp();
            toggled = false;
        }
    });
});
