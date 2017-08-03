<script>
    function list_event() {
        $('.list-group-item').click(function () {
            var target_node = this;
            $('.list-group-item').each(function (i, item) {
                var $item = $(item);
                if (item === target_node) {
                    $item.addClass('active');
                } else {
                    $item.removeClass('active');
                }
            });
        });
    }
</script>