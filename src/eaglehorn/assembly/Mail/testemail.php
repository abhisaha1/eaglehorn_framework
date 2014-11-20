<?php
/**
 * Simple example script using PHPMailer with exceptions enabled
 *
 * @package phpmailer
 * @version $Id$
 */

require 'class.phpmailer.php';

try {
    $mail = new PHPMailer(true); //New instance, with exceptions enabled

    $body = "Test Message";
    $mail->IsSendmail();  // tell the class to use Sendmail
    $mail->From = "info@squashchampions.net";
    $mail->FromName = "Squash Champions";
    $to = "ksreeejith@gmail.com";
    $mail->AddAddress($to);
    $mail->Subject = "First PHPMailer Message";
    $mail->WordWrap = 80; // set word wrap
    $mail->MsgHTML($body);
    $mail->IsHTML(true); // send as HTML
    $mail->Send();
    echo 'Message has been sent.';
} catch (phpmailerException $e) {
    echo $e->errorMessage();
}
?>