<?php
namespace Eaglehorn\worker\Session;

/**
 *  A PHP library acting as a drop-in replacement for PHP's default session handler, but instead of storing session data
 *  in flat files it stores them in a MySQL database, providing both better performance and better security and
 *  protection against session fixation and session hijacking.
 *
 *  This script was originally written by Stefan Gabos by the name ZebraSession.
 *  I have modified the script to make it use with Eaglehorn Framework.
 */

class Session
{

    private $flashdata;
    private $flashdata_varname;
    private $session_lifetime;
    private $link;
    private $lock_timeout;
    private $lock_to_ip;
    private $lock_to_user_agent;
    private $table_name;

    function __construct($db = array(),$config = array())
    {
        $config = (count($config) > 0) ? $config : configItem('session');
        $db_config = (count($db) > 0) ? $db : configItem('mysql');

        // the table to be used by the class
        $this->table_name = $config['table_name'];

        try {
            $this->link = mysqli_connect($db_config['host'], $db_config['user'], $db_config['password'], $db_config['db']);

        } catch (\Exception $e) {

            throw new \Exception($e->getMessage());
        }

        if(!$this->_tableExists())
        {
            $this->_createTable();
        }

        // continue if there is an active MySQL connection
        if ($this->_mysql_ping()) {

            // make sure session cookies never expire so that session lifetime
            // will depend only on the value of $session_lifetime
            ini_set('session.cookie_lifetime', 0);

            // if $session_lifetime is specified and is an integer number
            if ($config['session_lifetime'] != '' && is_integer($config['session_lifetime']))

                // set the new value
                ini_set('session.gc_maxlifetime', (int)$config['session_lifetime']);

            // if $gc_probability is specified and is an integer number
            if ($config['gc_probability'] != '' && is_integer($config['gc_probability']))

                // set the new value
                ini_set('session.gc_probability', $config['gc_probability']);

            // if $gc_divisor is specified and is an integer number
            if ($config['gc_divisor'] != '' && is_integer($config['gc_divisor']))

                // set the new value
                ini_set('session.gc_divisor', $config['gc_divisor']);

            // get session lifetime
            $this->session_lifetime = ini_get('session.gc_maxlifetime');

            // we'll use this later on in order to try to prevent HTTP_USER_AGENT spoofing
            $this->security_code = $config['security_code'];

            // some other defaults
            $this->lock_to_user_agent = $config['lock_to_user_agent'];
            $this->lock_to_ip = $config['lock_to_ip'];



            // the maximum amount of time (in seconds) for which a process can lock the session
            $this->lock_timeout = $config['lock_timeout'];

            // register the new handler
            session_set_save_handler(
                array(&$this, 'open'),
                array(&$this, 'close'),
                array(&$this, 'read'),
                array(&$this, 'write'),
                array(&$this, 'destroy'),
                array(&$this, 'gc')
            );

            // start the session
            session_start();

            // the name for the session variable that will be created upon script execution
            // and destroyed when instantiating this library, and which will hold information
            // about flashdata session variables
            $this->flashdata_varname = '_eaglehorn_session_flashdata_ec3asbuiad';

            // assume no flashdata
            $this->flashdata = array();

            // if there are any flashdata variables that need to be handled
            if (isset($_SESSION[$this->flashdata_varname])) {

                // store them
                $this->flashdata = unserialize($_SESSION[$this->flashdata_varname]);

                // and destroy the temporary session variable
                unset($_SESSION[$this->flashdata_varname]);

            }

            // handle flashdata after script execution
            register_shutdown_function(array($this, '_manage_flashdata'));

            // if no MySQL connections could be found
            // trigger a fatal error message and stop execution
        } else trigger_error('Session Worker: No MySQL connection!', E_USER_ERROR);

    }

