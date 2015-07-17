<?php

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
 * @desc           Commonly used functions
 */

/**
 * Loads all *.config.php files from config/default
 * This function lets us grab the config file even if the Config class
 * hasn't been instantiated yet
 *
 * @access    private
 * @return    array
 */
if (!function_exists('getConfig'))
{
    /**
     * @param array $replace
     * @return array
     */
    function &getConfig($replace = array())
    {
        static $_config;

        if (isset($_config)) {
            // Are any values being dynamically replaced?
            if (count($replace) > 0) {
                foreach ($replace as $key => $val) {
                    if (isset($_config[0][$key])) {
                        $_config[0][$key] = array_merge($_config[0][$key],$val);
                    }
                }
            }
            return $_config[0];
        }

        //include environment based configurations
        $env = getEnvironment();

        //include default configurations
        foreach (glob(root."/config/default/*.config.php") as $app_config)
        {
            if(file_exists($app_config)) {
                require $app_config;
            }
        }
        if($env != "default")
        {
            foreach (glob(root."/config/{$env['environment']}/*.config.php") as $app_config)
            {
                if(file_exists($app_config)) {
                    require $app_config;
                }
            }
        }

        $core_config_file = dirname(__DIR__) . '/config/config.php';

        require($core_config_file);

        // Does the $config array exist in the file?
        if (!isset($config) OR !is_array($config)) {
            exit('Your config file does not appear to be formatted correctly.');
        }

        $config['site']['url'] = $env['url'];

        $_config[0] =& $config;
        return $_config[0];
    }
}

/**
 * Returns the specified config item
 *
 * @access    public
 * @return    mixed
 */
if (!function_exists('configItem')) {
    /**
     * @param $item
     * @return bool
     */
    function configItem($item)
    {
        static $_config_item = array();

        if (!isset($_config_item[$item])) {
            $config =& getConfig();

            if (!isset($config[$item])) {
                return FALSE;
            }
            $_config_item[$item] = $config[$item];
        }

        return $_config_item[$item];
    }
}

/**
 * @return array
 */
if(!function_exists('get_environment'))
{
    function getEnvironment()
    {
        static $_env_config = array();

        if (empty($_env_config)){
            $host = $_SERVER['HTTP_HOST'];

            $config = require(root . 'config/environment.config.php');

            foreach ($config['environment'] as $env => $url) {
                if (strpos($url, $host) > 0) {

                    $_env_config = array(
                        'environment' => $env,
                        'url' => $url
                    );

                    break;
                }
            }
        }

        return $_env_config;
    }
}

/**
 *
 */
if (!function_exists('setConfigItem'))
{
    /**
     * @param $item
     * @param array $value
     */
    function setConfigItem($item,$value=array())
    {
        getConfig(array($item => $value));
    }
}

/**
 * @return mixed
 */
if (!function_exists('getController'))
{
    function getController()
    {
        return Eaglehorn\Router::$callback[0];
    }
}

/**
 * @return mixed
 */
if(!function_exists('get_method'))
{
    function getMethod()
    {
        return Eaglehorn\Router::$callback[1];
    }
}

/**
 * @param bool $index
 * @return null
 */
if(!function_exists('getParameters'))
{
    function getParameters($index = false)
    {
        if ($index !== false) {
            return isset(Eaglehorn\Router::$callback[2][$index]) ? Eaglehorn\Router::$callback[2][$index] : null;
        }
        return Eaglehorn\Router::$callback[2];
    }
}

/**
 * Used to encrypt a string
 *
 * @param type $string
 * @return type
 */
if(!function_exists('encodeString'))
{
    function encodeString($string)
    {
        $encrypt_method = "AES-256-CBC";
        $secret_key = configItem('secret');
        $secret_iv = configItem('secret');


        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);

        $output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
        return base64_encode($output);

    }
}

/**
 * Used to decrypt a string
 *
 * @param type $encrypted_string
 * @return type
 */
