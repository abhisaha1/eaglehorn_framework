<?php
namespace Eaglehorn\error;

use Eaglehorn\Base;

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
 */
class ErrorHandler
{
    protected $base;

    public function __construct(Base $base)
    {
        $this->base = $base;
        $this->setHandlers();
    }

    // CATCHABLE ERRORS
    public function captureNormal($number, $message, $file, $line)
    {
        $type = "";
        switch ($number) {

            case E_ERROR:
                $type = 'Error';

                break;

            case E_WARNING:
                $type = 'Warning';

                break;

            case E_PARSE:
                $type = 'Parsing Error';

                break;

            case E_NOTICE:
                $type = 'Notice';

                break;

            case E_CORE_ERROR:
                $type = 'Core Error';

                break;

            case E_CORE_WARNING:
                $type = 'Core Warning';

                break;

            case E_USER_ERROR:
                $type = 'User Error';

                break;

            case E_USER_WARNING:
                $type = 'User Warning';

                break;

            case E_USER_NOTICE:
                $type = 'User Notice';

                break;

            case E_STRICT:
                $type = 'Runtime Notice';

                break;

            case E_RECOVERABLE_ERROR:
                $type = 'Recoverable Error';

                break;

            case E_ALL:
                $type = E_ALL;

                break;
            default:
                break;
        }

        $error['heading'] = $type;
        $error['message'] = $message;
        $error['type'] = $type;
        $error['code'] = 0;
        $error['file'] = $file;
        $error['line'] = $line;
        $error['trace'] = '';
        $error['date'] = date('M d, Y h:iA');

        self::display($error);

    }

    // EXTENSIONS
    public function captureException($exception)
    {
        $error['heading'] = 'Exception';
        $error['message'] = $exception->getMessage();
        $error['type'] = 'Exception';
        $error['code'] = $exception->getCode();
        $error['file'] = $exception->getFile();
        $error['line'] = $exception->getLine();
        $error['trace'] = $exception->getTraceAsString();
        $error['date'] = date('M d, Y h:iA');

        self::display($error);

    }

    // UNCATCHABLE ERRORS
    public function captureShutdown()
    {
        $error = error_get_last();
        if ($error) {
            ## IF YOU WANT TO CLEAR ALL BUFFER, UNCOMMENT NEXT LINE:
            # ob_end_clean( );
            // Display content $error variable
            self::captureNormal($error['type'], $error['message'], $error['file'], $error['line']);
        } else {
            return true;
        }
    }

    /**
     * @param mixed $error
     */
    function display($error)
    {
        $log_message = "<code><h1>{$error['heading']}</h1>
         <p>
            <strong>Date:</strong> {$error['date']}
         </p>

         <p>
            <strong>Type:</strong> {$error['type']}
         </p>

         <p>
            <strong>Message:</strong> {$error['message']}
         </p>

         <p>
            <strong>File:</strong> {$error['file']}
         </p>

         <p>
            <strong>Line:</strong> {$error['line']}
         </p>";

        if ($error['trace'] != '') {
            $log_message .= "<h3>Stack trace:</h3>
                 <pre>{$error['trace']}
                 </pre>
                 <br />";
        }

        $log_message .= "</code>";

        $this->base->hook('error',$error);

        include_once 'error_template.php';
    }

    /**
     * Sets the Error Handlers
     */
    public function setHandlers()
    {
        $error = configItem('error');

        if ($error == 0)
        {
            ini_set('display_errors', 0);
            error_reporting(0);
        }
        else if ($error == 2)
        {
            error_reporting(0);
            set_error_handler(array($this, 'captureNormal'));
            set_exception_handler(array($this, 'captureException'));
            register_shutdown_function(array($this, 'captureShutdown'));

        }
        else if ($error == 1)
        {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
        }
    }

}


