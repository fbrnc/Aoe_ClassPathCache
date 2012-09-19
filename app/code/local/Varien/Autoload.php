<?php

/**
 * Classes source autoload
 *
 * @author Fabrizio Branca
 */
class Varien_Autoload
{
    const SCOPE_FILE_PREFIX = '__';

    static protected $_instance;
    static protected $_scope = 'default';
	static protected $_cache;
	static protected $_numberOfFilesAddedToCache = 0;

    protected $_arrLoadedClasses    = array();

    /**
     * Class constructor
     */
    public function __construct()
    {
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
			return include BP . DS . $realPath;
		}
        return false;
    }

	static function getFileFromClassname($classname) {
		return str_replace(' ', DIRECTORY_SEPARATOR, ucwords(str_replace('_', ' ', $classname))) . '.php';
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
		return BP . DS . 'var' . DS . 'classpathcache.php';
	}

	/**
	 * Load cache content from file
	 *
	 * @return array
	 */
	static public function loadCacheContent() {
		if (file_exists(self::getCacheFilePath())) {
			self::$_cache = unserialize(file_get_contents(self::getCacheFilePath()));
		}
		if (!is_array(self::$_cache)) {
			self::$_cache = array();
		}
	}

	/**
	 * Get full path
	 *
	 * @param $classname
	 * @return mixed
	 */
	static public function getFullPath($classname) {
		if (!isset(self::$_cache[$classname])) {
			self::$_cache[$classname] = self::searchFullPath(self::getFileFromClassname($classname));
			// removing the basepath
			self::$_cache[$classname] = str_replace(BP . DS, '', self::$_cache[$classname]);
			self::$_numberOfFilesAddedToCache++;
		}
		return self::$_cache[$classname];
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
			$fullpath = $path . DIRECTORY_SEPARATOR . $filename;
			if (file_exists($fullpath)) {
				return $fullpath;
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
			file_put_contents(self::getCacheFilePath(), serialize(self::$_cache));
			@chmod(self::getCacheFilePath(), 0664);
		}
	}

}
