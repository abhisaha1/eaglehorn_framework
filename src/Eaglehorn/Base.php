<?php
namespace Eaglehorn;

/**
 * EagleHorn
 * An open source application development framework for PHP 5.4 or newer
 *
 * @package        EagleHorn
 * @author         Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link           http://Eaglehorn.org
 * @since          Version 1.0
 * @filesource
 * @desc           Base class responsible for handling controllers
 */
class Base
{

    /**
     * Holds an instance of the loader object which is responsible for loading models, views, controller, workers.
     * @var object
     */
    public $load = null;

    /**
     * Holds the variables which are generated dynamically.
     * @var array
     */
    public $attr = array();

    /**
     * Checks if only one instance of loader class is available. If not it creates one.
     * @var object
     */
    private static $loaderInstance;

    /**
     * Loads all the custom hooks called be user
     * @var array
     */
    private static $hooks = array();
    /**
     * Base Instance.
     * @var object
     */
    private static $baseInstance;

    /**
     * Stores template data.
     * @var array
     */
    public $data_passed = "";

    /**
     * Holds the logger instance
     * @var Logger
     */
    public $logger;

    /**
     * @param bool $extended
     */
    public function __construct($extended = true)
    {

        self::$hooks = configItem('hooks');

        $this->_setLogger();

        $this->load = $this->getLoaderInstance($this->logger);

        if ($extended == true) {
            $this->load->worker(configItem('workers'));
        }
        self::$baseInstance =& $this;
    }

    private function _setLogger()
    {
        $loggerConfig = configItem('logger');

        if($this->hookActive('logger'))
        {
            $ns         = "\\application\\".self::$hooks['logger']['namespace'];
            $class      = self::$hooks['logger']['class'];
            $class_ns   = "$ns\\$class";
        }
        else
        {
            $class_ns = __NAMESPACE__."\\Logger";
        }

        $this->logger = new $class_ns($loggerConfig['file'], $loggerConfig['level']);
    }

    public function hookActive($hook)
    {
        return self::$hooks[$hook]['active'];
    }

    /**
     * @param $logger
     * @return Loader|object
     */
    public static function getLoaderInstance($logger)
    {
        if (!self::$loaderInstance) {

            self::$loaderInstance = new Loader($logger);

        }
        return self::$loaderInstance;
    }

    /**
     * @return Base|object
     */
    public static function getInstance()
    {
        return self::$baseInstance;
    }

    /**
     * @param $key
     * @return mixed
     */
    public function __get($key)
    {
        if (array_key_exists($key, $this->attr)) {

            return $this->attr[$key];

        } else if (isset($this->load->$key)) {

            return $this->load->$key;

        }
        return null;
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value)
    {
        $this->attr[$key] = $value;
    }


    /**
     * This method can be used from controller only from Controller.
     * Its main purpose is to get the content of any view.
     *
     * @param string $filename
     * @return string
     */
    public function getFileOutput($filename)
    {
        if (file_exists($filename)) {
            ob_start();
            include($filename);
            $content = ob_get_contents();
            ob_end_clean();
            $this->logger->info("View parsed - $filename");
        } else {
            $content = "";
        }
        return $content;
    }

}