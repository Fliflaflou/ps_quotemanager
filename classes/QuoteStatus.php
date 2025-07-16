<?php
class QuoteStatus extends ObjectModel {
        public $id_quote_status;
        /** @var bool Active status */
        public $active = true;

        /** @var int Position for ordering */
        public $position = 0;

        /** @var string Color for UI display */
        public $color = '#ffffff';

        /** @var bool Deleted flag */
        public $deleted = false;

        /** @var string Name (multilang) */
        public $name;

        /** @var string Creation date */
        public $date_add;

        /** @var string Update date */
        public $date_upd;

        /**
         * @see ObjectModel::$definition
         */
        public static $definition = [
            'table' => 'quote_status',
            'primary' => 'id_quote_status',
            'multilang' => true,
            'multilang_shop' => false,
            'fields' => [
                // Fields without lang
                'active' => [
                    'type' => self::TYPE_BOOL,
                    'validate' => 'isBool',
                    'copy_post' => false
                ],
                'position' => [
                    'type' => self::TYPE_INT,
                    'validate' => 'isUnsignedInt',
                    'required' => false
                ],
                'color' => [
                    'type' => self::TYPE_STRING,
                    'validate' => 'isColor',
                    'size' => 7,
                    'required' => false
                ],
                'deleted' => [
                    'type' => self::TYPE_BOOL,
                    'validate' => 'isBool',
                    'copy_post' => false
                ],
                'date_add' => [
                    'type' => self::TYPE_DATE,
                    'validate' => 'isDate',
                    'copy_post' => false
                ],
                'date_upd' => [
                    'type' => self::TYPE_DATE,
                    'validate' => 'isDate',
                    'copy_post' => false
                ],
                
                // Multilang fields
                'name' => [
                    'type' => self::TYPE_STRING,
                    'lang' => true,
                    'validate' => 'isGenericName',
                    'required' => true,
                    'size' => 64
                ]
            ]
        ];

        /**
         * Get all quote statuses
         * 
         * @param int $id_lang Language ID
         * @param bool $active_only Get only active statuses
         * @return array
         */
        public static function getQuoteStatuses($id_lang = null, $active_only = true)
        {
            if (!$id_lang) {
                $id_lang = Context::getContext()->language->id;
            }
            
            $sql = new DbQuery();
            $sql->select('qs.*, qsl.name')
                ->from('quote_status', 'qs')
                ->leftJoin('quote_status_lang', 'qsl', 'qs.id_quote_status = qsl.id_quote_status AND qsl.id_lang = ' . (int)$id_lang)
                ->where('qs.deleted = 0');
                
            if ($active_only) {
                $sql->where('qs.active = 1');
            }
            
            $sql->orderBy('qs.position ASC, qs.id_quote_status ASC');
            
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        }

        /**
         * Get quote status by ID
         * 
         * @param int $id_quote_status
         * @param int $id_lang Language ID
         * @return array|false
         */
        public static function getQuoteStatusById($id_quote_status, $id_lang = null)
        {
            if (!$id_lang) {
                $id_lang = Context::getContext()->language->id;
            }
            
            $sql = new DbQuery();
            $sql->select('qs.*, qsl.name')
                ->from('quote_status', 'qs')
                ->leftJoin('quote_status_lang', 'qsl', 'qs.id_quote_status = qsl.id_quote_status AND qsl.id_lang = ' . (int)$id_lang)
                ->where('qs.id_quote_status = ' . (int)$id_quote_status)
                ->where('qs.deleted = 0');
                
            return Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow($sql);
        }

        /**
         * Install default quote statuses
         * 
         * @return bool
         */
        public static function installDefaultStatuses()
        {
            $statuses = [
                [
                    'position' => 1,
                    'color' => '#3498db',
                    'names' => [
                        'fr' => 'Brouillon',
                        'en' => 'Draft'
                    ]
                ],
                [
                    'position' => 2,
                    'color' => '#f39c12',
                    'names' => [
                        'fr' => 'En attente',
                        'en' => 'Pending'
                    ]
                ],
                [
                    'position' => 3,
                    'color' => '#27ae60',
                    'names' => [
                        'fr' => 'Validé',
                        'en' => 'Validated'
                    ]
                ],
                [
                    'position' => 4,
                    'color' => '#2ecc71',
                    'names' => [
                        'fr' => 'Transformé en commande',
                        'en' => 'Converted to order'
                    ]
                ],
                [
                    'position' => 5,
                    'color' => '#e74c3c',
                    'names' => [
                        'fr' => 'Refusé',
                        'en' => 'Rejected'
                    ]
                ],
                [
                    'position' => 6,
                    'color' => '#95a5a6',
                    'names' => [
                        'fr' => 'Expiré',
                        'en' => 'Expired'
                    ]
                ]
            ];

            $languages = Language::getLanguages(false);
            
            foreach ($statuses as $status_data) {
                $status = new QuoteStatus();
                $status->position = $status_data['position'];
                $status->color = $status_data['color'];
                $status->active = true;
                
                // Set names for each language
                foreach ($languages as $language) {
                    $lang_iso = $language['iso_code'];
                    if (isset($status_data['names'][$lang_iso])) {
                        $status->name[$language['id_lang']] = $status_data['names'][$lang_iso];
                    } else {
                        // Fallback to English
                        $status->name[$language['id_lang']] = $status_data['names']['en'];
                    }
                }
                
                if (!$status->save()) {
                    return false;
                }
            }
            
            return true;
        }

        /**
         * Get next position for new status
         * 
         * @return int
         */
        public static function getNextPosition()
        {
            $sql = 'SELECT MAX(position) + 1 as next_position FROM ' . _DB_PREFIX_ . 'quote_status WHERE deleted = 0';
            $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            
            return $result ? (int)$result : 1;
        }

        /**
         * Soft delete (set deleted flag instead of real deletion)
         * 
         * @return bool
         */
        public function delete()
        {
            $this->deleted = true;
            return $this->update();
        }

        /**
         * Check if status can be deleted (not used in quotes)
         * 
         * @return bool
         */
        public function isDeletable()
        {
            $sql = 'SELECT COUNT(*) FROM ' . _DB_PREFIX_ . 'quote WHERE id_quote_status = ' . (int)$this->id_quote_status;
            $count = Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
            
            return (int)$count === 0;
        }
    }
?>