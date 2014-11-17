<?php
namespace eaglehorn\worker;
/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.4 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 *
 * @desc  Responsible for handling cookies
 *
 */
class Cookie
{

    const Session = null;
    const OneDay = 86400;
    const SevenDays = 604800;
    const ThirtyDays = 2592000;
    const SixMonths = 15811200;
    const OneYear = 31536000;
    const Lifetime = -1; // 2030-01-01 00:00:00

    /**
     * Returns true if there is a cookie with this name.
     *
     * @param string $name
     * @return bool
     */

    public function Exists($name)
    {
        return isset($_COOKIE[$name]);
    }

    /**
     * Returns true if there no cookie with this name or it's empty, or 0,
     * or a few other things. Check http://php.net/empty for a full list.
     *
     * @param string $name
     * @return bool
     */
    public function IsEmpty($name)
    {
        return empty($_COOKIE[$name]);
    }

    /**
     * Get the value of the given cookie. If the cookie does not exist the value
     * of $default will be returned.
     *
     * @param string $name
     * @param string $default
     * @return mixed
     */
    public function Get($name, $default = '')
    {
        return (isset($_COOKIE[$name]) ? $_COOKIE[$name] : $default);
    }

    /**
     * Set a cookie. Silently does nothing if headers have already been sent.
     *
     * @param string $name
     * @param string $value
     * @param mixed $expiry
     * @param string $path
     * @param string $domain
     * @return bool
     */
    public function Set($name, $value, $expiry = self::OneYear, $path = '/', $domain = false)
    {

        $retval = false;
        if (!headers_sent()) {

            if ($domain === false)
                $domain = $_SERVER['HTTP_HOST'];

            if ($expiry === -1)
                $expiry = 1893456000; // Lifetime = 2030-01-01 00:00:00
            elseif (is_numeric($expiry))
                $expiry += time();
            else
                $expiry = strtotime($expiry);

            //echo "$name, $value, $expiry, $path, $domain";
            $retval = setcookie($name, $value, $expiry, $path, $domain);
            if ($retval)
                $_COOKIE[$name] = $value;

        }
        return $retval;
    }

    /**
     * Delete a cookie.
     *
     * @param string $name
     * @param string $path
     * @param string $domain
     * @param bool $remove_from_global Set to true to remove this cookie from this request.
     * @return bool
     */
    public function Delete($name, $path = '/', $domain = false, $remove_from_global = true)
    {

        $retval = false;
        if (!headers_sent()) {
            if ($domain === false)
                $domain = $_SERVER['HTTP_HOST'];
            $retval = setcookie($name, '', time() - 3600, $path, $domain);

            if ($remove_from_global)
                unset($_COOKIE[$name]);
        }
        return $retval;
    }

    public function generate_sid($chars = 100, $alpha = TRUE, $numeric = TRUE, $symbols = TRUE, $timestamp = TRUE)
    {

        if ($chars <= 0 || !is_numeric($chars)) {
            return FALSE;
        }

        $salt = NULL;

        if ($alpha == TRUE) {
            $salt .= "abcdefghijklmnopqrstuvwxyz";
        }

        if ($numeric == TRUE) {
            $salt .= "1234567890";
        }

        if ($symbols == TRUE) {
            $salt .= "-_";
        }

        $sid = NULL;
        for ($c = 1; $c <= $chars; $c++) {
            $sid .= $salt{mt_rand(0, strlen($salt) - 1)};

            if (mt_rand(0, 1) == 1) {
                $sid{strlen($sid) - 1} = strtoupper($sid{strlen($sid) - 1});
            }
        }

        if ($timestamp == TRUE) {
            $sid .= time();
        }

        return $sid;
    }


}