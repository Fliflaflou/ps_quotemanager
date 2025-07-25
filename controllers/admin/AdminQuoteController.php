<?php
// controllers/admin/AdminQuoteController.php - VERSION FINALE

class AdminQuoteController extends ModuleAdminController
{
    public function __construct()
    {
        $this->table = 'quote';
        $this->className = 'Quote';
        $this->lang = false;
        $this->bootstrap = true;
        $this->list_id = 'quote';
        
        parent::__construct();
        
        // Configuration des colonnes avec bonnes jointures
        $this->fields_list = [
            'id_quote' => [
                'title' => 'ID',
                'align' => 'center',
                'class' => 'fixed-width-xs'
            ],
            'reference' => [
                'title' => 'Référence',
                'width' => 120,
                'search' => true,
                'orderby' => true
            ],
            'customer_email' => [
                'title' => 'Email Client',
                'width' => 200,
                'search' => true,
                'filter_key' => 'c!email'
            ],
            'customer_name' => [
                'title' => 'Nom Client',
                'width' => 150,
                'search' => true,
                'filter_key' => 'customer_name'
            ],
            'status_name' => [
                'title' => 'Statut',
                'align' => 'center',
                'callback' => 'displayStatus',
                'filter_key' => 'qsl!name',
                'orderby' => false
            ],
            'total_paid' => [
                'title' => 'Total TTC',
                'align' => 'right',
                'type' => 'price',
                'currency' => true,
                'orderby' => true
            ],
            'valid' => [
                'title' => 'Valide',
                'align' => 'center',
                'type' => 'bool',
                'active' => 'status'
            ],
            'valid_until' => [
                'title' => 'Expire le',
                'align' => 'center',
                'type' => 'date',
                'callback' => 'displayExpiryDate',
                'orderby' => true
            ],
            'date_add' => [
                'title' => 'Créé le',
                'align' => 'center',
                'type' => 'datetime',
                'orderby' => true
            ]
        ];
        
        // JOINTURES
        $this->_select = '
            CONCAT(c.firstname, " ", c.lastname) as customer_name,
            c.email as customer_email,
            qsl.name as status_name,
            qs.color as status_color';
            
        $this->_join = '
            LEFT JOIN `' . _DB_PREFIX_ . 'customer` c ON (c.id_customer = a.id_customer)
            LEFT JOIN `' . _DB_PREFIX_ . 'quote_status` qs ON (qs.id_quote_status = a.id_quote_status)
            LEFT JOIN `' . _DB_PREFIX_ . 'quote_status_lang` qsl ON (qsl.id_quote_status = qs.id_quote_status AND qsl.id_lang = ' . (int)$this->context->language->id . ')';
            
        // Filtrer les devis supprimés
        $this->_where = 'AND qs.deleted = 0';
        
        // Ordre par défaut
        $this->_defaultOrderBy = 'date_add';
        $this->_defaultOrderWay = 'DESC';
        
        // Actions
        $this->actions = ['view', 'edit', 'delete'];
        
        // Actions en masse
        $this->bulk_actions = [
            'delete' => [
                'text' => 'Supprimer sélection',
                'icon' => 'icon-trash',
                'confirm' => 'Supprimer les devis sélectionnés ?'
            ]
        ];
    }
    
    /**
     * Callback pour afficher le statut avec couleur
     */
    public function displayStatus($value, $row)
    {
        if (empty($value)) {
            return '<span class="label label-default">Non défini</span>';
        }
        
        $color = isset($row['status_color']) ? $row['status_color'] : '#cccccc';
        
        // Calculer la couleur du texte selon la luminosité
        $textColor = $this->getContrastColor($color);
        
        return '<span class="label" style="background-color: ' . $color . '; color: ' . $textColor . ';">' 
               . $value . '</span>';
    }
    
    /**
     * Callback pour afficher la date d'expiration
     */
    public function displayExpiryDate($value, $row)
    {
        if (empty($value) || $value == '0000-00-00' || $value == '0000-00-00 00:00:00') {
            return '<span class="text-muted">Jamais</span>';
        }
        
        $expiry = new DateTime($value);
        $now = new DateTime();
        $diff = $now->diff($expiry);
        
        if ($expiry < $now) {
            return '<span class="text-danger blink"><i class="icon-warning-sign"></i> ' 
                   . Tools::displayDate($value, null, true) . ' (Expiré)</span>';
        } elseif ($diff->days <= 7) {
            return '<span class="text-warning"><i class="icon-time"></i> ' 
                   . Tools::displayDate($value, null, true) . ' (' . $diff->days . 'j)</span>';
        }
        
        return '<span class="text-success">' . Tools::displayDate($value, null, true) . '</span>';
    }
    
    /**
     * Calculer la couleur de contraste pour le texte
     */
    private function getContrastColor($hexcolor)
    {
        $hexcolor = str_replace('#', '', $hexcolor);
        $r = hexdec(substr($hexcolor, 0, 2));
        $g = hexdec(substr($hexcolor, 2, 2));
        $b = hexdec(substr($hexcolor, 4, 2));
        
        $luminance = (($r * 0.299) + ($g * 0.587) + ($b * 0.114)) / 255;
        
        return $luminance > 0.5 ? '#000000' : '#ffffff';
    }
    
