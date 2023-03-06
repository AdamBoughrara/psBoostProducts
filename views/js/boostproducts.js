$(document).ready(function () {
    $(document).on('click', '.boost-product', function (e) {
        e.preventDefault();
        var id_product = $(this).data('id-product');
        var boost_status = $(this).data('boost-status');
        $.ajax({
            type: 'POST',
            url: baseUri + 'modules/boostproducts/ajax_boost.php',
            data: {
                'id_product': id_product,
                'boost_status': boost_status,
            },
            success: function (data) {
                location.reload();
            },
        });
    });
});
