<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';
// =====================================================
// GET CENTRAL MAIL CONFIG
// =====================================================

$apiUrl = 'https://netcomindia.in/contact-form/mail-api.php';
$apiKey = 'netcom@123';

$ch = curl_init($apiUrl);

curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-API-Key: ' . $apiKey,
        'Accept: application/json'
    ],
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => true,
]);

$apiResponse = curl_exec($ch);

if ($apiResponse === false) {
    curl_close($ch);

    http_response_code(500);
    echo "Unable to connect to central mail server.";
    exit;
}

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

curl_close($ch);

if ($httpCode !== 200) {
    http_response_code(500);
    echo "Unable to load central mail configuration.";
    exit;
}

$mailConfig = json_decode($apiResponse, true);

if (
    !is_array($mailConfig) ||
    !isset($mailConfig['success']) ||
    $mailConfig['success'] !== true ||
    !isset($mailConfig['gmail_username']) ||
    !isset($mailConfig['gmail_app_password'])
) {
    http_response_code(500);
    echo "Invalid mail configuration received.";
    exit;
}

$gmailUsername = $mailConfig['gmail_username'];
$gmailAppPassword = $mailConfig['gmail_app_password'];
/*
|--------------------------------------------------------------------------
| TEMPORARY DIAGNOSTIC SWITCH  --  SET BACK TO false WHEN DONE
|--------------------------------------------------------------------------
| true  = on failure return HTTP 500 with the real PHPMailer error and the
|         full SMTP conversation, and write it all to the PHP error log.
| false = normal production behaviour (friendly message, HTTP 200).
*/
const DEBUG = true;

// Don't let PHP notices/warnings leak into the AJAX response body,
// otherwise the browser never sees a clean "sent".
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: text/plain; charset=utf-8');

// SMTP account that actually authenticates.
$SMTP_USER = $gmailUsername;

$SMTP_PASS = $gmailAppPassword;


// Gmail only lets you send "From" the authenticated account or a verified
// alias. If contact@balance-bliss.in is verified under "Send mail as" in that
// Gmail account, you can change $FROM_EMAIL back to it.
$FROM_EMAIL = $SMTP_USER;
$FROM_NAME  = 'Balance Bliss';

$TO = array('anivira1999@gmail.com', 'contact@balance-bliss.in');

$name    = isset($_POST['name'])    ? trim($_POST['name'])    : '';
$email   = isset($_POST['email'])   ? trim($_POST['email'])   : '';
$phone   = isset($_POST['phone'])   ? trim($_POST['phone'])   : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

$error = "";

if ($name === "") {
    $error .= "\nName is required";
} elseif (!preg_match('/^[a-zA-Z\s]*$/', $name)) {
    $error .= "\nEnter valid Name";
}

if ($email === "") {
    $error .= " \nEmail Id is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error .= " \nEnter valid Email Id";
}

if ($phone === "") {
    $error .= " \nPhone number is required";
}

if ($message === "") {
    $error .= " \nMessage is required";
}

if ($error !== "") {
    echo $error;
    exit;
}

$e = function ($v) {
    return htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
};

// Collected SMTP conversation, only populated when DEBUG is on.
$smtpLog = '';

$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $SMTP_USER;
    $mail->Password   = $SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->Timeout    = 20;
    $mail->CharSet    = 'UTF-8';

    if (DEBUG) {
        // Level 3 = connection + client + server chatter. PHPMailer only
        // prints the actual credentials at level 4, so this stays safe.
        $mail->SMTPDebug   = 3;
        $mail->Debugoutput = function ($str, $level) use (&$smtpLog) {
            $smtpLog .= rtrim($str, "\r\n") . "\n";
        };
    }

    $message_body  = "<html><body><table border=5px>";
    $message_body .= "<tr><td colspan='2' style='color : #C50B33; font-size : 20px; '><b>Request For Contact Form</b></td></tr>";
    $message_body .= "<tr><td>Contact's Full Name : </td><td>" . $e($name) . "</td></tr>";
    $message_body .= "<tr><td>Contact's Email : </td><td>" . $e($email) . "</td></tr>";
    $message_body .= "<tr><td>Contact's Number : </td><td>" . $e($phone) . "</td></tr>";
    $message_body .= "<tr><td>Contact's Message : </td><td>" . nl2br($e($message)) . "</td></tr>";
    $message_body .= "</table></body></html>";

    $mail->setFrom($FROM_EMAIL, $FROM_NAME);
    $mail->addReplyTo($email, $name); // replying goes to the visitor
    foreach ($TO as $to) {
        $mail->addAddress($to);
    }
    $mail->Subject = 'Balance Bliss - New Contact Request';
    $mail->isHTML(true);
    $mail->Body    = $message_body;

    try {
        if (DEBUG) {
            $smtpLog .= "\n===== ATTEMPT 1: smtp.gmail.com:587 STARTTLS =====\n";
        }
        $mail->send();
    } catch (Exception $smtp587) {
        // Some shared hosts block STARTTLS on port 587 but allow SSL on 465.
        error_log('Balance Bliss SMTP 587 failed: ' . $mail->ErrorInfo);
        $mail->smtpClose();
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;
        if (DEBUG) {
            $smtpLog .= "\n===== ATTEMPT 1 FAILED: " . $mail->ErrorInfo . "\n";
            $smtpLog .= "\n===== ATTEMPT 2: smtp.gmail.com:465 SMTPS =====\n";
        }
        $mail->send();
    }

    // Acknowledgement to the visitor. If this one fails the enquiry has
    // already been delivered, so don't report it as an error to the browser.
    try {
        $mail->clearAllRecipients();
        $mail->clearReplyTos();
        $mail->addAddress($email);
        $mail->Subject = 'Balance Bliss - Thank You';
        $mail->Body    = "<html><body><h2>Thank you for contacting Balance Bliss!</h2><p>We have received your message and will get back to you shortly.</p></body></html>";
        $mail->send();
    } catch (Exception $ack) {
        error_log('Balance Bliss ack mail failed: ' . $mail->ErrorInfo);
    }

    echo 'sent';
} catch (Exception $ex) {
    $detail = 'Balance Bliss contact mail failed: ' . $mail->ErrorInfo . ' | ' . $ex->getMessage();
    error_log($detail);

    if (DEBUG) {
        error_log("Balance Bliss SMTP transcript:\n" . $smtpLog);
        http_response_code(500);
        echo "SMTP ERROR: " . $ex->getMessage() . "\n";
        echo "ErrorInfo : " . $mail->ErrorInfo . "\n";
        echo "\n===== SMTP TRANSCRIPT =====\n" . $smtpLog;
        exit;
    }

    echo 'Sorry, we could not send your message right now. Please email us at contact@balance-bliss.in';
}
