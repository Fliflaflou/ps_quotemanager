# Tests du module myquotemanager

## Tests d'intégration
- `integration/test_crud.php` : Tests CRUD complets
- `fixtures/clean_test_data.php` : Nettoyage des données

## Utilisation
```bash
# Nettoyer avant tests
php test/fixtures/clean_test_data.php

# Lancer les tests
php test/integration/test_crud.php
