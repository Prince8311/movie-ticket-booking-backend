<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require "../../../../../utils/headers.php";
require "../../../../utils/middleware.php";

$authResult = authenticateRequest();

if (!$authResult['authenticated']) {
    $data = [
        'status' => $authResult['status'],
        'message' => $authResult['message']
    ];
    header("HTTP/1.0 " . $authResult['status']);
    echo json_encode($data);
    exit;
}

if ($requestMethod == 'POST') {
    require "../../../../_db-connect.php";
    global $conn;

    require '../../../../PHPMailer/Exception.php';
    require '../../../../PHPMailer/PHPMailer.php';
    require '../../../../PHPMailer/SMTP.php';

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);
        $userEmail = mysqli_real_escape_string($conn, $inputData['userEmail']);
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $status = mysqli_real_escape_string($conn, $inputData['status']);
        if ($status === 'Completed') {
            $notSettedScreenSql = "SELECT * FROM `registered_screens` WHERE `theater_name`='$theaterName' AND `capacity` IS NULL";
            $notSettedResult = mysqli_query($conn, $notSettedScreenSql);
            if (mysqli_num_rows($notSettedResult) > 0) {
                $count = mysqli_num_rows($notSettedResult);
                $data = [
                    'status' => 400,
                    'message' => $count . ($count > 1 ? ' screens are not setted.' : ' screen is not setted.'),
                ];
                header("HTTP/1.0 400 Not setted");
                echo json_encode($data);
                return;
            }
            $amount = mysqli_real_escape_string($conn, $inputData['amount']);
            if ($amount !== '') {
                $updateSql = "UPDATE `registered_theaters` SET `status`='$status',`advance_payment`='$amount' WHERE `name`='$theaterName'";
                $updateResult = mysqli_query($conn, $updateSql);
                if ($updateResult) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'mail.ticketbay.in';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'noreply@ticketbay.in';
                        $mail->Password   = 'abhay$ticketbay@2024';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;
                        $mail->CharSet = 'UTF-8';

                        $mail->isHTML(true);
                        $mail->setFrom('noreply@ticketbay.in', 'Request Completed 📜📜📜');
                        $mail->addAddress("$userEmail", 'Admin');
                        $mail->Subject = 'Registration status';
                        $mail->Body    = '<!DOCTYPE html>
                                        <html lang="en">
                                            <head>
                                                <meta charset="UTF-8">
                                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
                                            </head>
                                            <body style="position: relative; margin: 0; padding: 0;">
                                                <div class="template_wrapper" style="position: relative; width: 100%;  padding: 10px; box-sizing: border-box; ">
                                                    <div class="template" style="position: relative; background: #FFF; padding-bottom: 50px; border-radius: 5px;" >
                                                        <div class="logo" style="position: relative; text-align: center;"><img src="https://ticketbay.in/Backend/Images/Logo.png" alt="Logo" style="width: 30px;"></div>
                                                        <div class="body_message" style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Dear <b>' . $userName . ',</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Your request for your theater <b>"' . $theaterName . '"</b> is completed and Rs. ' . $amount . ' is paid to you as an advance payment. Now, please login to your account <a href="theateradmin.ticketbay.in" style="color: #FC6736;" >theateradmin.ticketbay.in</a> here and publish your screen(s).</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 30px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: cursive; color: #fc6736; font-size: 18px; line-height: 1.4;"><b>Shetty Ticket Counter Pvt. Ltd.</b></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';
                        $mail->send();
                        $data = [
                            'status' => 200,
                            'message' => 'Status updated successfully.',
                        ];
                        header("HTTP/1.0 200 Updated");
                        echo json_encode($data);
                    } catch (Exception $e) {
                        $data = [
                            'status' => 500,
                            'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                        ];
                        header("HTTP/1.0 500 Message could not be sent");
                        echo json_encode($data);
                    }
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Internal Server Error',
                    ];
                    header("HTTP/1.0 500 Server Error");
                    echo json_encode($data);
                }
            } else {
                $updateSql = "UPDATE `registered_theaters` SET `status`='$status' WHERE `name`='$theaterName'";
                $updateResult = mysqli_query($conn, $updateSql);
                if ($updateResult) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'mail.ticketbay.in';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'noreply@ticketbay.in';
                        $mail->Password   = 'abhay$ticketbay@2024';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;
                        $mail->CharSet = 'UTF-8';

                        $mail->isHTML(true);
                        $mail->setFrom('noreply@ticketbay.in', 'Request Completed 📜📜📜');
                        $mail->addAddress("$userEmail", 'Admin');
                        $mail->Subject = 'Registration status';
                        $mail->Body    = '<!DOCTYPE html>
                                        <html lang="en">
                                            <head>
                                                <meta charset="UTF-8">
                                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
                                            </head>
                                            <body style="position: relative; margin: 0; padding: 0;">
                                                <div class="template_wrapper" style="position: relative; width: 100%;  padding: 10px; box-sizing: border-box; ">
                                                    <div class="template" style="position: relative; background: #FFF; padding-bottom: 50px; border-radius: 5px;" >
                                                        <div class="logo" style="position: relative; text-align: center;"><img src="https://ticketbay.in/Backend/Images/Logo.png" alt="Logo" style="width: 30px;"></div>
                                                        <div class="body_message" style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Dear <b>' . $userName . ',</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Your request for your theater <b>"' . $theaterName . '"</b> is completed. Now, please login to your account <a href="theateradmin.ticketbay.in" style="color: #FC6736;" >theateradmin.ticketbay.in</a> here and publish your screen(s).</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 30px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: cursive; color: #fc6736; font-size: 18px; line-height: 1.4;"><b>Shetty Ticket Counter Pvt. Ltd.</b></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';
                        $mail->send();
                        $data = [
                            'status' => 200,
                            'message' => 'Registration completed successfully.',
                        ];
                        header("HTTP/1.0 200 Updated");
                        echo json_encode($data);
                    } catch (Exception $e) {
                        $data = [
                            'status' => 500,
                            'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                        ];
                        header("HTTP/1.0 500 Message could not be sent");
                        echo json_encode($data);
                    }
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Internal Server Error',
                    ];
                    header("HTTP/1.0 500 Server Error");
                    echo json_encode($data);
                }
            }
        } else if ($status === 'Rejected') {
            $reason = mysqli_real_escape_string($conn, $inputData['reason']);
            if ($reason !== '') {
                $updateSql = "UPDATE `registered_theaters` SET `status`='$status',`reject_reason`='$reason' WHERE `name`='$theaterName'";
                $updateResult = mysqli_query($conn, $updateSql);
                if ($updateResult) {
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'mail.ticketbay.in';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'noreply@ticketbay.in';
                        $mail->Password   = 'abhay$ticketbay@2024';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port       = 465;
                        $mail->CharSet = 'UTF-8';

                        $mail->isHTML(true);
                        $mail->setFrom('noreply@ticketbay.in', 'Request Cancelled 🔴🔴🔴');
                        $mail->addAddress("$userEmail", 'Admin');
                        $mail->Subject = 'Registration status';
                        $mail->Body    = '<!DOCTYPE html>
                                        <html lang="en">
                                            <head>
                                                <meta charset="UTF-8">
                                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
                                            </head>
                                            <body style="position: relative; margin: 0; padding: 0;">
                                                <div class="template_wrapper" style="position: relative; width: 100%;  padding: 10px; box-sizing: border-box; ">
                                                    <div class="template" style="position: relative; background: #FFF; padding-bottom: 50px; border-radius: 5px;" >
                                                        <div class="logo" style="position: relative; text-align: center;"><img src="https://ticketbay.in/Backend/Images/Logo.png" alt="Logo" style="width: 30px;"></div>
                                                        <div class="body_message" style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Dear <b>' . $userName . ',</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Your request for your theater <b>"' . $theaterName . '"</b> has been rejected by admin. Because, "' . $reason . '". Now, please login to your account <a href="theateradmin.ticketbay.in" style="color: #FC6736;" >theateradmin.ticketbay.in</a> here and resubmit the form.</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 30px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: cursive; color: #fc6736; font-size: 18px; line-height: 1.4;"><b>Shetty Ticket Counter Pvt. Ltd.</b></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';
                        $mail->send();
                        $data = [
                            'status' => 200,
                            'message' => 'Status updated successfully.',
                        ];
                        header("HTTP/1.0 200 Updated");
                        echo json_encode($data);
                    } catch (Exception $e) {
                        $data = [
                            'status' => 500,
                            'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                        ];
                        header("HTTP/1.0 500 Message could not be sent");
                        echo json_encode($data);
                    }
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Internal Server Error',
                    ];
                    header("HTTP/1.0 500 Server Error");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 400,
                    'message' => 'Please provide the reason.'
                ];
                header("HTTP/1.0 400 Bad Request");
                echo json_encode($data);
            }
        } else if ($status === 'Confirmed') {
            $updateSql = "UPDATE `registered_theaters` SET `status`='$status' WHERE `name`='$theaterName'";
            $updateResult = mysqli_query($conn, $updateSql);
            if ($updateResult) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'mail.ticketbay.in';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'noreply@ticketbay.in';
                    $mail->Password   = 'abhay$ticketbay@2024';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet = 'UTF-8';

                    $mail->isHTML(true);
                    $mail->setFrom('noreply@ticketbay.in', 'Request Confirmed 📌📌📌');
                    $mail->addAddress("$userEmail", 'Admin');
                    $mail->Subject = 'Registration status';
                    $mail->Body    = '<!DOCTYPE html>
                                        <html lang="en">
                                            <head>
                                                <meta charset="UTF-8">
                                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
                                            </head>
                                            <body style="position: relative; margin: 0; padding: 0;">
                                                <div class="template_wrapper" style="position: relative; width: 100%;  padding: 10px; box-sizing: border-box; ">
                                                    <div class="template" style="position: relative; background: #FFF; padding-bottom: 50px; border-radius: 5px;" >
                                                        <div class="logo" style="position: relative; text-align: center;"><img src="https://ticketbay.in/Backend/Images/Logo.png" alt="Logo" style="width: 30px;"></div>
                                                        <div class="body_message" style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Dear <b>' . $userName . ',</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Your request for your theater <b>"' . $theaterName . '"</b> has been confirmed by admin. Also you can check <a href="theateradmin.ticketbay.in" style="color: #FC6736;" >theateradmin.ticketbay.in</a> here.</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 30px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: cursive; color: #fc6736; font-size: 18px; line-height: 1.4;"><b>Shetty Ticket Counter Pvt. Ltd.</b></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';
                    $mail->send();
                    $data = [
                        'status' => 200,
                        'message' => 'Status updated successfully.',
                    ];
                    header("HTTP/1.0 200 Updated");
                    echo json_encode($data);
                } catch (Exception $e) {
                    $data = [
                        'status' => 500,
                        'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                    ];
                    header("HTTP/1.0 500 Message could not be sent");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Internal Server Error',
                ];
                header("HTTP/1.0 500 Server Error");
                echo json_encode($data);
            }
        } else if ($status === 'Processing') {
            $updateSql = "UPDATE `registered_theaters` SET `status`='$status' WHERE `name`='$theaterName'";
            $updateResult = mysqli_query($conn, $updateSql);
            if ($updateResult) {
                $mail = new PHPMailer(true);
                try {
                    $mail->isSMTP();
                    $mail->Host       = 'mail.ticketbay.in';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = 'noreply@ticketbay.in';
                    $mail->Password   = 'abhay$ticketbay@2024';
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet = 'UTF-8';

                    $mail->isHTML(true);
                    $mail->setFrom('noreply@ticketbay.in', 'Request Under Process 📋📋📋');
                    $mail->addAddress("$userEmail", 'Admin');
                    $mail->Subject = 'Registration status';
                    $mail->Body    = '<!DOCTYPE html>
                                        <html lang="en">
                                            <head>
                                                <meta charset="UTF-8">
                                                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                                                <link href="https://fonts.googleapis.com/css2?family=Ubuntu:ital,wght@0,300;0,400;0,500;0,700;1,300;1,400;1,500;1,700&display=swap" rel="stylesheet">
                                            </head>
                                            <body style="position: relative; margin: 0; padding: 0;">
                                                <div class="template_wrapper" style="position: relative; width: 100%;  padding: 10px; box-sizing: border-box; ">
                                                    <div class="template" style="position: relative; background: #FFF; padding-bottom: 50px; border-radius: 5px;" >
                                                        <div class="logo" style="position: relative; text-align: center;"><img src="https://ticketbay.in/Backend/Images/Logo.png" alt="Logo" style="width: 30px;"></div>
                                                        <div class="body_message" style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Dear <b>' . $userName . ',</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Your request for your theater <b>"' . $theaterName . '"</b> is under process. Please, wait we will notify you when completed, also you can check <a href="theateradmin.ticketbay.in" style="color: #FC6736;" >theateradmin.ticketbay.in</a> here.</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 30px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: cursive; color: #fc6736; font-size: 18px; line-height: 1.4;"><b>Shetty Ticket Counter Pvt. Ltd.</b></span>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';
                    $mail->send();
                    $data = [
                        'status' => 200,
                        'message' => 'Status updated successfully.',
                    ];
                    header("HTTP/1.0 200 Updated");
                    echo json_encode($data);
                } catch (Exception $e) {
                    $data = [
                        'status' => 500,
                        'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                    ];
                    header("HTTP/1.0 500 Message could not be sent");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Internal Server Error',
                ];
                header("HTTP/1.0 500 Server Error");
                echo json_encode($data);
            }
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Empty request data'
        ];
        header("HTTP/1.0 400 Bad Request");
        echo json_encode($data);
    }
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
