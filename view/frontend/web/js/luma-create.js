define([
    'jquery',
    'mage/translate',
    'mage/validation'
], function ($, $t) {
    'use strict';

    return function (config, element) {
        var $form = $(element);
        var $items = $form.find('.rma-items');
        var $fieldset = $form.find('.rma-items-fieldset');
        var $message = $form.siblings('.rma-form-message');

        $form.validation();

        function showError(message) {
            $message.find('span').text(message);
            $message.show();
        }

        function optionList(options) {
            var $fragment = $(document.createDocumentFragment());
            $fragment.append($('<option>').val('').text($t('Please select')));
            $.each(options, function (index, option) {
                $fragment.append($('<option>').val(option.value).text(option.label));
            });
            return $fragment;
        }

        function renderItems(items) {
            $items.empty();
            $.each(items, function (index, item) {
                var unavailable = item.is_excluded || item.is_expired || item.qty_available <= 0;
                var $row = $('<div class="rma-item-row">').attr('data-item-id', item.item_id);
                var $choice = $('<input type="checkbox" class="checkbox rma-item-choice">')
                    .prop('disabled', unavailable);
                var $label = $('<strong>').text(item.name + ' (' + item.sku + ')');
                var $qty = $('<input type="number" class="input-text rma-item-qty" min="1" step="1" value="1">')
                    .attr('max', item.qty_available).prop('disabled', true);
                var $reason = $('<select class="select rma-item-reason">').prop('disabled', true)
                    .append(optionList(config.reasons));
                var $condition = $('<select class="select rma-item-condition">').prop('disabled', true)
                    .append(optionList(config.conditions));

                $choice.on('change', function () {
                    $row.find('input, select').not(this).prop('disabled', !this.checked);
                });
                $row.append($('<div class="rma-item-title">').append($choice, $label));
                $row.append($('<div class="rma-item-controls">')
                    .append($('<label>').text($t('Qty')).append($qty))
                    .append($('<label>').text($t('Return Reason')).append($reason))
                    .append($('<label>').text($t('Item Condition')).append($condition)));
                if (unavailable) {
                    $row.append($('<div class="message notice">').text(
                        item.is_expired ? $t('The return window has expired.') : $t('This item cannot be returned.')
                    ));
                }
                $items.append($row);
            });
            $fieldset.show();
        }

        $form.on('change', '.rma-order', function () {
            $fieldset.hide();
            $items.empty();
            $message.hide();
            $.getJSON(config.itemsUrl, {order_id: this.value})
                .done(function (response) {
                    if (response.error) {
                        showError(response.message || config.messages.loadError);
                        return;
                    }
                    renderItems(response.items || []);
                })
                .fail(function () { showError(config.messages.loadError); });
        });

        $form.on('submit', function (event) {
            event.preventDefault();
            $message.hide();
            if (!$form.validation('isValid')) {
                return;
            }

            var selected = [];
            var invalid = false;
            $items.find('.rma-item-choice:checked').each(function () {
                var $row = $(this).closest('.rma-item-row');
                var reason = $row.find('.rma-item-reason').val();
                var condition = $row.find('.rma-item-condition').val();
                if (!reason || !condition) {
                    invalid = true;
                    return;
                }
                selected.push({
                    order_item_id: Number($row.data('item-id')),
                    qty: Number($row.find('.rma-item-qty').val()),
                    reason_id: Number(reason),
                    condition_id: Number(condition)
                });
            });
            if (invalid || !selected.length) {
                showError(config.messages.required);
                return;
            }

            $form.find('[name="items"]').val(JSON.stringify(selected));
            var formData = new FormData($form[0]);
            var $button = $form.find('button[type="submit"]').prop('disabled', true);
            $.ajax({url: $form.attr('action'), type: 'POST', data: formData, processData: false, contentType: false, dataType: 'json'})
                .done(function (response) {
                    if (response.success) {
                        window.location.assign(response.redirect_url);
                    } else {
                        showError(response.message || config.messages.networkError);
                    }
                })
                .fail(function () { showError(config.messages.networkError); })
                .always(function () { $button.prop('disabled', false); });
        });
    };
});