    /**
     * Toolbar avec statistiques
     */
    public function initToolbar()
    {
        parent::initToolbar();
        
        if (empty($this->display)) {
            // Stats rapides
            $stats = $this->getQuoteStats();
            
            $this->toolbar_btn['new'] = [
                'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
                'desc' => 'Nouveau devis',
                'icon' => 'process-icon-new'
            ];
            
            $this->toolbar_btn['debug'] = [
                'href' => self::$currentIndex . '&debug=1&token=' . $this->token,
                'desc' => '🔍 DEBUG',
                'icon' => 'process-icon-refresh'
            ];
            
            // Afficher les stats dans le titre
            $this->toolbar_title .= ' <small class="text-muted">(' . $stats['total'] . ' devis)</small>';
            
            // Ajouter les stats dans le contexte pour le template
            $this->context->smarty->assign('quote_stats', $stats);
        }
    }
    
    /**
     * Récupérer les statistiques
     */
    private function getQuoteStats()
    {
        $sql = 'SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN valid = 1 THEN 1 ELSE 0 END) as valid_count,
                    SUM(CASE WHEN valid_until < NOW() THEN 1 ELSE 0 END) as expired_count,
                    ROUND(AVG(total_paid), 2) as avg_amount,
                    SUM(total_paid) as total_amount
                FROM ' . _DB_PREFIX_ . 'quote q
                LEFT JOIN ' . _DB_PREFIX_ . 'quote_status qs ON qs.id_quote_status = q.id_quote_status
                WHERE qs.deleted = 0';
        
        $stats = Db::getInstance()->getRow($sql);
        
        return [
            'total' => (int)$stats['total'],
            'valid' => (int)$stats['valid_count'],
            'expired' => (int)$stats['expired_count'],
            'avg_amount' => (float)$stats['avg_amount'],
            'total_amount' => (float)$stats['total_amount']
        ];
    }
    
    public function ajaxProcessDuplicateQuote()
    {
        $id_quote = Tools::getValue('id_quote');
        $quote = new Quote($id_quote);
        
        // ✅ UTILISE TA MÉTHODE EXISTANTE
        $new_quote = $quote->duplicate();
        
        die(Tools::jsonEncode([
            'success' => $new_quote ? true : false,
            'new_id' => $new_quote ? $new_quote->id : null
        ]));
    }

    public function ajaxProcessConvertToOrder()
    {
        $id_quote = Tools::getValue('id_quote');
        $quote = new Quote($id_quote);
        
        // ✅ UTILISE TA MÉTHODE EXISTANTE
        $order = $quote->convertToOrder();
        
        die(Tools::jsonEncode([
            'success' => $order ? true : false,
            'order_id' => $order ? $order->id : null
        ]));
    }
    /**
     * Action de debug avancée
     */
    public function postProcess()
    {
        if (Tools::getValue('debug')) {
            echo "<div class='panel'>";
            echo "<h3>🔍 DEBUG - Jointures et données</h3>";
            
            // Test de la requête complète
            $sql = 'SELECT ' . $this->_select . ' 
                    FROM ' . _DB_PREFIX_ . $this->table . ' a ' 
                    . $this->_join . ' 
                    WHERE 1 ' . $this->_where . ' 
                    LIMIT 3';
                    
            echo "<h4>Requête SQL générée :</h4>";
            echo "<pre style='background:#f5f5f5;padding:10px;'>" . $sql . "</pre>";
            
            try {
                $results = Db::getInstance()->executeS($sql);
                echo "<h4>Résultats :</h4>";
                echo "<pre>" . print_r($results, true) . "</pre>";
            } catch (Exception $e) {
                echo "<div class='alert alert-danger'>ERREUR SQL: " . $e->getMessage() . "</div>";
            }
            
            // Vérifier les statuts disponibles
            $statuses = Db::getInstance()->executeS('
                SELECT qs.*, qsl.name 
                FROM ' . _DB_PREFIX_ . 'quote_status qs 
                LEFT JOIN ' . _DB_PREFIX_ . 'quote_status_lang qsl ON (qsl.id_quote_status = qs.id_quote_status AND qsl.id_lang = ' . (int)$this->context->language->id . ')
                WHERE qs.deleted = 0');
                
            echo "<h4>Statuts disponibles :</h4>";
            echo "<table class='table'>";
            echo "<tr><th>ID</th><th>Nom</th><th>Couleur</th><th>Position</th></tr>";
            foreach ($statuses as $status) {
                echo "<tr>";
                echo "<td>{$status['id_quote_status']}</td>";
                echo "<td>{$status['name']}</td>";
                echo "<td><span style='background:{$status['color']};padding:2px 8px;color:white;'>{$status['color']}</span></td>";
                echo "<td>{$status['position']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
            echo "</div>";
        }
        
        parent::postProcess();
    }
}
