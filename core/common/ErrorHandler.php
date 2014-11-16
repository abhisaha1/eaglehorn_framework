<?php

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
 */
class ErrorHandler
{


    // CATCHABLE ERRORS
    public static function captureNormal($number, $message, $file, $line)
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


        // Insert all in one table
        $error = array('type' => $type, 'message' => $message, 'file' => $file, 'line' => $line);

        $date = date('M d, Y h:iA');

        $log_message = "<code><h1>A PHP Error was encountered:</h1>
         <p>
            <strong>Date:</strong> {$date}
         </p>
         
        <p>
            <strong>Error No:</strong> {$number}
         </p>
          
         <p>
            <strong>Type:</strong> {$type}
         </p>
         
         <p>
            <strong>Message:</strong> {$message}
         </p>
          
         <p>
            <strong>File:</strong> {$file}
         </p>
          
         <p>
            <strong>Line:</strong> {$line}
         </p>       
         
         </code>";

        include_once 'error/error_template.php';

    }

    // EXTENSIONS
    public static function captureException($exception)
    {

        $message = $exception->getMessage();
        $code = $exception->getCode();
        $file = $exception->getFile();
        $line = $exception->getLine();
        $trace = $exception->getTraceAsString();
        $date = date('M d, Y h:iA');

        $log_message = "<code><h1>Exception information:</h1>
         <p>
            <strong>Date:</strong> {$date}
         </p>
          
         <p>
            <strong>Message:</strong> {$message}
         </p>
         
         <p>
            <strong>Code:</strong> {$code}
         </p>
          
         <p>
            <strong>File:</strong> {$file}
         </p>
          
         <p>
            <strong>Line:</strong> {$line}
         </p>
          
         <h3>Stack trace:</h3>
         <pre>{$trace}
         </pre>
         <br />
         </code>";

        include_once 'error/error_template.php';

    }

    // UNCATCHABLE ERRORS
    public static function captureShutdown()
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

}

$error = configItem('error');
if ($error == 0) {

    ini_set('display_errors', 0);
    error_reporting(0);

} else if ($error == 2) {
    set_error_handler(array('ErrorHandler', 'captureNormal'));
    set_exception_handler(array('ErrorHandler', 'captureException'));
    register_shutdown_function(array('ErrorHandler', 'captureShutdown'));

} else if ($error == 1) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

}
