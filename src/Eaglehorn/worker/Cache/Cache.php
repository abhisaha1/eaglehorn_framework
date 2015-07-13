<?php
namespace Eaglehorn\worker\Cache;
/**
 *More Info - https://github.com/cosenary/Simple-PHP-Cache
 */
class Cache
{

    private static $m_pInstance;
    /**
     * The last cache stored, usually for pages
     *
     * @var string
     */
    public $last_stored_cache;
    /**
     * The path to the cache file folder
     *
     * @var string
     */
    private $_cachepath;
    /**
     * The name of the default cache file
     *
     * @var string
     */
    private $_cachename = 'default';
    /**
     * The cache file extension
     *
     * @var string
     */
    private $_extension = '.cache';
    private $template_data;

    /**
     * Default constructor
     *
     * @param string|array [optional] $config
     * @return \Cache
     */
    public function __construct($config = null)
    {
        $this->_cachepath = configItem('cache')['dir'];
        if (true === isset($config)) {
            if (is_string($config)) {
                $this->setCache($config);
            } else if (is_array($config)) {
                $this->setCache($config['name']);
                $this->setCachePath($config['path']);
                $this->setExtension($config['extension']);
            }
        }
    }

    /**
     * Cache name Setter
     *
     * @param string $name
     * @return object
     */
    public function setCache($name)
    {
        $this->_cachename = $name;
        return $this;
    }

    public static function getInstance()
    {
        if (!self::$m_pInstance) {
            self::$m_pInstance = new cache();
        }

        return self::$m_pInstance;
    }

    /**
     * Check whether data accociated with a key
     *
     * @param string $key
     * @return boolean
     */
    public function isCached($key)
    {
        if (false != $this->_loadCache()) {
            $cachedData = $this->_loadCache();
            return isset($cachedData[$key]['data']);
        }
        return false;
    }

    /**
     * Load appointed cache
     *
     * @return mixed
     */
    private function _loadCache()
    {
        if (true === file_exists($this->getCacheDir())) {
            $file = file_get_contents($this->getCacheDir());
            return json_decode($file, true);
        } else {
            return false;
        }
    }

    /**
     * Get the cache directory path
     *
     * @return string
     */
    public function getCacheDir()
    {
        if (true === $this->_checkCacheDir()) {
            $filename = $this->getCache();
            $filename = preg_replace('/[^0-9a-z\.\_\-]/i', '', strtolower($filename));
            return $this->getCachePath() . $this->_getHash($filename) . $this->getExtension();
        }
    }

    /**
     * Check if a writable cache directory exists and if not create a new one
     * @return bool
     * @throws \Exception
     */
    private function _checkCacheDir()
    {
        if (!is_dir($this->getCachePath()) && !mkdir($this->getCachePath(), 0775, true)) {
            throw new \Exception('Unable to create cache directory ' . $this->getCachePath());
        } elseif (!is_writable($this->getCachePath())) {
            throw new \Exception($this->getCachePath() . ' must be readable and writeable');
        }
        return true;
    }

    /**
     * Cache path Getter
     *
     * @return string
     */
    public function getCachePath()
    {
        return $this->_cachepath;
    }

    /**
     * Cache path Setter
     *
     * @param string $path
     * @return object
     */
    public function setCachePath($path)
    {
        $this->_cachepath = $path;
        return $this;
    }

    /**
     * Cache name Getter
     *
     * @return void
     */
    public function getCache()
    {
        return $this->_cachename;
    }

    /**
     * Get the filename hash
     *
     * @return string
     */
    private function _getHash($filename)
    {
        return sha1($filename);
    }

    /**
     * Cache file extension Getter
     *
     * @return string
     */
    public function getExtension()
    {
        return $this->_extension;
    }

    /**
     * Cache file extension Setter
     *
     * @param string $ext
     * @return object
     */
    public function setExtension($ext)
    {
        $this->_extension = $ext;
        return $this;
    }

    /**
     * Store data in the cache
     *
     * @param string $key
     * @param mixed  $data
     * @param        integer [optional] $expiration
     * @return object
     */
    public function store($key, $data, $expiration = 0)
    {
        $compress_data = gzcompress($data, 9);
        $storeData = array(
            'time' => time(),
            'expire' => $expiration,
            'data' => base64_encode($compress_data)
        );
        if (true === is_array($this->_loadCache())) {
            $dataArray = $this->_loadCache();
            $dataArray[$key] = $storeData;
        } else {
            $dataArray = array($key => $storeData);
        }
        $cacheData = json_encode($dataArray);

        file_put_contents($this->getCacheDir(), $cacheData);
        return $this;
    }

    /**
     * Retrieve cached data by its key
     *
     * @param string $key
     * @param        boolean [optional] $timestamp
     * @return string
     */
    public function retrieve($key, $timestamp = false)
    {
        $cachedData = $this->_loadCache();
        (false === $timestamp) ? $type = 'data' : $type = 'time';
        return gzuncompress(base64_decode($cachedData[$key][$type], 9));
    }

    /**
     * Erase cached entry by its key
     *
     * @param string $key
     * @throws Exception
     * @return object
     */
    public function erase($key)
    {
        $cacheData = $this->_loadCache();
        if (true === is_array($cacheData)) {
            if (true === isset($cacheData[$key])) {
                unset($cacheData[$key]);
                $cacheData = json_encode($cacheData);
                file_put_contents($this->getCacheDir(), $cacheData);
            } else {
                throw new Exception("Error: erase() - Key '{$key}' not found.");
            }
        }
        return $this;
    }

    /**
     * Erase all expired entries
     *
     * @return integer
     */
    public function eraseExpired()
    {
        $cacheData = $this->_loadCache();
        if (true === is_array($cacheData)) {
            $counter = 0;
            foreach ($cacheData as $key => $entry) {
                if (true === $this->_checkExpired($entry['time'], $entry['expire'])) {
                    unset($cacheData[$key]);
                    $counter++;
                }
            }
            if ($counter > 0) {
                $cacheData = json_encode($cacheData);
                file_put_contents($this->getCacheDir(), $cacheData);
            }
            return $counter;
        }
    }

    /**
     * Check whether a timestamp is still in the duration
     *
     * @param integer $timestamp
     * @param integer $expiration
     * @return boolean
     */
    private function _checkExpired($timestamp, $expiration)
    {
        $result = false;
        if ($expiration !== 0) {
            $timeDiff = time() - $timestamp;
            ($timeDiff > $expiration) ? $result = true : $result = false;
        }
        return $result;
    }

    /**
     * Erase all cached entries
     *
     * @return object
     */
    public function eraseAll()
    {
        $cacheDir = $this->getCacheDir();
        if (true === file_exists($cacheDir)) {
            $cacheFile = fopen($cacheDir, 'w');
            fclose($cacheFile);
        }
        return $this;
    }

    public function templateData($data)
    {

        $this->template_data = $data;
        extract($data);
    }

    /**
     * Start Caching
     */
    public function startCaching()
    {
        ob_start();
    }

    /**
     * Stop Caching
     */
    public function stopCaching($display = true)
    {
        $this->last_stored_cache = ob_get_clean();

        if ($display) {
            echo $this->last_stored_cache;
        }
    }
}