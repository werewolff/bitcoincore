jQuery(document).ready(function ($) {
    if ($('table').is('.table-sticky')) {
        var headMainTable = $('.table-sticky thead');
        var offsetMainTable = headMainTable.offset();
        var categoryMainTable = $('.table-sticky').find('tbody th');
        var offsetCategories = [];
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

    // Обработка события onDrop для сортировки
    function onDropSortable($item, container, _super) {
        var newIndex = $item.index(); // Новый индекс
        if (newIndex !== oldIndex) { // Если изменился индекс
            var i = 0,
                prevOrder = parseInt($item.children('a').attr('data-order')),
                nextOrder,
                changeOrder = {},
                prevValue = '';
            if (newIndex < oldIndex) { // переместили назад
                // опеределяем список сдвинутых элементов
                for (i = oldIndex; i > newIndex; i--) {
                    var element = $(container.items[i - 1]);
                    var order = parseInt(element.children('a').attr('data-order'));
                    if (i === oldIndex) {
                        changeOrder[order] = prevOrder;
                    } else {
                        changeOrder[order] = prevValue;
                    }
                    prevValue = order;
                    nextOrder = order;
                }
            } else if (newIndex > oldIndex) { // переместили вперед
                // опеределяем список сдвинутых элементов
                for (i = oldIndex; i < newIndex; i++) {
                    element = $(container.items[i]); // текущий перетаскиваемый элемент
                    order = parseInt(element.children('a').attr('data-order')); // его значение порядка в базе
                    if (i === oldIndex) { // Для первого элемента
                        changeOrder[order] = prevOrder;
                        element.children('a').attr('data-order', prevOrder);
                    } else { // Для остальных
                        changeOrder[order] = prevValue;
                        element.children('a').attr('data-order', prevValue);
                    }
                    prevValue = order;
                    nextOrder = order;
                }
            }
            $($item).children('a').attr('data-order', nextOrder); // задаем новый номер сортировки
            changeOrder[prevOrder] = nextOrder; // Добавляем сам перетаскиваемый элемент

            // Перемещаем остальные строки
            $item.closest('table').find('tbody tr').each(function (i, row) {
                row = $(row);
                if (newIndex < oldIndex) {
                    row.children().eq(newIndex).before(row.children()[oldIndex]);
                } else if (newIndex > oldIndex) {
                    row.children().eq(newIndex).after(row.children()[oldIndex]);
                }
            });

            var request = $.ajax({
                type: "POST",
                data: {
                    action: 'order_version',
                    change_order: changeOrder
                },
                dataType: "html"
            });

            request.done(function () {
            });

            request.fail(function () {
                alert('Ошибка при изменение порядка версий. Страница будет перезагружена');
                window.location.reload();
            });

        }

        _super($item, container);
    }

    //for sortable versions
    var oldIndex;
    $('.sorted_head tr').sortable({
        containerPath: 'thead',
        containerSelector: 'tr',
        itemSelector: 'th',
        placeholder: '<th class="sortable-placeholder"/>',
        vertical: false,
        exclude: '.not-sortable',
        onDragStart: function ($item, container, _super) {
            oldIndex = $item.index();
            $item.appendTo($item.parent());
            _super($item, container);
        },
        onDrop: onDropSortable
    });

    function editBarToggle() {
        $('.edit-bar').hide();
        $('[id^=edit-method-]').bind('click', function () {
            var id = $(this).attr('id');
            var idMethod = id.match(/\d+/)[0];
            $('#edit-bar-' + idMethod).toggle(300);
        });
        $('.btn-hide-edit').bind('click', function () {
            var target = $(this).attr('target');
            var idMethod = target.match(/\d+/)[0];
            $('#edit-method-' + idMethod).trigger('click');
        })
    }

    function addBarToggle() {
        $('.btn-hide-add').bind('click', function () {
            $('#add-method').collapse('hide');
        })
    }

    editBarToggle();
    addBarToggle();

    //edit-version
    $('[id^=edit-version-]').bind('click', function () {
        var parent = $(this).parent();
        var cell = parent.parent();
        var prevWidthCell = parseInt(cell.width());
        var paddingCell = parseInt(cell.css('padding-left')) + parseInt(cell.css('padding-right')) + 4;
        prevWidthCell += paddingCell;
        var widthInputVersion = prevWidthCell - paddingCell - 25 * 2;
        cell.css({'width': prevWidthCell});
        parent.prev().hide();
        parent.hide();
        parent.next().find('input[name=name]').css({'width': widthInputVersion});
        parent.next().show();
        parent.next().find('.btn-edit-cancel').bind('click', function () {
            var parentDiv = $(this).closest('div');
            parentDiv.hide();
            parentDiv.prevAll().show();
        })
    });
    //delete version
    $('[id^=delete-version-]').bind('click', function () {
        var id = $(this).attr('id');
        var idVersion = id.match(/\d+/)[0];
        var nameVersion = $(this).parent().prev().text();
        var confirmDel = confirm("Удалить версию: " + nameVersion + "?");
        if (confirmDel) {
            var request = $.ajax({
                type: "POST",
                data: {
                    action: 'delete_version',
                    id: idVersion
                },
                dataType: "html"
            });

            request.done(function () {
                window.location.reload();
            });

            request.fail(function () {
                alert("Ошибка при удалении версии: " + nameVersion);
            });
        }

    });
    //edit-category
    $('[id^=edit-category-]').bind('click', function () {
        var parent = $(this).parent();
        parent.prev().hide();
        parent.hide();
        parent.next().show();
        parent.next().find('.btn-edit-cancel').bind('click', function () {
            var parentDiv = $(this).closest('div');
            parentDiv.hide();
            parentDiv.prevAll().show();
        })
    });
    //delete-category
    $('[id^=delete-category-]').bind('click', function () {
        var id = $(this).attr('id');
        var idCategory = id.match(/\d+/)[0];
        var nameCategory = $(this).parent().prev().text();
        var confirmDel = confirm("Удалить категорию: " + nameCategory + "?" + "\nВНИМАНИЕ! Будут удалены ВСЕ методы в данной категории, а также данная категория и методы удалятся в ДРУГИХ блокчейнах!");
        if (confirmDel) {
            var request = $.ajax({
                type: "POST",
                data: {
                    action: 'delete_category',
                    id: idCategory
                },
                dataType: "html"
            });

            request.done(function () {
                window.location.reload();
            });

            request.fail(function () {
                alert("Ошибка при удалении версии: " + nameCategory);
            });
        }

    });
    //delete method
    $('[id^=delete-method-]').bind('click', function () {
        var id = $(this).attr('id');
        var matches = id.match(/\d+/g);
        var idMethod = matches[0];
        var idCategory = matches[1];
        var idBlockchain = matches[2];
        var nameMethod = $(this).parent().prev().text();
        var request = $.ajax({
            async: false,
            type: "POST",
            data: {
                action: 'delete_method',
                id: idMethod,
                category_id: idCategory,
                blockchain_id: idBlockchain
            },
            dataType: "html"
        });

        request.done(function () {
            window.location.reload();
        });

        request.fail(function () {
            alert("Ошибка при удалении метода: " + nameMethod);
        });

    });
    //edit blockchain
    $('[id^=edit-blockchain-]').bind('click', function () {
        var parent = $(this).parent();
        parent.prev().hide();
        parent.hide();
        parent.next().show();
        parent.next().find('.btn-edit-cancel').bind('click', function () {
            var parentDiv = $(this).closest('div');
            parentDiv.hide();
            parentDiv.prevAll().show();
        })
    });
    //delete blockchain
    $('[id^=delete-blockchain-]').bind('click', function () {
        var id = $(this).attr('id');
        var idBlockchain = id.match(/\d+/)[0];
        var nameBlockchain = $(this).parent().prev().text();
        var request = $.ajax({
            async: false,
            type: "POST",
            data: {
                action: 'delete_blockchain',
                id: idBlockchain
            },
            dataType: "html"
        });

        request.done(function () {
            window.location.reload();
        });

        request.fail(function () {
            alert("Ошибка при удалении блокчейна: " + nameBlockchain);
        });

    });

});
