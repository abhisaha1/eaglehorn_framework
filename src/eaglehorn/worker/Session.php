<?php
namespace Eaglehorn\worker;

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
 * @desc           Responsible for handling sessions with database support
 */
class Session
{

    /**
     * @description PDO database handle
     * @access      private
     * @var PDO database resource
     * */
    private $_db = NULL;

    /**
     * @description Database table name where sessions are stored.
     * @access      private
     * @var string
     * */
    private $_table_name = 'sessions';

    /**
     * @description Cookie name where the session ID is stored.
     * @access      private
     * @var string
     * */
    private $_cookie_name = 'session_cookie';

    /**
     * @description Number of seconds before the session expires. Default is 2 hours.
     * @access      private
     * @var integer
     * */
    private $_seconds_till_expiration = 7200; // 2 hours

    /**
     * @description Number of seconds before the session ID is regenerated. Default is 5 minutes.
     * @access      private
     * @var integer
     * */
    private $_renewal_time = 300; // 5 minutes

    /**
     * @description Closes the session when the browser is closed.
     * @access      private
     * @var boolean
     * */
    private $_expire_on_close = FALSE;

    /**
     * @description IP address that will be checked against the database if enabled. Must be a valid IP address.
     * @access      private
     * @var string
     * */
    private $_ip_address = FALSE;

    /**
     * @description User agent that will be checked against the database if enabled.
     * @access      private
     * @var string
     * */
    private $_user_agent = FALSE;

    /**
     * @description Will only set the session cookie if a secure HTTPS connection is being used.
     * @access      private
     * @var boolean
     * */
    private $_secure_cookie = FALSE;

    /**
     * @description A hashed string which is the ID of the session.
     * @access      private
     * @var string
     * */
    private $_session_id = '';

    /**
     * @description Data stored by the user.
     * @access      private
     * @var array
     * */
    private $_data = array();

    private $config = array();

    /**
     * @description Initializes the session handler.
     * @access      public
     * @throws Exception
     * @internal    param $array - configuration options
     */
    public function __construct($config = array(), $db = array())
    {
        // Sets user configuration

        $this->config['session'] = (count($config) > 0) ? $config : configItem('session');
        $this->config['db'] = (count($db) > 0) ? $db : configItem('mysql');

        $this->_setConfig();

        // Runs the session mechanism
        if ($this->_read()) {
            $this->_update();
        } else {
            $this->_create();
        }

        // Cleans expired sessions if necessary and writes cookie
        $this->_cleanExpired();
        $this->_setCookie();
    }

    /**
     * @description Regenerates a new session ID for the current session.
     * @access      public
     * @return void
     * */
    public function regenerateId()
    {
        // Acquires a new session ID
        $old_session_id = $this->_session_id;
        $this->_session_id = $this->_generateId();

        // Updates session ID in the database
        $stmt = $this->_db->prepare("UPDATE {$this->_table_name} SET time_updated = ?, session_id = ? WHERE session_id = ?");
        $stmt->execute(array(time(), $this->_session_id, $old_session_id));

        // Updates cookie
        $this->_setCookie();
    }

    /**
     * @description Sets a specific item to the session data array.
     * @access      public
     * @param string - session data array key
     * @param string - data value
     * @return void
     * */
    public function Set($key, $value)
    {
        $this->_data[$key] = $value;
        $this->_write(); // Writes to database
    }

    /**
     * @description Unsets a specific item from the session data array.
     * @access      public
     * @param string - session data array key
     * @return void
     * */
    public function Remove($key)
    {
        if (isset($this->_data[$key]))
            unset($this->_data[$key]);
    }

    /**
     * @description Returns a specific item from the session data array.
     * @access      public
     * @param string - session data array key
     * @return string - data value/FALSE
     * */
    public function Get($key)
    {
        return isset($this->_data[$key]) ? $this->_data[$key] : FALSE;
    }

    /**
     * @description Returns all items in the session data array.
     * @access      public
     * @return array
     * */
    public function GetAll()
    {
        return $this->_data;
    }

    /**
     * @description Destroys the current session.
     * @access      public
     * @return void
     * */
    public function destroy()
    {
        // Deletes session from the database
        if (isset($this->_session_id)) {
            $stmt = $this->_db->prepare("DELETE FROM {$this->_table_name} WHERE session_id = ?");
            $stmt->execute(array($this->_session_id));
        }

        // Kills the cookie
        setcookie(
            $this->_cookie_name, '', time() - 31500000, NULL, NULL, NULL, NULL
        );
    }

