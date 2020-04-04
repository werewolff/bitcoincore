jQuery(document).ready(function ($) {

    $('.versions h3').click(function () {
       $(this).toggleClass('show');
       $(this).next('dl').toggle();
    });

    if ($('table').is('.table-sticky')) {
        var headMainTable = $('.table-sticky thead');
        var offsetMainTable = headMainTable.offset();
        var categoryMainTable = $('.table-sticky').find('tbody th');
        var offsetCategories = new Array();
        categoryMainTable.each(function (i) {
            offsetCategories[i] = $(this).offset();
        });

        if (!headMainTable[0]) {
            headMainTable[0] = {
                offsetHeight: 0
            };
        }

        function stickyTopCategories(categories, offset, head) {
            var length = categories.length;
            categories.each(function (i) {
                var scrollTop = $(window).scrollTop();
                var translateY = scrollTop + head[0].offsetHeight - offset[i].top - 3;
                if (i === (length - 1)) {
                    if (scrollTop >= offset[i].top) {
                        $(this).css({
                            'transform': 'translateY(' + translateY + 'px)',
                            'background-color': '#e9ecef'
                        });
                    } else {
                        $(this).css({
                            'transform': 'translateY(0)',
                            'background-color': 'initial'
                        });
                    }
                } else {
                    if (scrollTop >= offset[i].top - head[0].offsetHeight && scrollTop < offset[i + 1].top - categoryMainTable[i].offsetHeight / 2) {
                        $(this).css({
                            'transform': 'translateY(' + translateY + 'px)',
                            'background-color': '#e9ecef'
                        });
                    }
                    else {
                        $(this).css({
                            'transform': 'translateY(0)',
                            'background-color': 'initial'
                        });
                    }
                }
            })
        }

        function stickyTop(element, offset) {
            var scrollTop = $(window).scrollTop();
            var translateY = scrollTop - offset.top - 1;
            if (scrollTop >= offset.top) {
                element.css('transform', 'translateY(' + translateY + 'px)');
            }
            else {
                element.css('transform', 'translateY(0)');
            }
        }

        if ($(window).scrollTop() > 0) {
            if (headMainTable[0].offsetHeight > 0)
                stickyTop(headMainTable, offsetMainTable);
            stickyTopCategories(categoryMainTable, offsetCategories, headMainTable);
        }
        $(window).scroll(function () {
            if (headMainTable[0].offsetHeight > 0)
                stickyTop(headMainTable, offsetMainTable);
            stickyTopCategories(categoryMainTable, offsetCategories, headMainTable);
        });
    }
});