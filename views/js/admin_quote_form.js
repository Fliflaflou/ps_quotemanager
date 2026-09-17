$(document).ready(function() {
    $('#quote-add-customer-btn').fancybox({
        type: 'iframe',
        width: '90%',
        height: '90%'
    });
});

function setupCustomer(customerId) {
    var $customerSelect = $('#id_customer');

    if (!$customerSelect.length || !customerId) {
        return;
    }

    var selector = 'option[value="' + customerId + '"]';
    if (!$customerSelect.find(selector).length) {
        $customerSelect.append(
            $('<option>', {
                value: customerId,
                text: 'Nouveau client #' + customerId
            })
        );
    }

    $customerSelect.val(String(customerId)).trigger('change');
}