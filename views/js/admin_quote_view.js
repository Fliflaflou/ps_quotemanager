// views/js/admin_quote_view.js
function duplicateQuote(id_quote) {
    if (confirm('Dupliquer ce devis ?')) {
        // ✅ UTILISE TA MÉTHODE duplicate() DE LA CLASSE Quote
        $.post(currentIndex, {
            ajax: true,
            action: 'duplicateQuote',
            id_quote: id_quote,
            token: token
        }).done(function(response) {
            if (response.success) {
                window.location.href = currentIndex + '&id_quote=' + response.new_id + '&viewquote&token=' + token;
            }
        });
    }
}

function convertToOrder(id_quote) {
    if (confirm('Convertir ce devis en commande ?')) {
        // ✅ UTILISE TA MÉTHODE convertToOrder() DE LA CLASSE Quote
        $.post(currentIndex, {
            ajax: true,
            action: 'convertToOrder', 
            id_quote: id_quote,
            token: token
        }).done(function(response) {
            if (response.success) {
                alert('Commande créée avec succès !');
                window.location.reload();
            }
        });
    }
}