    /**
     *  Get the number of active sessions - sessions that have not expired.
     *
     *  @return integer     Returns the number of active (not expired) sessions.
     */
    public function get_active_sessions()
    {

        // call the garbage collector
        $this->gc();

        // counts the rows from the database
        $result = @mysqli_fetch_assoc($this->_mysql_query('

            SELECT
                COUNT(session_id) as count
            FROM ' . $this->table_name . '

        ')) or die(_mysql_error());

        // return the number of found rows
        return $result['count'];

    }

    /**
     *  Queries the system for the values of <i>session.gc_maxlifetime</i>, <i>session.gc_probability</i> and <i>session.gc_divisor</i>
     *  and returns them as an associative array.
     *
     *
     *  @return array   Returns the values of <i>session.gc_maxlifetime</i>, <i>session.gc_probability</i> and <i>session.gc_divisor</i>
     *                  as an associative array.
     *
     */
    public function get_settings()
    {

        // get the settings
        $gc_maxlifetime = ini_get('session.gc_maxlifetime');
        $gc_probability = ini_get('session.gc_probability');
        $gc_divisor     = ini_get('session.gc_divisor');

        // return them as an array
        return array(
            'session.gc_maxlifetime'    =>  $gc_maxlifetime . ' seconds (' . round($gc_maxlifetime / 60) . ' minutes)',
            'session.gc_probability'    =>  $gc_probability,
            'session.gc_divisor'        =>  $gc_divisor,
            'probability'               =>  $gc_probability / $gc_divisor * 100 . '%',
        );

    }

    /**
     *  Regenerates the session id.
     *
     *  @return void
     */
    public function regenerate_id()
    {

        // regenerates the id (create a new session with a new id and containing the data from the old session)
        // also, delete the old session
        session_regenerate_id(true);

    }

    /**
     *  Sets a "flashdata" session variable which will only be available for the next server request, and which will be
     *  automatically deleted afterwards.
     *
     *  @param  string  $name   The name of the session variable.
     *
     *  @param  string  $value  The value of the session variable.
     *
     *  @return void
     */
    public function set_flashdata($name, $value)
    {

        // set session variable
        $_SESSION[$name] = $value;

        // initialize the counter for this flashdata
        $this->flashdata[$name] = 0;

    }

    /**
     *  Deletes all data related to the session
     *  @return void
     */
    public function stop()
    {

        $this->regenerate_id();

        session_unset();

        session_destroy();

    }

    /**
     *  Custom close() function
     *
     *  @access private
     */
    function close()
    {

        // release the lock associated with the current session
        $this->_mysql_query('SELECT RELEASE_LOCK("' . $this->session_lock . '")')

        // stop execution and print message on error
        or die($this->_mysql_error());

        return true;

    }

    /**
     *  Custom destroy() function
     *
     *  @access private
     */
    function destroy($session_id)
    {

        // deletes the current session id from the database
        $result = $this->_mysql_query('

            DELETE FROM
                ' . $this->table_name . '
            WHERE
                session_id = "' . $this->_mysql_real_escape_string($session_id) . '"

        ') or die($this->_mysql_error());

        // if anything happened
        // return true
        if ($this->_mysql_affected_rows() !== -1) return true;

        // if something went wrong, return false
        return false;

    }

    /**
     *  Custom gc() function (garbage collector)
     *
     *  @access private
     */
    function gc()
    {

        // deletes expired sessions from database
        $result = $this->_mysql_query('

            DELETE FROM
                ' . $this->table_name . '
            WHERE
                session_expire < "' . $this->_mysql_real_escape_string(time()) . '"

        ') or die($this->_mysql_error());

    }

    /**
     *  Custom open() function
     *
     *  @access private
     */
    function open($save_path, $session_name)
    {

        return true;

    }

    /**
     *  Custom read() function
     *
     *  @access private
     */
    function read($session_id)
    {

        // get the lock name, associated with the current session
        $this->session_lock = $this->_mysql_real_escape_string('session_' . $session_id);

        // try to obtain a lock with the given name and timeout
        $result = $this->_mysql_query('SELECT GET_LOCK("' . $this->session_lock . '", ' . $this->_mysql_real_escape_string($this->lock_timeout) . ')');

        // if there was an error
        // stop execution
        if (!is_object($result) || strtolower(get_class($result)) != 'mysqli_result' || @mysqli_num_rows($result) != 1 || !($row = mysqli_fetch_array($result)) || $row[0] != 1) die('Session Worker: Could not obtain session lock!');

        //  reads session data associated with a session id, but only if
        //  -   the session ID exists;
        //  -   the session has not expired;
        //  -   if lock_to_user_agent is TRUE and the HTTP_USER_AGENT is the same as the one who had previously been associated with this particular session;
        //  -   if lock_to_ip is TRUE and the host is the same as the one who had previously been associated with this particular session;
        $hash = '';

        // if we need to identify sessions by also checking the user agent
        if ($this->lock_to_user_agent && isset($_SERVER['HTTP_USER_AGENT']))

            $hash .= $_SERVER['HTTP_USER_AGENT'];

        // if we need to identify sessions by also checking the host
        if ($this->lock_to_ip && isset($_SERVER['REMOTE_ADDR']))

            $hash .= $_SERVER['REMOTE_ADDR'];

        // append this to the end
        $hash .= $this->security_code;

        $result = $this->_mysql_query('

            SELECT
                session_data
            FROM
                ' . $this->table_name . '
            WHERE
                session_id = "' . $this->_mysql_real_escape_string($session_id) . '" AND
                session_expire > "' . time() . '" AND
                hash = "' . $this->_mysql_real_escape_string(md5($hash)) . '"
            LIMIT 1

        ') or die($this->_mysql_error());

        // if anything was found
        if (is_object($result) && strtolower(get_class($result)) == 'mysqli_result' && @mysqli_num_rows($result) > 0) {

            // return found data
            $fields = @mysqli_fetch_assoc($result);

            // don't bother with the unserialization - PHP handles this automatically
            return $fields['session_data'];

        }

        $this->regenerate_id();

        // on error return an empty string - this HAS to be an empty string
        return '';

    }

    /**
     *  Custom write() function
     *
     *  @access private
     */
    function write($session_id, $session_data)
    {

        // insert OR update session's data - this is how it works:
        // first it tries to insert a new row in the database BUT if session_id is already in the database then just
        // update session_data and session_expire for that specific session_id
        // read more here http://dev.mysql.com/doc/refman/4.1/en/insert-on-duplicate.html
        $result = $this->_mysql_query('

            INSERT INTO
                ' . $this->table_name . ' (
                    session_id,
                    hash,
                    session_data,
                    session_expire
                )
            VALUES (
                "' . $this->_mysql_real_escape_string($session_id) . '",
                "' . $this->_mysql_real_escape_string(md5(($this->lock_to_user_agent && isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '') . ($this->lock_to_ip && isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '') . $this->security_code)) . '",
                "' . $this->_mysql_real_escape_string($session_data) . '",
                "' . $this->_mysql_real_escape_string(time() + $this->session_lifetime) . '"
            )
            ON DUPLICATE KEY UPDATE
                session_data = "' . $this->_mysql_real_escape_string($session_data) . '",
                session_expire = "' . $this->_mysql_real_escape_string(time() + $this->session_lifetime) . '"

        ') or die($this->_mysql_error());

        // if anything happened
        if ($result) {

            // note that after this type of queries, mysqli_affected_rows() returns
            // - 1 if the row was inserted
            // - 2 if the row was updated

            // if the row was updated
            // return TRUE
            if (@$this->_mysql_affected_rows() > 1) return true;

            // if the row was inserted
            // return an empty string
            else return '';

        }

        // if something went wrong, return false
        return false;

    }

    /**
     *  Manages flashdata behind the scenes
     *
     *  @access private
     */
    function _manage_flashdata()
    {

        // if there is flashdata to be handled
        if (!empty($this->flashdata)) {

            // iterate through all the entries
            foreach ($this->flashdata as $variable => $counter) {

                // increment counter representing server requests
                $this->flashdata[$variable]++;

                // if we're past the first server request
                if ($this->flashdata[$variable] > 1) {

                    // unset the session variable
                    unset($_SESSION[$variable]);

                    // stop tracking
                    unset($this->flashdata[$variable]);

                }

            }

            // if there is any flashdata left to be handled
            if (!empty($this->flashdata))

                // store data in a temporary session variable
                $_SESSION[$this->flashdata_varname] = serialize($this->flashdata);

        }

    }

    /**
     *  Wrapper for PHP's "mysqli_affected_rows" function.
     *
     *  @access private
     */
    private function _mysql_affected_rows()
    {

        // execute "mysqli_affected_rows" and returns the result
        return mysqli_affected_rows($this->link);

    }

    /**
     *  Wrapper for PHP's "mysqli_error" function.
     *
     *  @access private
     */
    private function _mysql_error()
    {

        // execute "mysqli_error" and returns the result
        return 'Session Worker: ' . mysqli_error($this->link);

    }

    /**
     *  Wrapper for PHP's "mysqli_query" function.
     *
     *  @access private
     */
    private function _mysql_query($query)
    {

        // execute "mysqli_query" and returns the result
        return mysqli_query($this->link, $query);

    }

    /**
     *  Wrapper for PHP's "mysqli_ping" function.
     *
     *  @access private
     */
    private function _mysql_ping()
    {

        // execute "mysqli_ping" and returns the result
        return mysqli_ping($this->link);

    }

    /**
     *  Wrapper for PHP's "mysqli_real_escape_string" function.
     *
     *  @access private
     */
    private function _mysql_real_escape_string($string)
    {

        // execute "mysqli_real_escape_string" and returns the result
        return mysqli_real_escape_string($this->link, $string);

    }

    /**
     * Check if a table exists in the current database.
     *
     * @return bool TRUE if table exists, FALSE if no table found.
     */
    private function _tableExists() {

        // Try a select statement against the table
        // Run it in try/catch in case PDO is in ERRMODE_EXCEPTION.
        try {
            $result = mysqli_query($this->link,"SELECT 1 FROM $this->table_name LIMIT 1");
        } catch (\Exception $e) {
            // We got an exception == table not found
            return FALSE;
        }

        // Result is either boolean FALSE (no table found) or PDOStatement Object (table found)
        return $result !== FALSE;
    }

    private function _createTable() {

        try {

            $result = mysqli_query($this->link,"CREATE TABLE $this->table_name (
                  session_id varchar(32) DEFAULT '' NOT NULL,
                  hash varchar(32) DEFAULT '' NOT NULL,
                  session_data blob NOT NULL,
                  session_expire int(11) DEFAULT '0' NOT NULL,
                  PRIMARY KEY  (session_id)

            )");

        }catch(\Exception $e) {

            return false;
        }
display($this->link->errorInfo());
        return $result !== FALSE;
    }

}