if(!function_exists('decodeString'))
{
    function decodeString($encrypted_string)
    {

        $encrypt_method = "AES-256-CBC";
        $secret_key = configItem('secret');
        $secret_iv = configItem('secret');


        $key = hash('sha256', $secret_key);

        // iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
        $iv = substr(hash('sha256', $secret_iv), 0, 16);
        return openssl_decrypt(base64_decode($encrypted_string), $encrypt_method, $key, 0, $iv);
    }
}

/**
 * Formats an array with <pre> tags
 *
 * @param array $var
 * @param int   $mode - 2 to dump the array
 */
if(!function_exists('display'))
{
    function display($var, $mode = 1)
    {
        if ($mode == 1) {
            echo "<pre>";
            print_r($var);
            echo "</pre>";
        } else {
            var_dump($var);
        }
    }
}

/**
 * Get files from a folder with or without a specific extension
 *
 * @param string $folderPath
 * @param bool   $filepath
 * @param string $ext
 * @return array
 */
if(!function_exists('getFilesFromFolder'))
{
    function getFilesFromFolder($folderPath, $filepath = false, $ext = 'php')
    {

        $handle = glob($folderPath . '/*.' . $ext);

        if (!$filepath) {

            $filenames = array();

            foreach ($handle as $key => $filelocation) {

                $filenames[] = str_replace(".$ext", "", basename(preg_replace('/^.+\\\\/', '', $filelocation)));
            }

            return $filenames;
        } else {

            return $handle;
        }
    }
}

/**
 * Function list all the subfolders of a directory
 *
 * @param string $dir
 * @return array
 */
if(!function_exists('getSubDirectories'))
{
    function getSubDirectories($dir)
    {

        $handle = glob($dir . '/*', GLOB_ONLYDIR);

        $subFolders = array();

        foreach ($handle as $subfolder) {

            $subfolderSplit = explode("/", $subfolder);

            $subFolders[] = $subfolderSplit[sizeof($subfolderSplit) - 1];
        }

        return $subFolders;
    }
}


/**
 * Get browser type
 *
 * @return string
 */
if(!function_exists('getBrowser'))
{
    function getBrowser()
    {
        return $_SERVER ['HTTP_USER_AGENT'];
    }
}

/**
 * Check if the url is active/online
 *
 * @param string $url
 * @return boolean
 */
if(!function_exists('isUrlOnline'))
{
    function isUrlOnline($url)
    {
        $url = @parse_url($url);
        if (!$url)
            return false;

        $url = array_map('trim', $url);
        $url['port'] = (!isset($url['port'])) ? 80 : (int)$url['port'];

        $path = (isset($url['path'])) ? $url['path'] : '/';
        $path .= (isset($url['query'])) ? "?$url[query]" : '';

        if (isset($url['host']) && $url['host'] != gethostbyname($url['host'])) {

            $fp = fsockopen($url['host'], $url['port'], $errno, $errstr, 30);

            if (!$fp)
                return false; //socket not opened

            fputs($fp, "HEAD $path HTTP/1.1\r\nHost: $url[host]\r\n\r\n"); //socket opened
            $headers = fread($fp, 4096);
            fclose($fp);

            if (preg_match('#^HTTP/.*\s+[(200|301|302)]+\s#i', $headers)) {
                //matching header
                return true;
            } else
                return false;
        } // if parse url
        else
            return false;
    }
}


/**
 * Converts arrays into objects
 *
 * @param array $array
 * @return object
 */
if(!function_exists('arrayToObject'))
{
    function arrayToObject($array)
    {
        if (is_array($array))
            return json_decode(json_encode($array), FALSE);
    }
}

/**
 * Redirects to a different link
 *
 * @param string $path controller/method
 */
function redirect($path, $addhost = true)
{

    (!$addhost) ? header("Location: " . $path) : header("Location: " . configItem('site')['url'] . $path);
    die();
}

/**
 * Creates a random string of specified length
 *
 * @param int $length
 * @return string
 */
