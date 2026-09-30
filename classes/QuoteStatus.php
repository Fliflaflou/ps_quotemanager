<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class QuoteStatus extends ObjectModel
{
    /** @var int Quote status ID */
    public $id_quote_status;
    
    /** @var string Name */
    public $name;
    
    /** @var string Color for UI display */
    public $color = '#ffffff';
    
    /** @var int Position for ordering */
    public $position = 0;
    
    /** @var bool Active status */
    public $active = true;
    
    /** @var bool Send email when status changes */
    public $send_email = false;

    /** @var bool Lock product stock for quotes using this status */
    public $stock_lock = false;
    
    /** @var string Email template to use */
    public $email_template;
    
    /** @var bool Deleted flag */
    public $deleted = false;
    
    /** @var string Creation date */
    public $date_add;
    
    /** @var string Update date */
    public $date_upd;

    /**
     * Model definition
     */
    public static $definition = [
        'table' => 'quote_status',
        'primary' => 'id_quote_status',
        'multilang' => false,
        'fields' => [
            'name' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'required' => true,
                'size' => 255
            ],
            'color' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isColor',
                'required' => true,
                'size' => 32
            ],
            'position' => [
                'type' => self::TYPE_INT,
                'validate' => 'isUnsignedInt'
            ],
            'active' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ],
            'send_email' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ],
            'stock_lock' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ],
            'email_template' => [
                'type' => self::TYPE_STRING,
                'validate' => 'isGenericName',
                'size' => 255
            ],
            'deleted' => [
                'type' => self::TYPE_BOOL,
                'validate' => 'isBool'
            ],
            'date_add' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ],
            'date_upd' => [
                'type' => self::TYPE_DATE,
                'validate' => 'isDate'
            ],
        ],
    ];

    /**
     * Constructor
     */
    public function __construct($id = null, $id_lang = null, $id_shop = null)
    {
        parent::__construct($id, $id_lang, $id_shop);
    }

    /**
     * Get all quote statuses
     */
    public static function getQuoteStatuses($active_only = true)
    {
        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'quote_status` 
                WHERE ' . ($active_only ? '`active` = 1 AND ' : '') . '`deleted` = 0
                ORDER BY `position` ASC, `name` ASC';
        
        return Db::getInstance()->executeS($sql);
    }
    
    /**
     * Get status by ID
     */
    public static function getStatusById($id_quote_status)
    {
        if (!$id_quote_status) {
            return false;
        }

        $sql = 'SELECT * FROM `' . _DB_PREFIX_ . 'quote_status` 
                WHERE `id_quote_status` = ' . (int)$id_quote_status . '
                AND `deleted` = 0';
        
        return Db::getInstance()->getRow($sql);
    }

    public static function getConvertedStatusId()
    {
        return (int) Db::getInstance()->getValue(
            'SELECT `id_quote_status` FROM `' . _DB_PREFIX_ . 'quote_status`'
            . ' WHERE `name` = "Transformé en commande" AND `deleted` = 0'
        );
    }

    /**
     * Install default statuses
     */
    public static function installDefaultStatuses()
    {
        $default_statuses = [
            [
                'name' => 'Brouillon',
                'color' => '#3498db',
                'position' => 1,
                'send_email' => 0,
                'stock_lock' => 0,
                'email_template' => null
            ],
            [
                'name' => 'En attente',
                'color' => '#f39c12',
                'position' => 2,
                'send_email' => 1,
                'stock_lock' => 1,
                'email_template' => 'quote_pending'
            ],
            [
                'name' => 'Validé',
                'color' => '#27ae60',
                'position' => 3,
                'send_email' => 1,
                'stock_lock' => 1,
                'email_template' => 'quote_validated'
            ],
            [
                'name' => 'Transformé en commande',
                'color' => '#2ecc71',
                'position' => 4,
                'send_email' => 1,
                'stock_lock' => 1,
                'email_template' => 'quote_converted'
            ],
            [
                'name' => 'Refusé',
                'color' => '#e74c3c',
                'position' => 5,
                'send_email' => 1,
                'stock_lock' => 0,
                'email_template' => 'quote_rejected'
            ],
            [
                'name' => 'Expiré',
                'color' => '#95a5a6',
                'position' => 6,
                'send_email' => 0,
                'stock_lock' => 0,
                'email_template' => null
            ]
        ];

        foreach ($default_statuses as $status_data) {
            // Vérifier si le statut existe déjà
            $existing = Db::getInstance()->getValue(
                'SELECT id_quote_status FROM `' . _DB_PREFIX_ . 'quote_status` 
                WHERE `name` = "' . pSQL($status_data['name']) . '"'
            );

            if (!$existing) {
                $result = Db::getInstance()->insert('quote_status', [
                    'name' => pSQL($status_data['name']),
                    'color' => pSQL($status_data['color']),
                    'position' => (int)$status_data['position'],
                    'send_email' => (int)$status_data['send_email'],
                    'stock_lock' => (int)$status_data['stock_lock'],
                    'email_template' => $status_data['email_template'] ? pSQL($status_data['email_template']) : null,
                    'active' => 1,
                    'deleted' => 0,
                    'date_add' => date('Y-m-d H:i:s'),
                    'date_upd' => date('Y-m-d H:i:s')
                ]);
                
                if (!$result) {
                    PrestaShopLogger::addLog(
                        'QuoteStatus: Failed to insert status ' . $status_data['name'], 
                        3, 
                        null, 
                        'QuoteStatus'
                    );
                    return false;
                }
            }
        }
        
        return true;
    }


    /**
     * Check if status is being used by quotes
     */
    public function isUsedByQuotes()
    {
        $sql = 'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'quote` 
                WHERE `id_quote_status` = ' . (int)$this->id;
        
        return (int)Db::getInstance()->getValue($sql) > 0;
    }

    /**
     * Keep the stock policy immutable after creation.
     */
    public function update($null_values = false)
    {
        if ((int)$this->id) {
            $stored_stock_lock = Db::getInstance()->getValue(
                'SELECT `stock_lock` FROM `' . _DB_PREFIX_ . 'quote_status` WHERE `id_quote_status` = ' . (int)$this->id
            );

            if ($stored_stock_lock !== false) {
                $this->stock_lock = (bool)$stored_stock_lock;
            }
        }

        return parent::update($null_values);
    }

    /**
     * Soft delete instead of hard delete
     */
    public function delete()
    {
        // Check if status is being used
        if ($this->isUsedByQuotes()) {
            return false;
        }

        // Soft delete
        $this->deleted = true;
        $this->active = false;
        
        return $this->update();
    }
}
