<?php
namespace ajaxtown\eaglehorn_framework\core\controller;

use PDOException;

/**
 * EagleHorn
 *
 * An open source application development framework for PHP 5.3 or newer
 *
 * @package        EagleHorn
 * @author        Abhishek Saha <abhisheksaha11 AT gmail DOT com>
 * @license        Available under MIT licence
 * @link        http://eaglehorn.org
 * @since        Version 1.0
 * @filesource
 *
 *
 * @desc  Responsible for handling database queries
 *
 */
class Model extends \PDO
{

    private $_error;
    private $_sql;
    private $_bind;
    private $_errorCallbackFunction;
    private $_errorMsgFormat;

    public $logger;
    public $print = false;

    /**
     * Initiating the model base class
     *
     * @internal param string $dbname
     * @internal param string $host
     * @internal param string $user
     * @internal param string $passwd
     * @param array $config
     * @internal param Logger|string $logger
     */
    public function __construct($config = array())
    {

        $this->_errorCallbackFunction = '_errorCallbackFunction';
        $options = array(
            \PDO::ATTR_PERSISTENT => false,
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
        );

        try {
            $config = (count($config) > 0) ? $config : configItem('mysql');
            parent::__construct("mysql:dbname=" . $config['db'] .
                ";host=" . $config['host'],
                $config['user'],
                $config['password'],
                $options);

        } catch (PDOException $e) {
            $this->_error = $e->getMessage();
        }

        $loggerConfig = configItem('logger');
        $this->logger = new Logger($loggerConfig['file'],$loggerConfig['level']);
    }


    /**
     * Delete query
     * @param        $table
     * @param        $where
     * @param string $bind
     */
    public function delete($table, $where, $bind = "")
    {
        $sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
        $this->run($sql, $bind);
    }


    /**
     * Execute the query
     * @param        $sql
     * @param string $bind
     * @return array|bool|int
     */
    public function run($sql, $bind = "")
    {
        $this->_sql = trim($sql);
        $this->_bind = $this->cleanup($bind);
        $this->_error = "";

        try {
            $start = microtime(true);
            $pdostmt = $this->prepare($this->_sql);
            if ($pdostmt->execute($this->_bind) !== false) {
                if (preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->_sql))
                    $return = $pdostmt->fetchAll(\PDO::FETCH_ASSOC);
                elseif (preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->_sql))
                    $return = $pdostmt->rowCount();
            }