    /**
     * @description The main session mechanism:
     *      - Reads session cookie and retrives session data
     *      - Checks session expiration
     *      - Verifies IP address (if enabled)
     *      - Verifies user agent (if enabled)
     * @access      private
     * @return void
     * */
    private function _read()
    {
        // Fetches session cookie
        $session_id = isset($_COOKIE[$this->_cookie_name]) ? $_COOKIE[$this->_cookie_name] : FALSE;

        // Cookie doesn't exist!
        if (!$session_id) {
            return FALSE;
        }

        $this->_session_id = $session_id;

        // Fetches the session from the database
        $stmt = $this->_db->prepare("SELECT data, time_updated, user_agent, ip_address FROM {$this->_table_name} WHERE session_id = ?");
        $stmt->execute(array($this->_session_id));

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Did a session exist?
        if ($result !== FALSE && count($result) > 0) {
            // Checks if the session has expired in the database
            if (!$this->_expire_on_close) {
                if (($result['time_updated'] + $this->_seconds_till_expiration) < time()) {
                    $this->destroy();
                    return FALSE;
                }
            }

            // Checks if the user's IP address matches the one saved in the database
            if ($this->_ip_address) {
                if ($result['ip_address'] != $this->_ip_address) {
                    $this->_flagForUpdate();
                    return FALSE;
                }
            }

            // Checks if the user's user agent matches the one saved in the database
            if ($this->_user_agent) {
                if ($result['user_agent'] != $this->_user_agent) {
                    $this->_flagForUpdate();
                    return FALSE;
                }
            }

            // Checks if the session has been requested to regenerate a new ID (hack attempt)
            $this->_checkUpdateFlag();

            // Checks if the session ID needs to be renewed (time exceeded)
            $this->_checkIdRenewal();

            // Sets user data
            $user_data = unserialize($result['data']);

            if ($user_data) {
                $this->_data = $user_data;
                unset($user_data);
            }

            // All good!
            return TRUE;
        }

        // No session found
        return FALSE;
    }

    /**
     * @description Creates a session.
     * @access      private
     * @return void
     * */
    private function _create()
    {
        // Generates session ID
        $this->_session_id = $this->_generateId();

        // Inserts session into database
        $stmt = $this->_db->prepare("INSERT INTO {$this->_table_name} (session_id, user_agent, ip_address, time_updated) VALUES (?, ?, ?, ?)");
        $stmt->execute(array($this->_session_id, $this->_user_agent, $this->_ip_address, time()));
    }

    /**
     * @description Updates a current session.
     * @access      private
     * @return void
     * */
    private function _update()
    {
        // Updates session in database
        $stmt = $this->_db->prepare("UPDATE {$this->_table_name} SET time_updated = ? WHERE session_id = ?");
        $stmt->execute(array(time(), $this->_session_id));
    }

    /**
     * @description Writes session data to the database.
     * @access      private
     * @return void
     * */
    private function _write()
    {
        // Custom data doesn't exist
        if (count($this->_data) == 0) {
            $custom_data = '';
        } else {
            $custom_data = serialize($this->_data);
        }

        // Writes session data to database
        $stmt = $this->_db->prepare("UPDATE {$this->_table_name} SET data = ?, time_updated = ? WHERE session_id = ?");
        $stmt->execute(array($custom_data, time(), $this->_session_id));
    }

    /**
     * @description Sets session cookie.
     * @access      private
     * @return void
     * */
    private function _setCookie()
    {
        setcookie(
            $this->_cookie_name, $this->_session_id, ($this->_expire_on_close) ? 0 : time() + $this->_seconds_till_expiration, // Expiration timestamp
            NULL, NULL, $this->_secure_cookie, // Will cookie be set without HTTPS?
            TRUE // HttpOnly
        );
    }

    /**
     * @description Removes expired sessions from the database.
     * @access      private
     * @return void
     * */
    private function _cleanExpired()
    {
        // 0.1 % chance to clean the database of expired sessions
        if (mt_rand(1, 1000) == 1) {
            $stmt = $this->_db->prepare("DELETE FROM {$this->_table_name} WHERE (time_updated + {$this->_seconds_till_expiration}) < ?");
            $stmt->execute(array(time()));
        }
    }

    /**
     * @description Creates a unique session ID.
     * @access      private
     * @return string
     * */
    private function _generateId()
    {
        $salt = 'x7^!bo3p,.$$!$6[&Q.#,//@i"%[X';
        $random_number = mt_rand(0, mt_getrandmax());
        $ip_address_fragment = md5(substr($_SERVER['REMOTE_ADDR'], 0, 5));
        $timestamp = md5(microtime(TRUE) . time());

        $hash_data = $random_number . $ip_address_fragment . $salt . $timestamp;
        $hash = hash('sha256', $hash_data);

        return $hash;
    }

