<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_QuoteManagerUninstaller
{
    public static function cleanUp()
    {
        // Nettoyer les fichiers temporaires si nécessaire
        self::cleanTempFiles();
        
        // Supprimer les répertoires vides créés par le module
        self::cleanDirectories();
        
        return true;
    }
    
    protected static function cleanTempFiles()
    {
        $tempPaths = [
            _PS_MODULE_DIR_ . 'ps_quotemanager/cache/',
            _PS_MODULE_DIR_ . 'ps_quotemanager/tmp/',
        ];
        
        foreach ($tempPaths as $path) {
            if (is_dir($path)) {
                self::removeDirectory($path);
            }
        }
    }
    
    protected static function cleanDirectories()
    {
        // Ici on peut nettoyer des répertoires spécifiques si nécessaire
        // Par exemple des uploads de fichiers liés aux devis
        $uploadPath = _PS_MODULE_DIR_ . 'ps_quotemanager/uploads/';
        if (is_dir($uploadPath)) {
            self::removeDirectory($uploadPath);
        }
    }
    
    protected static function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return false;
        }
        
        $files = array_diff(scandir($dir), ['.', '..']);
        
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                self::removeDirectory($path);
            } else {
                @unlink($path);
            }
        }
        
        return @rmdir($dir);
    }
}