            if ($this->print) {
                $time = microtime(true) - $start;
                $queryPreview = $this->interpolateQuery($this->_sql, $this->_bind);
                print_r($queryPreview);
            }
            return $return;

        } catch (PDOException $e) {
            $this->_error = $e->getMessage();
            if($this->print) {
                $queryPreview = $this->interpolateQuery($this->_sql, $this->_bind);
                print_r($queryPreview);
            }
            $this->debug();
            return false;
        }
    }

    /**
     * Remove all data from bind array
     *
     * @param array $bind
     * @return array
     */
    private function cleanup($bind)
    {
        if (!is_array($bind)) {
            if (!empty($bind))
                $bind = array($bind);
            else
                $bind = array();
        }
        return $bind;
    }

    /**
     * A nice method to preview your prepared statements
     *
     * @param string $query
     * @param array  $params
     * @return string
     */
    private function interpolateQuery($query, $params)
    {
        $keys = array();
        $values = array();

        # build a regular expression for each parameter
        foreach ($params as $key => $value) {
            if (is_string($key)) {
                $keys[] = '/' . $key . '/';
            } else {
                $keys[] = '/[?]/';
            }


            $values[$key] = "'$value'";

            if (is_null($value))
                $values[$key] = 'NULL';
        }

        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }

    /**
     * Used for query debugging purpose
     */
    private function debug()
    {
        if (!empty($this->_errorCallbackFunction)) {
            $error = array("Error" => $this->_error);
            if (!empty($this->_sql))
                $error["SQL Statement"] = $this->_sql;
            if (!empty($this->_bind))
                $error["Bind Parameters"] = trim(print_r($this->_bind, true));

            $backtrace = debug_backtrace();
            if (!empty($backtrace)) {
                foreach ($backtrace as $info) {
                    if (isset($info["file"]) && $info["file"] != __FILE__)
                        $error["Backtrace_Line_" . $info["line"]] = $info["file"] . " at line " . $info["line"];
                }
            }

            $msg = "";

            $msg .= "SQL Error\n" . str_repeat("-", 50);
            foreach ($error as $key => $val) {
                $msg .= "\n\n$key:\n$val";
            }

            $this->logger->error($msg);
            //$func = $this->_errorCallbackFunction;
            //@$this->$func($msg);
        }
    }

    /**
     * Insert Query
     *
     * @param string $table
     * @param string $info
     * @return boolean
     */
    public function insert($table, $info)
    {
        $fields = $this->filter($table, $info);
        $sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
        $bind = array();
        foreach ($fields as $field)
            $bind[":$field"] = $info[$field];
        return $this->run($sql, $bind);
    }

    /**
     *
     * @param string $table
     * @param array  $info
     * @return array
     */
    private function filter($table, $info)
    {
        $driver = $this->getAttribute(\PDO::ATTR_DRIVER_NAME);
        if ($driver == 'sqlite') {
            $sql = "PRAGMA table_info('" . $table . "');";
            $key = "name";
        } elseif ($driver == 'mysql') {
            $sql = "DESCRIBE " . $table . ";";
            $key = "Field";
        } else {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
            $key = "column_name";
        }

        if (false !== ($list = $this->run($sql))) {
            $fields = array();
            foreach ($list as $record)
                $fields[] = $record[$key];
            return array_values(array_intersect($fields, array_keys($info)));
        }
        return array();
    }

    /**
     * Creates a table
     *
     * @param string $sql
     * @param array  $bind
     * @return boolean
     */
    public function create($sql, $bind)
    {
        $this->run($sql, $bind);
    }

    /**
     * Select statement
     *
     * @param string       $table
     * @param string       $where
     * @param array|string $bind
     * @param string       $fields
     * @param string       $groupby
     * @param string       $having
     * @param string       $orderby
     * @param string       $limit
     * @return array
     */
    public function select($table, $where = "", $bind = "", $fields = "*", $groupby = "", $having = "", $orderby = "", $limit = "")
    {

        $sql = "SELECT " . $fields . " FROM " . $table;

        if (!empty($where)) $sql .= " WHERE " . $where;

        if (!empty($groupby)) $sql .= " Group by " . $groupby;

        if (!empty($having)) $sql .= " Having " . $having;

        if (!empty($orderby)) $sql .= " Order By " . $orderby;

        if (!empty($limit)) $sql .= " Limit " . $limit;

        $sql .= ";";

        return $this->run($sql, $bind);
    }

    /**
     * Sets the error callback function
     *
     * @param string $errorCallbackFunction
     * @param string $errorMsgFormat
     */
    public function setErrorCallbackFunction($errorCallbackFunction, $errorMsgFormat = "html")
    {
        //Variable functions for won't work with language constructs such as echo and print, so these are replaced with print_r.
        if (in_array(strtolower($errorCallbackFunction), array("echo", "print")))
            $errorCallbackFunction = "print_r";

        if (function_exists($errorCallbackFunction)) {
            $this->_errorCallbackFunction = $errorCallbackFunction;
            if (!in_array(strtolower($errorMsgFormat), array("html", "text")))
                $errorMsgFormat = "html";
            $this->_errorMsgFormat = $errorMsgFormat;
        }
    }

    /**
     * Updates a table
     *
     * @param string       $table
     * @param type         $info
     * @param string       $where
     * @param array|string $bind
     * @return int
     */
    public function update($table, $info, $where, $bind = "")
    {
        $fields = $this->filter($table, $info);
        $fieldSize = sizeof($fields);

        $sql = "UPDATE " . $table . " SET ";
        for ($f = 0; $f < $fieldSize; ++$f) {
            if ($f > 0)
                $sql .= ", ";
            $sql .= $fields[$f] . " = :update_" . $fields[$f];
        }
        $sql .= " WHERE " . $where . ";";

        $bind = $this->cleanup($bind);
        foreach ($fields as $field)
            $bind[":update_$field"] = $info[$field];

        return $this->run($sql, $bind);
    }
}