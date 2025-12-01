<?php

require "../../utils/headers.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($requestMethod == 'POST') {
    require "../../_db-connect.php";
    global $conn;

    require '../../PHPMailer/Exception.php';
    require '../../PHPMailer/PHPMailer.php';
    require '../../PHPMailer/SMTP.php';

    $userEmail = $_SESSION['userEmail'] ?? '';
    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $otp = mysqli_real_escape_string($conn, $inputData['otp']);
        $isRegistration = isset($inputData['isRegistration']) ? (bool)$inputData['isRegistration'] : false;

        $sql = "SELECT * FROM `users` WHERE `email` = '$userEmail'";
        $result = mysqli_query($conn, $sql);
        $row = mysqli_fetch_assoc($result);
        $userName = $row['name'];
        $savedOtp = $row['mail_token'];

        if ($savedOtp === null) {
            $data = [
                'status' => 401,
                'message' => 'Authentication error',
                'userId' => $userId
            ];
            header("HTTP/1.0 401 Authentication error");
            echo json_encode($data);
            exit;
        }

        if ($savedOtp == $otp) {
            if ($isRegistration) {
                $status = 1;
                $updateSql = "UPDATE `users` SET `mail_token`= NULL, `status`='$status' WHERE `email`='$userEmail'";
                $updateResult = mysqli_query($conn, $updateSql);
                
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
                    $mail->setFrom('noreply@ticketbay.in', 'Registration Successful 📌📌📌');
                    $mail->addAddress("$userEmail", 'User');
                    $mail->Subject = 'User registration';
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
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Hello <b>' . $userName . ',</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Great news — your Ticket Bay account has been successfully created!</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">You can now sign in and explore seamless movie ticket booking, quick reservations, and exclusive updates. If you did not initiate this registration, please contact our support team immediately.</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thank you for choosing Ticket Bay!</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative;">
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
                        'message' => 'Registration successful.',
                    ];
                    header("HTTP/1.0 200 Ok");
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
                $updateSql = "UPDATE `users` SET `mail_token`= NULL WHERE `email`='$userEmail'";
                $updateResult = mysqli_query($conn, $updateSql);
                $data = [
                    'status' => 200,
                    'message' => 'verification successful.',
                ];
                header("HTTP/1.0 200 Ok");
                echo json_encode($data);
            }
        } else {
            $data = [
                'status' => 404,
                'message' => 'Wrong OTP',
            ];
            header("HTTP/1.0 404 Wrong OTP");
            echo json_encode($data);
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
