<?php
namespace eaglehorn\assembly\Mail;
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
 * @desc  Responsible for handling sessions with database support
 *
 */
require("class.phpmailer.php");

class Mail extends PHPMailer
{
    // Set default variables for all new objects
    public $SMTPDebug = SMTP_DEBUG;
    public $SMTPAuth = SMTP_AUTH;                  // enable SMTP authentication
    public $SMTPSecure = SMTP_SECURE;                 // sets the prefix to the servier
    public $Host = MAIL_HOST;      // sets GMAIL as the SMTP server
    public $Port = MAIL_PORT;                   // set the SMTP port for the GMAIL server
    public $Username = MAIL_UNAME;  // GMAIL username
    public $Password = MAIL_PWD;            // GMAIL password
    public $Mailer = MAILER;

}

?>