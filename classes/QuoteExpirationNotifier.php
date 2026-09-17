<?php

class QuoteExpirationNotifier
{
    private $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function notifyUpcomingExpirations()
    {
        if (!(int) Configuration::get('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED')) {
            return 0;
        }

        $days = max(0, (int) Configuration::get('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS', 3));
        $today = date('Y-m-d');
        $limit = date('Y-m-d', strtotime('+' . $days . ' days'));
        $quotes = Db::getInstance()->executeS(
            'SELECT `id_quote` FROM `' . _DB_PREFIX_ . 'quote`
             WHERE `id_order` = 0
             AND `date_exp` >= "' . pSQL($today) . '"
             AND `date_exp` <= "' . pSQL($limit) . '"
             AND `date_exp_notification_sent` IS NULL'
        );
        $sentCount = 0;

        foreach ((array) $quotes as $quoteData) {
            $quote = new Quote((int) $quoteData['id_quote']);
            if ($this->sendNotification($quote)) {
                $sentCount++;
            }
        }

        return $sentCount;
    }

    private function sendNotification(Quote $quote)
    {
        $customer = new Customer((int) $quote->id_customer);
        if (!Validate::isLoadedObject($customer) || !Validate::isEmail($customer->email)) {
            PrestaShopLogger::addLog('QuoteExpirationNotifier: client sans e-mail valide pour ' . $quote->reference, 2);
            return false;
        }

        $idLang = (int) ($quote->id_lang ?: Context::getContext()->language->id);
        $sent = Mail::Send(
            $idLang,
            'quote_expiration',
            sprintf('Votre devis %s arrive à expiration', $quote->reference),
            [
                '{firstname}' => $customer->firstname,
                '{lastname}' => $customer->lastname,
                '{quote_reference}' => $quote->reference,
                '{date_exp}' => $quote->date_exp,
                '{shop_name}' => Configuration::get('PS_SHOP_NAME'),
            ],
            $customer->email,
            trim($customer->firstname . ' ' . $customer->lastname),
            null,
            null,
            null,
            null,
            _PS_MODULE_DIR_ . 'myquotemanager/mails/'
        );

        if (!$sent) {
            PrestaShopLogger::addLog('QuoteExpirationNotifier: échec pour ' . $quote->reference, 2);
            return false;
        }

        Db::getInstance()->update('quote', [
            'date_exp_notification_sent' => date('Y-m-d H:i:s'),
        ], 'id_quote = ' . (int) $quote->id);

        return true;
    }
}