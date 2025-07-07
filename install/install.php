<?php
class Ps_QuoteManagerInstaller
{
    public static function checkPermissions()
    {
        $modulePath = _PS_MODULE_DIR_ . 'ps_quotemanager/';
        self::setPermissions($modulePath);
    }

    protected static function setPermissions($path)
    {
        if (!file_exists($path)) {
            return false;
        }

        // Définir les permissions pour les répertoires
        if (is_dir($path)) {
            @chmod($path, 0775);

            $items = scandir($path);
            foreach ($items as $item) {
                if ($item === '.' || $item === '..') {
                    continue;
                }
                self::setPermissions($path . $item);
            }
        } else {
            // Définir les permissions pour les fichiers
            @chmod($path, 0664);
        }

        return true;
    }
}
