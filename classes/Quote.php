<?php
class Quote extends ObjectModel
{
    public $id_customer;
    public $id_employee;
    public $id_shop;
    public $id_lang;
    public $id_currency;
    public $reference;
    public $date_add;
    public $date_upd;
    public $date_exp;
    public $total_products;
    public $total_tax_incl;
    public $total_tax_excl;
    public $id_quote_status;
    public $note;

    public static $definition = array(
        'table' => 'quote',
        'primary' => 'id_quote',
        'multilang' => true,
        'fields' => array(
            'id_customer' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_employee' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId'),
            'id_shop' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_lang' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'id_currency' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'reference' => array('type' => self::TYPE_STRING, 'validate' => 'isReference', 'required' => true),
            'date_add' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'date_upd' => array('type' => self::TYPE_DATE, 'validate' => 'isDate', 'required' => true),
            'date_exp' => array('type' => self::TYPE_DATE, 'validate' => 'isDate'),
            'total_products' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_tax_incl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'total_tax_excl' => array('type' => self::TYPE_FLOAT, 'validate' => 'isPrice', 'required' => true),
            'id_quote_status' => array('type' => self::TYPE_INT, 'validate' => 'isUnsignedId', 'required' => true),
            'note' => array('type' => self::TYPE_STRING, 'validate' => 'isCleanHtml'),
        ),
    );

    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }
}
