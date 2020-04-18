jQuery(document).ready(function ($) {
    var menuLeft = $('#menu-left');

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
            if ($(this).is('a')) {
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
        if ($('body').is('.menu-left-text-over'))
            $('body').find('.menu-left-text-over').remove();
    }

    menuLeft.find('a:only-child').hover(showTextOver, removeTextOver); // Для методов
    menuLeft.find('p').hover(showTextOver, removeTextOver); // Для категорий

    // Обновление положения элемента при скролле
    function updatePositionTextOver() {
        if ($('body').is('.menu-left-text-over')) {
            var offset = menuLeft.find('.text-over-target').offset();
            $('body .menu-left-text-over').css('top', offset.top + 2);
        }
    }

    $(window).scroll(updatePositionTextOver); // Обновляем положение при скролле страницы
    menuLeft.scroll(updatePositionTextOver); // Обновляем положение при скролле самого меню

    // Раскрытие меню до текущей страницы
    var currentLinkInMenu = menuLeft.find('ul').find('a[href="' + window.location.href.split('?')[0] + '"]');
    currentLinkInMenu.parent().addClass('active-link');
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
        var content = menuLeft.next();

        menuLeft.toggleClass('left-menu-full-screen');
        menuLeft.toggle();
        content.toggle();
    });

    // Раскрытие списка
    menuLeft.find('.btn-expand').bind('click', function () {
        $(this).toggleClass('btn-expanded');
        $(this).parent().nextAll().toggle(200);
    });

    // Задаем высоту левого меню исходя из высоты контента
    function setHeightMenuLeft() {
        var content = menuLeft.next(),
            adminBarHeight = 0,
            header = $('header'),
            footer = $('footer'),
            windowHeight = $(window).height(),
            windowWidth = $(window).width(),
            windowScrollTop = $(window).scrollTop();

        if ($('div').is('#wpadminbar')) {
            adminBarHeight = $('#wpadminbar').height();
        }

        if (windowWidth > 768) {
            if (windowScrollTop > header.height() + adminBarHeight && content.height() >= windowHeight) {
                menuLeft.css({'height': windowHeight, 'max-height': ''});
            }
            else if (content.height() >= windowHeight) {
                menuLeft.css({
                    'height': '',
                    'max-height': windowHeight - header.height() - adminBarHeight + windowScrollTop
                });
            }
            else {
                menuLeft.css({'height': '', 'max-height': content.height()});
            }
        }
        else {
            menuLeft.css({'height': '100%', 'max-height': ''});
        }
    }

    setHeightMenuLeft();

    // При измении размера окна, при скролле, и изменении контента считать высоту левого меню
    $(window).resize(function () {
        setHeightMenuLeft()
    });
    $(window).scroll(function () {
        setHeightMenuLeft()
    });
    window.addEventListener('resizeContent',function () {
        setHeightMenuLeft()
    })
});