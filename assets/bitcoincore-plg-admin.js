jQuery(document).ready(function ($) {
    function editBarToggle() {
        $('.edit-bar').hide();
        $('.edit-bar-btn').bind('click', function () {
            var id = $(this).attr('target');
            $('#' + id).toggle(300);
        });
    }

    function addBarToggle() {
        $('.form-add').hide();
        $('.btn-show-add').bind('click', function () {
            var id = $(this).attr('target');
            $(this).toggleClass('btn-active');
            if (id == 'form-add') {
                $('.' + id).toggle(300)
            } else
                $('#' + id).toggle(300);
        });
        $('.btn-hide-add').bind('click', function () {
            var target = $(this).attr('target');
            $('.btn-show-add[target= ' + target + ']').trigger('click', $(this).attr('target'));
        })
    }

    editBarToggle();
    addBarToggle();
});
