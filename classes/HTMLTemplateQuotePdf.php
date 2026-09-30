<?php

class HTMLTemplateQuotePdf extends HTMLTemplate
{
    /** @var Quote */
    private $quote;

    public function __construct(Quote $quote, $smarty)
    {
        $this->quote = $quote;
        $this->smarty = $smarty;
        $this->shop = new Shop((int) Context::getContext()->shop->id);
        $this->title = 'Devis ' . $quote->reference;
        $this->date = $quote->date_add;
    }

    public function getContent()
    {
        $customer = new Customer((int) $this->quote->id_customer);
        $currency = new Currency((int) $this->quote->id_currency);
        $customer_address = new Address((int) ($this->quote->id_address_delivery ?: $this->quote->id_address_invoice));
        $shop = new Shop((int) Context::getContext()->shop->id);
        $shop_address = AddressFormat::generateAddress($shop->getAddress(), [], ', ', ' ');
        $shop_id = (int) $shop->id;
        $logo = $this->getShopLogo($shop_id);
        $logoPath = $logo ? Tools::getShopProtocol() . Tools::getMediaServer(_PS_IMG_) . _PS_IMG_ . $logo : null;
        $logoWidth = 0;
        $logoHeight = 0;
        if ($logo && file_exists(_PS_IMG_DIR_ . $logo)) {
            [$logoWidth, $logoHeight] = getimagesize(_PS_IMG_DIR_ . $logo);
            if ($logoHeight > 100) {
                $ratio = 100 / $logoHeight;
                $logoWidth = (int) round($logoWidth * $ratio);
                $logoHeight = 100;
            }
        }
        $carrier = (int) $this->quote->id_carrier > 0 ? new Carrier((int) $this->quote->id_carrier, $this->quote->id_lang) : null;
        $products = QuoteProduct::getByQuote((int) $this->quote->id);
        $calculatedLines = [];
        foreach ($this->quote->computeProductLines() as $line) {
            $calculatedLines[$line['id_quote_product']] = $line;
        }
        foreach ($products as &$product) {
            $line = $calculatedLines[(int) $product['id_quote_product']] ?? null;
            $product['net_tax_incl'] = $line ? $line['net_tax_incl'] : (float) $product['total_price_tax_incl'];
            $product['net_tax_excl'] = $line ? $line['net_tax_excl'] : (float) $product['total_price_tax_excl'];
        }
        unset($product);
        $priceDisplay = (string) Configuration::get('PS_QUOTEMANAGER_PDF_PRICE_DISPLAY', null, null, $shop_id, 'tax_incl');
        if (!in_array($priceDisplay, ['tax_incl', 'tax_excl', 'both'], true)) {
            $priceDisplay = 'tax_incl';
        }

        $this->smarty->assign([
            'quote' => $this->quote,
            'customer' => $customer,
            'customer_address' => Validate::isLoadedObject($customer_address) ? $customer_address : null,
            'customer_company' => Validate::isLoadedObject($customer_address) ? $customer_address->company : null,
            'customer_phone' => Validate::isLoadedObject($customer_address)
                ? ($customer_address->phone ?: $customer_address->phone_mobile)
                : null,
            'currency' => $currency,
            'products' => $products,
            'price_display' => $priceDisplay,
            'shop_name' => Configuration::get('PS_SHOP_NAME', null, null, $shop_id),
            'shop_details' => Configuration::get('PS_SHOP_DETAILS', null, null, $shop_id),
            'shop_address' => $shop_address,
            'shop_company' => $shop->getAddress()->company,
            'shop_email' => Configuration::get('PS_SHOP_EMAIL', null, null, $shop_id),
            'shop_phone' => Configuration::get('PS_SHOP_PHONE', null, null, $shop_id),
            'logo_path' => $logoPath,
            'logo_width' => $logoWidth,
            'logo_height' => $logoHeight,
            'carrier_name' => $carrier && Validate::isLoadedObject($carrier) ? $carrier->name : null,
            'total_tax' => max(0, (float) $this->quote->total_paid - (float) $this->quote->total_paid_tax_excl),
            'legal_notice' => 'Produits issus de l’Agriculture Biologique - certifiés par FR-BIO-01 - Agriculture France',
            'payment_info' => Configuration::get('PS_QUOTEMANAGER_PAYMENT_INFO', null, null, $shop_id),
        ]);

        return $this->smarty->fetch(
            _PS_MODULE_DIR_ . 'myquotemanager/views/templates/pdf/quote_pdf.tpl'
        );
    }

    private function getShopLogo($shopId)
    {
        $invoiceLogo = Configuration::get('PS_LOGO_INVOICE', null, null, (int) $shopId);
        if ($invoiceLogo && file_exists(_PS_IMG_DIR_ . $invoiceLogo)) {
            return $invoiceLogo;
        }

        $logo = Configuration::get('PS_LOGO', null, null, (int) $shopId);

        return $logo && file_exists(_PS_IMG_DIR_ . $logo) ? $logo : null;
    }

    public function getHeader()
    {
        return '';
    }

    public function getFooter()
    {
        return '';
    }

    public function getPagination()
    {
        return '';
    }

    public function getFilename()
    {
        return 'devis-' . $this->quote->reference . '.pdf';
    }

    public function getBulkFilename()
    {
        return $this->getFilename();
    }
}