function getRandomString($length = 6)
{

    $validCharacters = "abcdefghijklmnopqrstuxyvwzABCDEFGHIJKLMNOPQRSTUXYVWZ+-*#&@!?";
    $validCharNumber = strlen($validCharacters);
    $result = "";
    for ($i = 0; $i < $length; $i++) {

        $index = mt_rand(0, $validCharNumber - 1);

        $result .= $validCharacters[$index];
    }

    return $result;
}

/**
 * Get the user id from an email
 * @param $email
 * @return mixed
 */
function getUserIdFromEmail($email)
{
    return preg_replace('/([^@]*).*/', '$1', $email);
}

/**
 * Check if a request parameter exist.
 * @param $name
 * @return string
 */
function ifRequestExist($name)
{
    return isset($_REQUEST[$name]) ? $_REQUEST[$name] : "";
}

/**
 * @param $url
 * @return string
 */
if(!function_exists('addHttp'))
{
    function addHttp($url)
    {
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }
        return $url;
    }
}

/**
 * @param $string
 * @return mixed
 */
if(!function_exists('fixWordText'))
{
    function fixWordText($string)
    {
        $search = array(// www.fileformat.info/info/unicode/<NUM>/ <NUM> = 2018
            "\xC2\xAB", // « (U+00AB) in UTF-8
            "\xC2\xBB", // » (U+00BB) in UTF-8
            "\xE2\x80\x98", // ‘ (U+2018) in UTF-8
            "\xE2\x80\x99", // ’ (U+2019) in UTF-8
            "\xE2\x80\x9A", // ‚ (U+201A) in UTF-8
            "\xE2\x80\x9B", // ‛ (U+201B) in UTF-8
            "\xE2\x80\x9C", // “ (U+201C) in UTF-8
            "\xE2\x80\x9D", // ” (U+201D) in UTF-8
            "\xE2\x80\x9E", // „ (U+201E) in UTF-8
            "\xE2\x80\x9F", // ‟ (U+201F) in UTF-8
            "\xE2\x80\xB9", // ‹ (U+2039) in UTF-8
            "\xE2\x80\xBA", // › (U+203A) in UTF-8
            "\xE2\x80\x93", // – (U+2013) in UTF-8
            "\xE2\x80\x94", // — (U+2014) in UTF-8
            "\xE2\x80\xA6", // … (U+2026) in UTF-8
            "\xE2\x80\xA2", // bullet (U+2026) in UTF-8
            "â€¢"  // … (U+2026) in UTF-8
        );

        $replacements = array(
            "<<", // «
            ">>", // »
            "'", // ‘
            "'", // ’
            "'", // ‚ (U+201A) in UTF-8
            "'", // ‛ (U+201B) in UTF-8
            '"', // “ (U+201C) in UTF-8
            '"', // ” (U+201D) in UTF-8
            '"', // „ (U+201E) in UTF-8
            '"', // ‟ (U+201F) in UTF-8
            "<", // ‹ (U+2039) in UTF-8
            ">", // › (U+203A) in UTF-8
            "-", // – (U+2013) in UTF-8
            "-", // – (U+2014) in UTF-8
            "...", // // … (U+2026) in UTF-8
            "&bull;", // – (U+2014) in UTF-8
            "&bull;"
        );

        return str_replace($search, $replacements, $string);
    }
}

/**
 * Gets the clinet ip
 * @return string
 */
if(!function_exists('getClientIp'))
{
    function getClientIp()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('HTTP_X_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_X_FORWARDED_FOR');
        else if (getenv('HTTP_X_FORWARDED'))
            $ipaddress = getenv('HTTP_X_FORWARDED');
        else if (getenv('HTTP_FORWARDED_FOR'))
            $ipaddress = getenv('HTTP_FORWARDED_FOR');
        else if (getenv('HTTP_FORWARDED'))
            $ipaddress = getenv('HTTP_FORWARDED');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
}
