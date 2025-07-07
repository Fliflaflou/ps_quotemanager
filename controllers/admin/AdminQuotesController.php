<?php
class AdminQuotesController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'quote';
        $this->className = 'Quote';
        $this->identifier = 'id_quote';
        $this->lang = false;

        parent::__construct();

        $this->fields_list = array(
            'id_quote' => array(
                'title' => $this->l('ID'),
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ),
            'reference' => array(
                'title' => $this->l('Reference')
            ),
            'id_customer' => array(
                'title' => $this->l('Customer')
            ),
            'total_tax_incl' => array(
                'title' => $this->l('Total (tax incl.)'),
                'type' => 'price',
                'currency' => true,
                'callback' => 'setOrderCurrency'
            ),
            'date_add' => array(
                'title' => $this->l('Date')
            ),
            'id_quote_status' => array(
                'title' => $this->l('Status')
            )
        );
    }

    public function setOrderCurrency($echo, $tr)
    {
        $currency = new Currency($tr['id_currency']);
        return $currency->sign;
    }
}
