<?php

require_once dirname(__FILE__) . '/../../config/config.inc.php';
require_once dirname(__FILE__) . '/../../init.php';
require_once dirname(__FILE__) . '/classes/Quote.php';
require_once dirname(__FILE__) . '/classes/QuoteExpirationNotifier.php';

$expectedToken = (string) Configuration::get('PS_QUOTEMANAGER_EXPIRATION_CRON_TOKEN');
$providedToken = (string) Tools::getValue('token');
if (PHP_SAPI === 'cli' && isset($argv)) {
    foreach ($argv as $argument) {
        if (strpos($argument, '--token=') === 0) {
            $providedToken = substr($argument, 8);
            break;
        }
    }
}
if ($expectedToken === '' || !hash_equals($expectedToken, $providedToken)) {
    http_response_code(403);
    exit('Forbidden');
}

$module = Module::getInstanceByName('myquotemanager');
$notifier = new QuoteExpirationNotifier($module);
echo (int) $notifier->notifyUpcomingExpirations();