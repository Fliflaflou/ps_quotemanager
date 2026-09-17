# Fonctionnalité: Réductions sur Devis (v0.2.4)

## Vue d'ensemble

La fonctionnalité de réductions permet aux administrateurs PrestaShop d'appliquer des remises sur chaque ligne de produit d'un devis, avec support pour les réductions en pourcentage (%) et/ou montants fixes (€).

## Caractéristiques

### Réductions au niveau produit
- **Réduction en pourcentage** : 0-100% du prix unitaire
- **Réduction montant fixe** : montant€ déduit du total de la ligne
- **Combinaison** : les deux peuvent être appliquées ensemble
- **Plafond** : la réduction totale ne peut pas dépasser 100% du prix de la ligne
- **Temps réel** : les totaux se mettent à jour immédiatement via AJAX

### Réductions au niveau devis
- **Base pour futures réductions** : champs `total_discount` et `total_discount_wt` prêts pour réductions globales
- **Accumulatif** : total des réductions de tous les produits

## Architecture technique

### Base de données
```sql
-- Colonnes existantes dans ps_quote_product
reduction_percent DECIMAL(20,6)  -- Pourcentage de réduction (0-100)
reduction_amount   DECIMAL(20,6)  -- Montant fixe de réduction

-- Colonnes existantes dans ps_quote
total_discount     DECIMAL(20,6)  -- Total réductions HT
total_discount_wt  DECIMAL(20,6)  -- Total réductions TTC
```

### Classes PHP

#### QuoteProduct
```php
public $reduction_percent;  // Pourcentage de réduction
public $reduction_amount;   // Montant fixe de réduction

// Mettre à jour la réduction d'un produit
QuoteProduct::updateReduction($id_quote_product, $reduction_percent, $reduction_amount);
```

#### Quote
```php
public $total_discount;     // Montant total réductions HT
public $total_discount_wt;  // Montant total réductions TTC

// Applique les réductions au calcul des totaux
public function calculateTotals();

// Réduction globale (pour usage futur)
public function applyGlobalDiscount($discount_percent, $discount_amount);
```

#### AdminQuoteController
```php
// Handler AJAX pour mettre à jour les réductions
private function processUpdateProductReductionAjax();

// Route AJAX disponible: 'updateProductReductionInline'
```

### Formule de calcul

Pour chaque ligne produit :
```
reduction_tax_excl = reduction_amount + (line_total * reduction_percent / 100)
reduction_tax_incl = reduction_tax_excl * (1 + tax_rate / 100)

line_total_after = line_total - reduction_tax_excl
```

Le total du devis est recalculé automatiquement :
```
total_products = SUM(line_total - reduction)
total_discount = SUM(reduction)
total_paid = total_products + total_shipping
```

## Interface utilisateur

### Vue d'administration du devis

Tableau des produits avec colonnes supplémentaires :
```
Produit | Déclinaison | Quantité | P.U. HT | P.U. TTC | Total HT | Total TTC 
| Réduction (% et €) | Total après réduction HT | Total après réduction TTC | Actions
```

Chaque ligne produit contient :
- Champ % : réduction en pourcentage (0-100)
- Champ € : réduction montant fixe
- Bouton ✓ : valider les réductions (peut aussi presser Entrée)

Pied de tableau :
- Ligne "Total des produits" : avant réductions
- Ligne "Réductions produits" : total des réductions appliquées (affichée en jaune si > 0)
- Ligne "Total du devis" : après réductions et transport

### Interaction AJAX

Lorsque l'utilisateur clique sur ✓ ou appuie sur Entrée :
1. Validation client : % entre 0-100, montant € positif
2. Envoi AJAX POST avec action='updateProductReductionInline'
3. Serveur valide : réduction totale ≤ 100% de la ligne
4. Réponse JSON avec nouveaux totaux
5. Mise à jour UI : affichage des totaux après réduction, totaux du devis

## Exemples d'utilisation

### Exemple 1: Réduction 20% sur un produit
- Produit: 100€ HT x 2 = 200€
- Réduction: 20%
- Résultat: 200€ - 40€ = 160€ HT

### Exemple 2: Réduction montant fixe + pourcentage
- Produit: 100€ HT x 1 = 100€
- Réduction: 10€ + 10%
- Calcul: 100€ - (10€ + 100€*10%) = 100€ - 20€ = 80€ HT

### Exemple 3: Produit gratuit (100% réduction)
- Produit: 100€ HT x 1 = 100€
- Réduction: 100%
- Résultat: 0€

## Limitations actuelles

### Conversion en commande
- Les réductions appliquées au devis **ne sont pas** automatiquement converties en remises PrestaShop
- Le prix utilisé pour créer la commande est le prix du produit standard (sans réduction)
- **Workaround**: Les réductions peuvent être notées dans les commentaires du devis ou appliquées manuellement à la commande
- **À faire futur**: Créer des CartRule automatiques lors de la conversion

### Affichage FO/PDF
- Les réductions ne sont pas visibles en FO actuellement
- Le PDF doit être mis à jour pour afficher les réductions

## Mise en production (v0.2.4)

Fichiers modifiés :
- `classes/QuoteProduct.php` : propriétés + méthode updateReduction()
- `classes/Quote.php` : calcul des réductions dans calculateTotals()
- `controllers/admin/AdminQuoteController.php` : handler AJAX
- `views/templates/admin/quote_view.tpl` : UI réductions + JavaScript
- `views/templates/admin/quote_view.tpl` : styles CSS

Aucune migration BDD nécessaire (colonnes existantes).

## Tests

Exécuter le test suite :
```bash
php modules/myquotemanager/tests/DiscountTest.php
```

Teste :
- ✓ Réductions produit (% et €)
- ✓ Plafond 100%
- ✓ Calcul multi-produits

## Notes de développement

### Pour les prochaines versions

1. **Réductions globales** : Implémenter la réduction au niveau devis
2. **Conversion avec réductions** : Créer CartRule pour les réductions lors de la conversion
3. **Affichage FO** : Montrer les réductions dans le devis client
4. **PDF** : Ajouter une ligne "Réductions" dans le PDF
5. **Historique** : Tracer qui a modifié les réductions et quand

### Validation client vs serveur

- **Validation client** : rapide, feedback immédiat (% 0-100, € >= 0)
- **Validation serveur** : complète, vérifie réduction totale ≤ 100% de la ligne
- Les deux sont nécessaires pour sécurité et UX

### Performance

- Calcul des totaux en O(n) (une boucle sur les produits)
- AJAX asynchrone, ne bloque pas l'UI
- Pas de N+1 queries (batch queries dans calculateTotals)
