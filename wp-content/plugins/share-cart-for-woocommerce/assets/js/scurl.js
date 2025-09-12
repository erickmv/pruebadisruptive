jQuery(document).ready(function ($) {
    $('#share-cart-btn').on('click', function (e) {
        e.preventDefault();

        $.post(share_cart_ajax.ajax_url, { action: 'generate_share_link' }, function (response) {
            if (response.success) {
                var shareUrl = response.data.url;

                if (navigator.clipboard && window.isSecureContext) {
                    navigator.clipboard.writeText(shareUrl).then(function () {
                        $('#share-cart-url').html('<p>Link copied to clipboard: ' + shareUrl + '</p>');
                        $('#share-cart-btn').hide();
                    });
                } else {
                    var tempInput = $('<input>').val(shareUrl).appendTo('body').select();
                    document.execCommand("copy");
                    tempInput.remove();

                    $('#share-cart-url')
                        .html('<span>Link copied to clipboard - ' + shareUrl + '</span>')
                        .css({
                            'margin-bottom': '10px',
                            'background': 'rgb(241, 241, 241)',
                            'padding': '0.6em 1em'
                        });

                    $('#share-cart-btn').hide();
                }
            }
        });
    });

    // Reset the share link when the cart updates
    $(document.body).on('updated_wc_div', function () {
        $('#share-cart-url').empty();
        $('#share-cart-btn').show();
    });
});
