jQuery(document).ready(function ($) {
    // Раскрытие меню до текущей страницы
    var currentLinkInMenu = $('#menu-left ul').find('a[href="' + window.location.href + '"]');
    currentLinkInMenu.addClass('active-link');
    currentLinkInMenu.parents('ul').each(function (index) {

        if (index === 0) {
            $(this).show();
        }
        else {
            $(this).find('li:first .btn-expand').addClass('btn-expanded');
            $(this).children().nextAll().show();
        }
    });

    // Кнопка показа меню на маленьких экранах
    $('#btn-toggle-left-menu').bind('click', function () {
        $('#menu-left').toggleClass('left-menu-full-screen');
        $('#menu-left').toggle();
    });

    // Раскрытие списка
    $('#menu-left .btn-expand').bind('click', function () {
        $(this).toggleClass('btn-expanded');
        $(this).parent().nextAll().toggle(200);
    });

    // Задаем высоту левого меню исходя из высоты контента
    function setHeightMenuLeft() {
        var menuLeft = $('#menu-left'),
            header = $('header'),
            windowHeight = $(window).height();
        if ($(window).scrollTop() > header.height()) {
            menuLeft.css({'height': windowHeight, 'max-height': ''});
        }
        else {
            menuLeft.css({'height': '', 'max-height': windowHeight});
        }
    }

    setHeightMenuLeft();

    // При измении размера окна считать высоту левого меню
    $(window).resize(function () {
        setHeightMenuLeft()
    });
    $(window).scroll(function () {
        setHeightMenuLeft()
    });
});