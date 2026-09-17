# Tests du module myquotemanager

## Tests d'intégration
- `integration/test_crud.php` : Tests CRUD complets
- `fixtures/clean_test_data.php` : Nettoyage des données

## Utilisation
```bash
# Nettoyer avant tests
php tests/fixtures/clean_test_data.php

# Lancer les tests
php tests/integration/test_crud.php
