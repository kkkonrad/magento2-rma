define(['jquery', 'mage/translate'], function ($, $t) {
    'use strict';

    return function (config, element) {
        var $form = $(element);
        $form.on('submit', function (event) {
            event.preventDefault();
            if (config.confirm && !window.confirm($form.data('confirm-message'))) {
                return;
            }
            var $button = $form.find('button[type="submit"]').prop('disabled', true);
            $.ajax({url: $form.attr('action'), type: 'POST', data: $form.serialize(), dataType: 'json'})
                .done(function (response) {
                    if (response.success) {
                        window.location.reload();
                        return;
                    }
                    $('.rma-view-message').prop('hidden', false).find('span')
                        .text(response.message || $t('An error occurred.'));
                })
                .fail(function () {
                    $('.rma-view-message').prop('hidden', false).find('span').text($t('Network error. Please try again.'));
                })
                .always(function () { $button.prop('disabled', false); });
        });
    };
});
