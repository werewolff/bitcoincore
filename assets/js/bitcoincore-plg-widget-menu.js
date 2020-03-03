jQuery(document).ready(function ($) {
    // Раскрытие списка
    $('.menu-left .btn-expand').bind('click', function () {
        $(this).toggleClass('btn-expanded');
        $(this).parent().nextAll().toggle(200);
    })

    // Задаем высоту левого меню исходя из высоты контента
    function setHeightMenuLeft() {
        var contentHeight = $('body > section').height()
        var menuLeft = $('.menu-left');
        menuLeft.innerHeight(contentHeight);
        menuLeft.children('div').innerHeight(contentHeight);
    }

    setHeightMenuLeft();

    // При изменении окна считать высоту левого меню
    $(window).resize(function () {
        setHeightMenuLeft();
    });
});