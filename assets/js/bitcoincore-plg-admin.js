jQuery(document).ready(function ($) {
    //for sortable versions
    $(function () {
        $(".sortable tbody").sortable({
            connectWith: "edit-bar",
            cursor: 'move',
            axis: "y",
            helper: function (e, ui) {
                ui.children().each(function () {
                    $(this).width($(this).width());
                });
                return ui;
            },
            forceHelperSize: true,
            forcePlaceholderSize: true,
            revert: true,
            placeholder: 'sortable-placeholder'
        });
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

            request.done(function (msg) {
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
                id: idBlockchain,
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
