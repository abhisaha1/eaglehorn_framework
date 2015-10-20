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
 * @desc           Router Class for routing requests
 *
 */
class Router
{

    /**
     * An array containing the source url as key and destination url as value
     *
     * @var mixed
     */
    private static $_routes = array();

    /**
     * Callback containing the controller/method/attr
     *
     * @var array
     */
    public static $callback;

    /**
     * Contains the attribute passed to the method
     *
     * @var mixed
     */
    private static $_attr = array();

    private static $_base;
    /**
     * Function used to add routes
     *
     * @param string $source
     * @param string $destination
     * @param int    $priority
     */
    static function route($source, $destination, $priority = 10)
    {
        self::match($source, $destination, $priority);
    }

    /**
     * Executes the router
     *
     * @return mixed
     */
    static function execute(Base $base)
    {

        self::$_base = $base;
        //first we separate the parameters
        $request = isset($_REQUEST['route']) ? $_REQUEST['route'] : '/';

        if(strpos($request,'?') > 0) {
            $split = explode('?',$request);
            $request = $split[0];
        }

        self::$_attr = array_slice(explode('/', trim($request, '/')), 2);

        self::_run($request);

        return self::$callback;
    }

    /**
     * Tries to match one of the URL routes to the current URL, otherwise
     * execute the default function.
     * Sets the callback that needs to be returned
     *
     * @param string $request
     */

    private static function _run($request)
    {
        // Whether or not we have matched the URL to a route
        $matched_route = false;

        $request = '/' . $request;

        //make sure the request has a trailing slash
        $request = rtrim($request, '/') . '/';

        $request = str_replace("//","/",$request);

        // Sort the array by priority
        ksort(self::$_routes);


        // Loop through each priority level
        foreach (self::$_routes as $priority => $routes) {
            // Loop through each route for this priority level
            foreach ($routes as $source => $destination) {

                // Does the routing rule match the current URL?
                if (preg_match($source, $request, $matches)) {
                    // A routing rule was matched
                    $matched_route = TRUE;
                    $attr = implode('/',array_intersect_key($matches, array_flip(array_filter(array_keys($matches), 'is_string'))));
                    self::$_attr = explode('/', trim($attr, '/'));
                    self::_set_callback($destination);
                }



            }
        }

        if(!$matched_route)
        {
            if($request != '/')
            {
                self::_set_callback($request);
            }
        }

    }

    /**
     * Sets the callback as an array containing Controller, Method & Parameters
     *
     * @param string $destination
     */
    private static function _set_callback($destination)
    {

        if(is_callable($destination))
        {
            self::$callback = array($destination,self::$_attr);
        }
        else
        {
            $result = explode('/', trim($destination, '/'));
            //fix the controller now
            $controller = ($result[0] == "") ? configItem('site')['default_controller'] : str_replace('-', '/', $result[0]);
            //if no method, set it to index
            $method = isset($result[1]) ? $result[1] : 'index';

            //if controller is valid file
            if (self::fileExists($file = ucfirst(configItem('site')['cust_controller_dir']) . $controller . '.php',false)) {
                self::$callback = array(ucFirst($controller), $method, self::$_attr);
            } else {
                header("HTTP/1.0 404 Not Found");
                self::$_base->hook('404',array(
                    'file' => $file,
                    'controller' => $controller,
                    'method' => $method,
                    'message' => '404'
                ));
                die();
            }
        }

    }

    private static function match($source, $destination, $priority)
    {
        // Keep the original routing rule for debugging/unit Tests
        $route = $source;

        // Make sure the route ends in a / since all of the URLs will
        $route = rtrim($route, '/') . '/';

        // Custom capture, format: <:var_name|regex>
        $route = preg_replace('/\<\:(.*?)\|(.*?)\>/', '(?P<\1>\2)', $route);

        // Alphanumeric capture (0-9A-Za-z-_), format: <:var_name>
        $route = preg_replace('/\<\:(.*?)\>/', '(?P<\1>[A-Za-z0-9\-\_]+)', $route);

        // Numeric capture (0-9), format: <#var_name>
        $route = preg_replace('/\<\#(.*?)\>/', '(?P<\1>[0-9]+)', $route);

        // Wildcard capture (Anything INCLUDING directory separators), format: <*var_name>
        $route = preg_replace('/\<\*(.*?)\>/', '(?P<\1>.+)', $route);

        // Wildcard capture (Anything EXCLUDING directory separators), format: <!var_name>
        $route = preg_replace('/\<\!(.*?)\>/', '(?P<\1>[^\/]+)', $route);

        // Add the regular expression syntax to make sure we do a full match or no match
        $route = '#^' . $route . '$#';

        // Add the route to our routing array
        self::$_routes[$priority][$route] = $destination;

    }

    public static function fileExists($fileName, $caseSensitive = true) {

        if(file_exists($fileName)) {
            return $fileName;
        }
        if($caseSensitive) return false;

        // Handle case insensitive requests
        $directoryName = dirname($fileName);
        $fileArray = glob($directoryName . '/*', GLOB_NOSORT);
        $fileNameLowerCase = strtolower($fileName);
        foreach($fileArray as $file) {
            if(strtolower($file) == $fileNameLowerCase) {
                return $file;
            }
        }
        return false;
    }

}