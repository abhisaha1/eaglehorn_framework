<?php
namespace Eaglehorn;

/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://Eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 *
 * @desc  Router Class for routing requests
 *
 * This it the Igniter URL Router, the layer of a web application between the
 * URL and the function executed to perform a request. The router determines
 * which function to execute for a given URL.
 *
 * <code>
 *
 * // Adding a basic route
 * Router::route( '/login', 'login_function' );
 *
 * // Adding a route with a named alphanumeric capture, using the <:var_name> syntax
 * Router::route( '/user/view/<:username>', 'view_username' );
 *
 * // Adding a route with a named numeric capture, using the <#var_name> syntax
 * Router::route( '/user/view/<#user_id>', array( 'UserClass', 'view_user' ) );
 *
 * // Adding a route with a wildcard capture (Including directory separtors), using
 * // the <*var_name> syntax
 * Router::route( '/browse/<*categories>', 'category_browse' );
 *
 * // Adding a wildcard capture (Excludes directory separators), using the
 * // <!var_name> syntax
 * Router::route( '/browse/<!category>', 'browse_category' );
 *
 * // Adding a custom regex capture using the <:var_name|regex> syntax
 * Router::route( '/lookup/zipcode/<:zipcode|[0-9]{5}>', 'zipcode_func' );
 *
 * // Specifying priorities
 * Router::route( '/users/all', 'view_users', 1 ); // Executes first
 * Router::route( '/users/<:status>', 'view_users_by_status', 100 ); // Executes after
 *
 *
 *
 * // Run the router
 * Router::execute();
 * </code>
 *
 * @since 1.0.0
 */
class Router
{

    /**
     * An array containing the source url as key and destination url as value
     * @var mixed
     */
    private static $_routes = array();

    /**
     * Callback containing the controller/method/attr
     * @var array
     */
    public static $callback;

    /**
     * Contains the attribute passed to the method
     * @var mixed
     */
    private static $_attr = array();

    /**
     * Function used to add routes
     * @param string $source
     * @param string $destination
     * @param int $priority
     */
    static function route($source, $destination, $priority = 10)
    {


        self::match($source, $destination, $priority);

    }

    /**
     * Executes the router
     * @return mixed
     */
    static function execute()
    {

        //first we separate the parameters
        $request = isset($_REQUEST['route']) ? $_REQUEST['route'] : '/';

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

        $request = DIRECTORY_SEPARATOR . $request;

        //make sure the request has a trailing slash
        $request = rtrim($request, '/') . '/';

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
                    self::_set_callback($destination);
                }
            }
        }

        //if no match found, check if the url is valid
        if (!$matched_route && $request != '/') {
            self::_set_callback($request);
        }

        if ($request == '/') {
            self::_set_callback(configItem('site')['default_controller']);
        }

    }

    /**
     * Sets the callback as an array containing Controller, Method & Parameters
     * @param string $destination
     */
    private static function _set_callback($destination)
    {

        $result = explode('/', trim($destination, '/'));
        //fix the controller now
        $controller = ($result[0] == "") ? configItem('site')['default_controller'] : str_replace('-', '/', $result[0]);
        //if no method, set it to index
        $method = isset($result[1]) ? $result[1] : 'index';
        //if controller is valid file
        if (file_exists($file = configItem('site')['cust_controller_dir'] . $controller . '.php')) {
            self::$callback = array($controller, $method, self::$_attr);
        } else {
            die("<b>Exception: </b>Incorrect routing");
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

}