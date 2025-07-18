<?php
/**
 * Admin Controller for Quote Management
 */
class AdminQuoteController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $this->table = 'quote';
        $this->className = 'Quote';
        $this->lang = false;
        $this->context = Context::getContext();
        
        // Liste des actions disponibles
        $this->actions = ['view', 'edit', 'delete'];
        
        // Configuration de la liste
        $this->fields_list = [
            'id_quote' => [
                'title' => 'ID',
                'width' => 50,
                'type' => 'text',
            ],
            'reference' => [
                'title' => 'Référence',
                'width' => 120,
                'type' => 'text',
            ],
            'id_customer' => [
                'title' => 'Client',
                'width' => 150,
                'type' => 'text',
                'callback' => 'displayCustomerName',
            ],
            'status_name' => [
                'title' => 'Statut',
                'width' => 100,
                'type' => 'text',
                'callback' => 'displayStatus',
            ],
            'total_tax_incl' => [
                'title' => 'Total TTC',
                'width' => 80,
                'type' => 'price',
                'currency' => true,
            ],
            'date_add' => [
                'title' => 'Date création',
                'width' => 120,
                'type' => 'datetime',
            ],
            'valid_until' => [
                'title' => 'Valide jusqu\'au',
                'width' => 120,
                'type' => 'date',
            ],
        ];
        
        // Titre de la page
        $this->meta_title = 'Gestion des devis';
        
        parent::__construct();
    }

    /**
     * Récupère la liste des devis avec les jointures nécessaires
     */
    public function getList($id_lang, $order_by = null, $order_way = null, $start = 0, $limit = null, $id_lang_shop = false)
    {
        // Requête personnalisée avec jointures
        $sql = new DbQuery();
        $sql->select('q.*, c.firstname, c.lastname, qsl.name as status_name, qs.color as status_color')
            ->from('quote', 'q')
            ->leftJoin('customer', 'c', 'q.id_customer = c.id_customer')
            ->leftJoin('quote_status', 'qs', 'q.id_quote_status = qs.id_quote_status')
            ->leftJoin('quote_status_lang', 'qsl', 'qs.id_quote_status = qsl.id_quote_status AND qsl.id_lang = ' . (int)$id_lang);
        
        // Ajout du nom du client pour l'affichage
        // $sql->select('CONCAT(c.firstname, " ", c.lastname) as customer_name');
        
        // Ordre par défaut
        if (!$order_by) {
            $order_by = 'date_add';
            $order_way = 'DESC';
        }
        
        $sql->orderBy($order_by . ' ' . $order_way);
        
        // Pagination
        if ($limit) {
            $sql->limit($limit, $start);
        }
        
        $result = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
        
        // Formatage des résultats
        if ($result) {
            foreach ($result as &$row) {
                // Formatage du prix
                if (isset($row['total_tax_incl'])) {
                    $row['total_tax_incl'] = Tools::displayPrice($row['total_tax_incl']);
                }
                
                // Formatage des dates
                if (isset($row['date_add'])) {
                    $row['date_add'] = Tools::displayDate($row['date_add'], true);
                }
                
                if (isset($row['valid_until'])) {
                    $row['valid_until'] = Tools::displayDate($row['valid_until']);
                }
            }
        }
        
        return $result ?: [];
    }

    /**
     * Callback pour afficher le nom du client
     */
        public function displayCustomerName($value, $row)
        {
            // $value = id_customer
            // $row contient firstname et lastname via les JOINs
            
            if (empty($row['firstname']) && empty($row['lastname'])) {
                return '<span class="text-muted">Client supprimé (ID: ' . $value . ')</span>';
            }
            
            return $row['firstname'] . ' ' . $row['lastname'];
        }

    /**
     * Callback pour afficher le statut avec couleur
     */
    public function displayStatus($value, $row)
    {
        $color = !empty($row['status_color']) ? $row['status_color'] : '#6c757d';
        $name = !empty($row['status_name']) ? $row['status_name'] : 'Inconnu';
        
        return sprintf(
            '<span class="badge" style="background-color: %s;">%s</span>',
            Tools::safeOutput($color),
            Tools::safeOutput($name)
        );
    }

    /**
     * Obtient le nombre total de devis
     */
    public function getListTotal($id_lang, $id_lang_shop = false)
    {
        $sql = new DbQuery();
        $sql->select('COUNT(q.id_quote)')
            ->from('quote', 'q');
        
        return (int)Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
    }

    /**
     * Actions personnalisées
     */
    public function postProcess()
    {
        // Validation d'un devis
        if (Tools::isSubmit('validate_quote') && $id_quote = Tools::getValue('id_quote')) {
            $this->validateQuote($id_quote);
        }
        
        // Refus d'un devis
        if (Tools::isSubmit('reject_quote') && $id_quote = Tools::getValue('id_quote')) {
            $this->rejectQuote($id_quote);
        }
        
        return parent::postProcess();
    }

    /**
     * Valider un devis
     */
    private function validateQuote($id_quote)
    {
        $quote = new Quote($id_quote);
        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = 'Devis introuvable';
            return;
        }
        
        // Changer le statut vers "Validé" (ID 3)
        $quote->id_quote_status = 3;
        $quote->date_validation = date('Y-m-d H:i:s');
        
        if ($quote->save()) {
            $this->confirmations[] = 'Devis validé avec succès';
            // TODO: Envoyer email de confirmation au client
        } else {
            $this->errors[] = 'Erreur lors de la validation du devis';
        }
    }

    /**
     * Refuser un devis
     */
    private function rejectQuote($id_quote)
    {
        $quote = new Quote($id_quote);
        if (!Validate::isLoadedObject($quote)) {
            $this->errors[] = 'Devis introuvable';
            return;
        }
        
        // Changer le statut vers "Refusé" (ID 5)
        $quote->id_quote_status = 5;
        $quote->date_validation = date('Y-m-d H:i:s');
        
        if ($quote->save()) {
            $this->confirmations[] = 'Devis refusé';
            // TODO: Envoyer email de refus au client
        } else {
            $this->errors[] = 'Erreur lors du refus du devis';
        }
    }

    /**
     * Ajouter des boutons d'actions dans la toolbar
     */
    public function initToolbar()
    {
        parent::initToolbar();
        
        // Bouton nouveau devis
        $this->toolbar_btn['new'] = [
            'href' => self::$currentIndex . '&add' . $this->table . '&token=' . $this->token,
            'desc' => 'Nouveau devis',
            'icon' => 'plus-sign'
        ];
        
        // Bouton export
        $this->toolbar_btn['export'] = [
            'href' => self::$currentIndex . '&export' . $this->table . '&token=' . $this->token,
            'desc' => 'Export CSV',
            'icon' => 'download'
        ];
    }
}
