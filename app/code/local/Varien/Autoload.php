<?php

/**
 * Classes source autoload
 *
 * @author Fabrizio Branca
 */
class Varien_Autoload
{
    const CACHE_KEY_PREFIX = 'classPathCache';

    static protected $_instance;
    static protected $_scope = 'default';
    static protected $_cache = array();
    static protected $_numberOfFilesAddedToCache = 0;

    static protected $useAPC = FALSE;
    static protected $cacheKey = self::CACHE_KEY_PREFIX;

    /**
     * Class constructor
     */
    public function __construct()
    {
        if (!defined('BP')) {
            define('BP', dirname(dirname(dirname(dirname(dirname(__FILE__))))));
        }
        if (extension_loaded('apc')) {
            self::$useAPC = TRUE;
        }
        self::$cacheKey = self::CACHE_KEY_PREFIX . "_" . md5(BP);
        self::registerScope(self::$_scope);
        self::loadCacheContent();
    }

    /**
     * Singleton pattern implementation
     *
     * @return Varien_Autoload
     */
    static public function instance()
    {
        if (!self::$_instance) {
            self::$_instance = new Varien_Autoload();
        }
        return self::$_instance;
    }

    /**
     * Register SPL autoload function
     */
    static public function register()
    {
        spl_autoload_register(array(self::instance(), 'autoload'));
    }

    /**
     * Load class source code
     *
     * @param string $class
     * @return bool
     */
    public function autoload($class)
    {
        $realPath = self::getFullPath($class);
        if ($realPath !== false) {
            return include BP . DIRECTORY_SEPARATOR . $realPath;
        }
        return false;
    }

    /**
     * Get file name from class name
     *
     * @param string $className
     * @return string
     */
    static function getFileFromClassName($className) {
        return str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $className))) . '.php';
    }

    /**
     * Register autoload scope
     * This process allow include scope file which can contain classes
     * definition which are used for this scope
     *
     * @param string $code scope code
     */
    static public function registerScope($code)
    {
        self::$_scope = $code;
    }

    /**
     * Get current autoload scope
     *
     * @return string
     */
    static public function getScope()
    {
        return self::$_scope;
    }

    /**
     * Get cache file path
     *
     * @return string
     */
    static public function getCacheFilePath() {
        return BP . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'cache'. DIRECTORY_SEPARATOR . 'classPathCache.php';
    }

    /**
     * Setting cache content
     *
     * @param array $cache
     */
    static public function setCache(array $cache) {
        self::$_cache = $cache;
    }

    /**
     * Load cache content from file
     *
     * @return array
     */
    static public function loadCacheContent() {
        if (self::$useAPC) {
            $value = apc_fetch(self::$cacheKey);
            if ($value !== FALSE) {
                self::setCache($value);
            }
            return;
        }
        if (file_exists(self::getCacheFilePath())) {
            self::setCache(unserialize(file_get_contents(self::getCacheFilePath())));
        }
    }

    /**
     * Get full path
     *
     * @param $className
     * @return mixed
     */
    static public function getFullPath($className) {
        if (!isset(self::$_cache[$className])) {
            self::$_cache[$className] = self::searchFullPath(self::getFileFromClassName($className));
            // removing the basepath
            self::$_cache[$className] = str_replace(BP . DIRECTORY_SEPARATOR, '', self::$_cache[$className]);
            self::$_numberOfFilesAddedToCache++;
        }
        return self::$_cache[$className];
    }

    /**
     * Checks if a file exists in the include path and returns the full path if the file exists
     *
     * @param $filename
     * @return bool|string
     */
    static public function searchFullPath($filename)
    {
        $paths = explode(PATH_SEPARATOR, get_include_path());
        foreach ($paths as $path) {
            $fullPath = $path . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }
        return false;
    }

    /**
     * Class destructor
     */
    public function __destruct()
    {
        if (self::$_numberOfFilesAddedToCache > 0) {
            if (self::$useAPC) {
                apc_store(self::$cacheKey, self::$_cache, 0);
            } else {
                $fileContent = serialize(self::$_cache);
                $tmpFile = tempnam(sys_get_temp_dir(), 'aoe_classpathcache');
                if (file_put_contents($tmpFile, $fileContent)) {
                    if (rename($tmpFile, self::getCacheFilePath())) {
                        @chmod(self::getCacheFilePath(), 0664);
                    }
                }
            }
        }
    }

}
