<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_0_2_3($module)
{
    if (!Configuration::hasKey('PS_QUOTEMANAGER_PAYMENT_INFO')) {
        Configuration::updateValue(
            'PS_QUOTEMANAGER_PAYMENT_INFO',
            "Paiement par chèque à l'ordre du GAEC du Merlanson\n"
            . "Paiement par virement IBAN : FR76 1680 7005 8737 2711 6521 832 – Id. banque : CCBPFRPPGRE – banque populaire AURA Agri Drôme Ardèche\n"
            . "Gaec Agréé du Merlanson - Siret 9188872771 00012"
        );
    }

    // Backfill missing delivery/invoice addresses for quotes created before automatic assignment existed.
    $quoteTable = _DB_PREFIX_ . 'quote';
    $quotesWithoutAddress = Db::getInstance()->executeS(
        'SELECT `id_quote`, `id_customer` FROM `' . bqSQL($quoteTable) . '`
         WHERE (`id_address_delivery` IS NULL OR `id_address_delivery` = 0)
         AND `id_customer` > 0'
    );

    foreach ((array) $quotesWithoutAddress as $row) {
        $idAddress = (int) Address::getFirstCustomerAddressId((int) $row['id_customer']);
        if ($idAddress > 0) {
            Db::getInstance()->update(
                'quote',
                ['id_address_delivery' => $idAddress, 'id_address_invoice' => $idAddress],
                'id_quote = ' . (int) $row['id_quote']
            );
        }
    }

    return true;
}
