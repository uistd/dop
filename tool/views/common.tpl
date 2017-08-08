<script>
    function list_event(callback) {
        $('.list-group-item').click(function () {
            console.log(this);
            var target_node = this;
            $('.list-group-item').each(function (i, item) {
                var $item = $(item);
                if (item === target_node) {
                    $item.addClass('active');
                    if ('function' === typeof callback) {
                        callback($item);
                    }
                } else {
                    $item.removeClass('active');
                }
            });
        }).first().trigger('click');
    }

    function disable_btn(btn)
    {
        btn.html('loading..').removeClass('btn-primary');
    }

</script>