    /**
     * @description Checks if the session ID needs to be regenerated and does so if necessary.
     * @access      private
     * @return void
     * */
    private function _checkIdRenewal()
    {
        // Gets the last time the session was updated
        $stmt = $this->_db->prepare("SELECT time_updated FROM {$this->_table_name} WHERE session_id = ?");
        $stmt->execute(array($this->_session_id));

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result !== FALSE && count($result) > 0) {
            // Checks if the session ID has exceeded it's permitted lifespan.
            if ((time() - $this->_renewal_time) > $result['time_updated']) {
                // Regenerates a new session ID
                $this->regenerateId();
            }
        }
    }

    /**
     * @description Flags a session so that it will receive a new ID on the next subsequent request.
     * @access      private
     * @return void
     * */
    private function _flagForUpdate()
    {
        $stmt = $this->_db->prepare("UPDATE {$this->_table_name} SET flagged_for_update = '1' WHERE session_id = ?");
        $stmt->execute(array($this->_session_id));
    }

    /**
     * @description Checks if the session has been requested to regenerate a new ID and does so if necessary.
     * @access      private
     * @return void
     * */
    private function _checkUpdateFlag()
    {
        // Gets flagged status
        $stmt = $this->_db->prepare("SELECT flagged_for_update FROM {$this->_table_name} WHERE session_id = ?");
        $stmt->execute(array($this->_session_id));

        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($result !== FALSE && count($result) > 0) {
            // Flagged?
            if ($result['flagged_for_update']) {
                // Creates a new session ID
                $this->regenerateId();

                // Updates database
                $stmt = $this->_db->prepare("UPDATE {$this->_table_name} SET flagged_for_update = '0' WHERE session_id = ?");
                $stmt->execute(array($this->_session_id));
            }
        }
    }

    /**
     * @description Sets configuration.
     * @access      private
     * @throws \Exception
     * @internal    param array $config - configuration options
     * @return void
     */
    private function _setConfig()
    {
        try {
            $pdo = new \PDO('mysql:host=' . $this->config['db']['host'] . ';dbname=' . $this->config['db']['db'], $this->config['db']['user'], $this->config['db']['password'], array(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_WARNING));

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }

        $this->_db = $pdo;


        // --------------------------------------------
        // Cookie name
        if (isset($config['cookie_name'])) {
            // Checks if alpha-numeric
            if (!ctype_alnum(str_replace(array('-', '_'), '', $config['cookie_name']))) {
                throw new \Exception('Invalid cookie name!');
            }

            $this->_cookie_name = $config['cookie_name'];
        }

        // --------------------------------------------
        // Database table name
        if (isset($config['table_name'])) {
            // Checks if alpha-numeric
            if (!ctype_alnum(str_replace(array('-', '_'), '', $config['table_name']))) {
                throw new \Exception('Invalid table name!');
            }

            $this->_table_name = $config['table_name'];
        }

        // --------------------------------------------
        // Expiration time in seconds
        if (isset($config['seconds_till_expiration'])) {
            // Anything else than digits?
            if (!is_int($config['seconds_till_expiration']) || !preg_match('#[0-9]#', $config['seconds_till_expiration'])) {
                throw new \Exception('Seconds till expiration must be a valid number.');
            }

            // Negative number or zero?
            if ($config['seconds_till_expiration'] < 1) {
                throw new \Exception('Seconds till expiration can not be zero or less. Enable session expiration when the browser closes instead.');
            }

            $this->_seconds_till_expiration = (int)$config['seconds_till_expiration'];
        }

        // --------------------------------------------
        // End the session when the browser is closed?
        if (isset($config['expire_on_close'])) {
            // Not TRUE or FALSE?
            if (!is_bool($config['expire_on_close'])) {
                throw new \Exception('Expire on close must be either TRUE or FALSE.');
            }

            $this->_expire_on_close = $config['expire_on_close'];
        }

        // --------------------------------------------
        // How often should the session be renewed?
        if (isset($config['renewal_time'])) {
            // Anything else than digits?
            if (!is_int($config['renewal_time']) || !preg_match('#[0-9]#', $config['renewal_time'])) {
                throw new \Exception('Session renewal time must be a valid number.');
            }

            // Negative number or zero?
            if ($config['renewal_time'] < 1) {
                throw new \Exception('Session renewal time can not be zero or less.');
            }

            $this->_renewal_time = (int)$config['renewal_time'];
        }

        // --------------------------------------------
        // Check IP addresses?
        if (isset($config['check_ip_address'])) {
            // Not a string?
            if (!is_string($config['check_ip_address'])) {
                throw new \Exception('The IP address must be a string similar to this: \'172.16.254.1\'.');
            }

            // Invalid IP?
            if (!preg_match('/^(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]).){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/', $config['check_ip_address'])) {
                throw new \Exception('Invalid IP address.');
            }

            $this->_ip_address = $config['check_ip_address'];
        }

        // --------------------------------------------
        // Check user agent?
        if (isset($config['check_user_agent'])) {
            $this->_user_agent = substr($config['check_user_agent'], 0, 999);
        }

        // --------------------------------------------
        // Send cookie only when HTTPS is enabled?
        if (isset($config['secure_cookie'])) {
            if (!is_bool($config['secure_cookie'])) {
                throw new \Exception('The secure cookie option must be either TRUE or FALSE.');
            }

            $this->_secure_cookie = $config['secure_cookie'];
        }
    }

}