jQuery(document).ready(function ($) {
    // Для названий которые не влезают
    function showTextOver() {
        if ($(this)[0].scrollWidth > $(this).innerWidth()) {
            var color = $(this).parents('#menu-left').css('color'),
                backgroundColor = $(this).parents('#menu-left').css('background-color'),
                offset = $(this).offset(),
                style = {
                    position: 'absolute',
                    top: offset.top + 2,
                    left: offset.left,
                    'font-weight': 500,
                    'background-color': backgroundColor,
                    color: color,
                    'pointer-events': 'none',
                    'z-index': '101'
                };
            if($(this).is('a')){
                style.color = $(this).css('color');
            }
            $(this)
                .clone()
                .appendTo('body')
                .css(style)
                .addClass('menu-left-text-over');
            $(this).addClass('text-over-target') // Для отслеживания
        }
    }
    // Удалить наложеный элемент
    function removeTextOver() {
        $('body').find('.menu-left-text-over').remove();
    }

    $('#menu-left a:only-child').hover(showTextOver, removeTextOver); // Для методов
    $('#menu-left p').hover(showTextOver, removeTextOver); // Для категорий

    // Обновление положения элемента при скролле
    function updatePositionTextOver() {
        var offset = $('#menu-left .text-over-target').offset();
        $('body .menu-left-text-over').css('top', offset.top + 2);
    }

    $(window).scroll(updatePositionTextOver); // Обновляем положение при скролле страницы
    $('#menu-left').scroll(updatePositionTextOver); // Обновляем положение при скролле самого меню

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