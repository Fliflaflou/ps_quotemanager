$(document).ready(function() {
    var quoteId = $('#quote-id').val() || getUrlParameter('id_quote');
    
    // Toggle add product form
    $('#add-product-btn').on('click', function() {
        $('#add-product-form').toggle();
    });
    
    // Initialize product search with Select2
    $('#product-search').select2({
        ajax: {
            url: currentIndex + '&token=' + token + '&ajax=1&action=searchProducts',
            dataType: 'json',
            delay: 250,
            data: function(params) {
                return {
                    q: params.term
                };
            },
            processResults: function(data) {
                return {
                    results: data
                };
            }
        },
        placeholder: translations.searchProducts,
        minimumInputLength: 2
    });
    
    // When product is selected, fill prices
    $('#product-search').on('select2:select', function(e) {
        var data = e.params.data;
        if (data.price) {
            var priceExcl = parseFloat(data.price);
            $('#product-price-excl').val(priceExcl.toFixed(2));
            if (data.price_tax_incl !== undefined) {
                $('#product-price-incl').val(parseFloat(data.price_tax_incl).toFixed(2));
            } else {
                $('#product-price-incl').val('');
            }
        }
    });
    
    // Add product to quote
    $('#add-product-confirm').on('click', function() {
        var productId = $('#product-search').val();
        var quantity = $('#product-quantity').val();
        var priceExcl = $('#product-price-excl').val();
        var priceIncl = $('#product-price-incl').val();
        
        if (!productId || !quantity || !priceExcl || !priceIncl) {
            showError(translations.fillAllFields);
            return;
        }
        
        $.ajax({
            url: currentIndex + '&token=' + token + '&ajax=1&action=addProductToQuote',
            type: 'POST',
            data: {
                id_quote: quoteId,
                id_product: productId,
                quantity: quantity,
                price_tax_excl: priceExcl,
                price_tax_incl: priceIncl
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    showSuccess(data.message);
                    location.reload(); // Reload to show new product
                } else {
                    showError(data.error);
                }
            },
            error: function() {
                showError(translations.errorOccurred);
            }
        });
    });
    
    // Remove product from quote
    $('.remove-product-btn').on('click', function() {
        if (!confirm(translations.confirmRemove)) {
            return;
        }
        
        var quoteProductId = $(this).data('quote-product-id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: currentIndex + '&token=' + token + '&ajax=1&action=removeProductFromQuote',
            type: 'POST',
            data: {
                id_quote_product: quoteProductId
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    showSuccess(data.message);
                    row.fadeOut(function() {
                        $(this).remove();
                        updateTotals();
                    });
                } else {
                    showError(data.error);
                }
            },
            error: function() {
                showError(translations.errorOccurred);
            }
        });
    });
    
    // Update quantity
    $('.quantity-input').on('change', function() {
        var quoteProductId = $(this).data('quote-product-id');
        var quantity = $(this).val();
        
        if (quantity < 1) {
            $(this).val(1);
            return;
        }
        
        $.ajax({
            url: currentIndex + '&token=' + token + '&ajax=1&action=updateProductQuantity',
            type: 'POST',
            data: {
                id_quote_product: quoteProductId,
                quantity: quantity
            },
            success: function(response) {
                var data = JSON.parse(response);
                if (data.success) {
                    showSuccess(data.message);
                    location.reload(); // Reload to update totals
                } else {
                    showError(data.error);
                }
            },
            error: function() {
                showError(translations.errorOccurred);
            }
        });
    });
    
    // Helper functions
    function showSuccess(message) {
        $.growl.notice({ title: "Success", message: message });
    }
    
    function showError(message) {
        $.growl.error({ title: "Error", message: message });
    }
    
    function getUrlParameter(name) {
        name = name.replace(/[\[]/, '\\[').replace(/[\]]/, '\\]');
        var regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        var results = regex.exec(location.search);
        return results === null ? '' : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }
});

// Translations (to be set by the controller)
var translations = {
    searchProducts: 'Type to search products...',
    fillAllFields: 'Please fill all fields',
    confirmRemove: 'Are you sure you want to remove this product?',
    errorOccurred: 'An error occurred'
};
