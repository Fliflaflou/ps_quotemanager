<?php
/**
 * Admin controller pour la configuration du module devis
 */

class AdminQuoteSettingsController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        parent::__construct();

        if (!$this->module && class_exists('Module')) {
            $this->module = Module::getInstanceByName('myquotemanager');
        }

        $this->meta_title = $this->l('Configuration des devis');
    }

    public function postProcess()
    {
        if (!Tools::isSubmit('submitConfiguration')) {
            return;
        }

        $referencePrefix = trim((string) Tools::getValue('PS_QUOTEMANAGER_REFERENCE_PREFIX', Tools::getValue('QUOTE_REFERENCE_PREFIX')));
        $referenceDateOrder = (string) Tools::getValue('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER', 'Ymd');
        if (!in_array($referenceDateOrder, ['Ymd', 'Ydm', 'mYd', 'mdY', 'dYm', 'dmY'], true)) {
            $referenceDateOrder = 'Ymd';
        }
        $defaultValidity = max(1, (int) Tools::getValue('PS_QUOTEMANAGER_DEFAULT_VALIDITY', Tools::getValue('QUOTE_DEFAULT_VALIDITY')));
        $emailNotifications = (int) Tools::getValue('PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS', Tools::getValue('QUOTE_EMAIL_NOTIFICATIONS'));
        $expirationNotificationEnabled = (int) Tools::getValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED', 0);
        $expirationNotificationDays = max(0, (int) Tools::getValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS', 3));
        $paymentInfo = (string) Tools::getValue('PS_QUOTEMANAGER_PAYMENT_INFO', '');
        $pdfPriceDisplay = (string) Tools::getValue('PS_QUOTEMANAGER_PDF_PRICE_DISPLAY', 'tax_incl');
        if (!array_key_exists($pdfPriceDisplay, $this->getPdfPriceDisplayOptions())) {
            $pdfPriceDisplay = 'tax_incl';
        }

        Configuration::updateValue('PS_QUOTEMANAGER_REFERENCE_PREFIX', $referencePrefix);
        Configuration::updateValue('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER', $referenceDateOrder);
        Configuration::updateValue('PS_QUOTEMANAGER_DEFAULT_VALIDITY', $defaultValidity);
        Configuration::updateValue('PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS', $emailNotifications);
        Configuration::updateValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED', $expirationNotificationEnabled);
        Configuration::updateValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS', $expirationNotificationDays);
        Configuration::updateValue('PS_QUOTEMANAGER_PAYMENT_INFO', $paymentInfo);
        Configuration::updateValue('PS_QUOTEMANAGER_PDF_PRICE_DISPLAY', $pdfPriceDisplay);

        // Compatibilité avec les installations anciennes
        Configuration::updateValue('QUOTE_REFERENCE_PREFIX', $referencePrefix);
        Configuration::updateValue('QUOTE_REFERENCE_DATE_ORDER', $referenceDateOrder);
        Configuration::updateValue('QUOTE_DEFAULT_VALIDITY', $defaultValidity);
        Configuration::updateValue('QUOTE_EMAIL_NOTIFICATIONS', $emailNotifications);

        $this->confirmations[] = $this->l('Paramètres sauvegardés avec succès !');
    }

    public function getContent()
    {
        $this->postProcess();

        return $this->renderConfigurationForm();
    }

    private function getConfigValue($currentKey, $legacyKey, $default = null)
    {
        if (Configuration::hasKey($currentKey)) {
            return Configuration::get($currentKey, $default);
        }

        if (Configuration::hasKey($legacyKey)) {
            return Configuration::get($legacyKey, $default);
        }

        return $default;
    }

    private function getPdfPriceDisplayOptions()
    {
        return [
            'tax_incl' => $this->l('TTC'),
            'tax_excl' => $this->l('HT'),
            'both' => $this->l('HT et TTC'),
        ];
    }

    public function initContent()
    {
        if (!$this->viewAccess()) {
            $this->errors[] = $this->trans('You do not have permission to view this.', [], 'Admin.Notifications.Error');

            return;
        }

        $this->content = $this->getContent();
        $this->context->smarty->assign([
            'content' => $this->content,
        ]);
    }

    /**
     * Formulaire de configuration
     */
    public function renderConfigurationForm()
    {
        $referencePrefix = $this->getConfigValue('PS_QUOTEMANAGER_REFERENCE_PREFIX', 'QUOTE_REFERENCE_PREFIX', 'QUO');
        $referenceDateOrder = $this->getConfigValue('PS_QUOTEMANAGER_REFERENCE_DATE_ORDER', 'QUOTE_REFERENCE_DATE_ORDER', 'Ymd');
        $defaultValidity = (int) $this->getConfigValue('PS_QUOTEMANAGER_DEFAULT_VALIDITY', 'QUOTE_DEFAULT_VALIDITY', 30);
        $emailNotifications = (int) $this->getConfigValue('PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS', 'QUOTE_EMAIL_NOTIFICATIONS', 1);
        $expirationNotificationEnabled = (int) $this->getConfigValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED', 'QUOTE_EXPIRATION_NOTIFICATION_ENABLED', 0);
        $expirationNotificationDays = max(0, (int) $this->getConfigValue('PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS', 'QUOTE_EXPIRATION_NOTIFICATION_DAYS', 3));
        $paymentInfo = (string) $this->getConfigValue('PS_QUOTEMANAGER_PAYMENT_INFO', 'QUOTE_PAYMENT_INFO', '');
        $pdfPriceDisplay = (string) Configuration::get('PS_QUOTEMANAGER_PDF_PRICE_DISPLAY', null, null, null, 'tax_incl');

        $currentIndex = $this->context->link->getAdminLink('AdminQuoteSettings');
        $token = Tools::getAdminTokenLite('AdminQuoteSettings');

        $html = '<div class="panel">';
        $html .= '<h3><i class="icon-cogs"></i> ' . $this->l('Configuration générale') . '</h3>';
        $html .= '<form action="' . $currentIndex . '" method="post" class="form-horizontal">';
        $html .= '<input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '" />';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3" for="PS_QUOTEMANAGER_REFERENCE_PREFIX">' . $this->l('Préfixe des références') . '</label>';
        $html .= '<div class="col-lg-9"><input type="text" id="PS_QUOTEMANAGER_REFERENCE_PREFIX" name="PS_QUOTEMANAGER_REFERENCE_PREFIX" value="' . htmlspecialchars((string) $referencePrefix, ENT_QUOTES, 'UTF-8') . '" class="form-control" /></div>';
        $html .= '<div class="col-lg-9 col-lg-offset-3"><p class="help-block">' . $this->l('Préfixe utilisé pour les références de devis (ex: QUO)') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3" for="PS_QUOTEMANAGER_REFERENCE_DATE_ORDER">' . $this->l('Ordre de la date dans la référence') . '</label>';
        $html .= '<div class="col-lg-4"><select id="PS_QUOTEMANAGER_REFERENCE_DATE_ORDER" name="PS_QUOTEMANAGER_REFERENCE_DATE_ORDER" class="form-control">';
        $dateOrders = [
            'Ymd' => $this->l('Année - Mois - Jour'),
            'Ydm' => $this->l('Année - Jour - Mois'),
            'mYd' => $this->l('Mois - Année - Jour'),
            'mdY' => $this->l('Mois - Jour - Année'),
            'dYm' => $this->l('Jour - Année - Mois'),
            'dmY' => $this->l('Jour - Mois - Année'),
        ];
        foreach ($dateOrders as $value => $label) {
            $html .= '<option value="' . $value . '"' . ($referenceDateOrder === $value ? ' selected="selected"' : '') . '>' . $label . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="col-lg-5"><p class="help-block">' . $this->l('Le compteur interne est toujours ajouté en dernier pour garantir une référence unique.') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3" for="PS_QUOTEMANAGER_DEFAULT_VALIDITY">' . $this->l('Durée de validité par défaut') . '</label>';
        $html .= '<div class="col-lg-3"><input type="text" id="PS_QUOTEMANAGER_DEFAULT_VALIDITY" name="PS_QUOTEMANAGER_DEFAULT_VALIDITY" value="' . (int) $defaultValidity . '" class="form-control" /></div>';
        $html .= '<div class="col-lg-6"><p class="help-block">' . $this->l('jours') . '</p></div>';
        $html .= '<div class="col-lg-9 col-lg-offset-3"><p class="help-block">' . $this->l('Nombre de jours de validité par défaut pour un nouveau devis') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Notifications email') . '</label>';
        $html .= '<div class="col-lg-9"><span class="switch prestashop-switch">';
        $html .= '<input type="radio" name="PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS" id="PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS_on" value="1" ' . (($emailNotifications ? 'checked="checked"' : '') . '>');
        $html .= '<label for="PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS_on">' . $this->l('Activé') . '</label>';
        $html .= '<input type="radio" name="PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS" id="PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS_off" value="0" ' . ((!$emailNotifications) ? 'checked="checked"' : '') . '>';
        $html .= '<label for="PS_QUOTEMANAGER_EMAIL_NOTIFICATIONS_off">' . $this->l('Désactivé') . '</label>';
        $html .= '</span><p class="help-block">' . $this->l('Envoyer des notifications email lors des changements de statut') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3">' . $this->l('Alerte avant expiration') . '</label>';
        $html .= '<div class="col-lg-9"><span class="switch prestashop-switch">';
        $html .= '<input type="radio" name="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED" id="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED_on" value="1" ' . ($expirationNotificationEnabled ? 'checked="checked"' : '') . '>';
        $html .= '<label for="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED_on">' . $this->l('Activée') . '</label>';
        $html .= '<input type="radio" name="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED" id="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED_off" value="0" ' . (!$expirationNotificationEnabled ? 'checked="checked"' : '') . '>';
        $html .= '<label for="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_ENABLED_off">' . $this->l('Désactivée') . '</label>';
        $html .= '</span><p class="help-block">' . $this->l('Notifier le client avant la date d’expiration de son devis.') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3" for="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS">' . $this->l('Délai de notification') . '</label>';
        $html .= '<div class="col-lg-3"><input type="number" min="0" id="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS" name="PS_QUOTEMANAGER_EXPIRATION_NOTIFICATION_DAYS" value="' . (int) $expirationNotificationDays . '" class="form-control" /></div>';
        $html .= '<div class="col-lg-6"><p class="help-block">' . $this->l('jours avant l’expiration. La tâche planifiée doit être exécutée quotidiennement.') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3" for="PS_QUOTEMANAGER_PAYMENT_INFO">' . $this->l('Informations de paiement (PDF)') . '</label>';
        $html .= '<div class="col-lg-9"><textarea id="PS_QUOTEMANAGER_PAYMENT_INFO" name="PS_QUOTEMANAGER_PAYMENT_INFO" rows="4" class="form-control">' . htmlspecialchars((string) $paymentInfo, ENT_QUOTES, 'UTF-8') . '</textarea></div>';
        $html .= '<div class="col-lg-9 col-lg-offset-3"><p class="help-block">' . $this->l('Affiché en bas du devis PDF : RIB, ordre de chèque, SIRET, etc. Une ligne par information.') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="form-group">';
        $html .= '<label class="control-label col-lg-3" for="PS_QUOTEMANAGER_PDF_PRICE_DISPLAY">' . $this->l('Affichage des prix (PDF)') . '</label>';
        $html .= '<div class="col-lg-4"><select id="PS_QUOTEMANAGER_PDF_PRICE_DISPLAY" name="PS_QUOTEMANAGER_PDF_PRICE_DISPLAY" class="form-control">';
        foreach ($this->getPdfPriceDisplayOptions() as $value => $label) {
            $html .= '<option value="' . $value . '"' . ($pdfPriceDisplay === $value ? ' selected="selected"' : '') . '>' . $label . '</option>';
        }
        $html .= '</select></div>';
        $html .= '<div class="col-lg-5"><p class="help-block">' . $this->l('Prix unitaires, totaux de ligne, remises et transport. Le récapitulatif Total HT / TVA / Total TTC reste toujours affiché.') . '</p></div>';
        $html .= '</div>';

        $html .= '<div class="panel-footer">';
        $html .= '<button type="submit" name="submitConfiguration" class="btn btn-default pull-right"><i class="process-icon-save"></i> ' . $this->l('Enregistrer les paramètres') . '</button>';
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';

        return $html;
    }
}
