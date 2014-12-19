<?php
namespace Eaglehorn\assembly\Mail;

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
require("class.phpmailer.php");


class Mail extends PHPMailer
{

    // Set default variables for all new objects
    public $SMTPDebug;
    public $SMTPAuth;                   // enable SMTP authentication
    public $SMTPSecure;                 // sets the prefix to the servier
    public $Host;                       // sets GMAIL as the SMTP server
    public $Port;                       // set the SMTP port for the GMAIL server
    public $Username;                   // GMAIL username
    public $Password;                   // GMAIL password
    public $Mailer;

    function __construct() {
        $config = configItem('mail');

        $this->SMTPDebug = $config['smtp_debug'];
        $this->SMTPAuth = $config['smtp_auth'];
        $this->SMTPSecure = $config['ssl'];
        $this->Host = $config['host'];
        $this->Port = $config['port'];
        $this->Username = $config['uname'];
        $this->Password = $config['pwd'];
        $this->Mailer = $config['mailer'];
    }
}