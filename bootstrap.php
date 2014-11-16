<?php
namespace ajaxtown\eaglehorn_framework;
use ajaxtown\eaglehorn_framework\core\controller as ehController;
/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 * @desc Fires up the application.
 */


class bootstrap
{

    var $route_callback;

    /**
     * @param Base $base
     * @internal param $config
     */
    function __construct($base)
    {
        $this->route_callback = ehController\Router::execute();
        $base->load->controller($this->route_callback[0], array(), $this->route_callback[1], $this->route_callback[2]);
    }

}
$t = __NAMESPACE__;
$base = new ehController\Base($extended = false);
new bootstrap($base);