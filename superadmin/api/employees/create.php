<?php

session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Access-Control-Allow-Origin: http://localhost:3000');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Content-Type: application/json');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == 'OPTIONS') {
    header('HTTP/1.1 200 OK');
    exit();
}

require "../../../utils/middleware.php";

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
    require "../../../_db-connect.php";
    global $conn;

    require "../../../PHPMailer/Exception.php";
    require "../../../PHPMailer/PHPMailer.php";
    require "../../../PHPMailer/SMTP.php";

    $inputData = json_decode(file_get_contents("php://input"), true);
    if (!empty($inputData)) {
        $userType = 'employee';
        $empName = mysqli_real_escape_string($conn, $inputData['name']);
        $empPhone = mysqli_real_escape_string($conn, $inputData['phone']);
        $empMail = mysqli_real_escape_string($conn, $inputData['email']);
        $empRole = mysqli_real_escape_string($conn, $inputData['roleName']);
        $password = mysqli_real_escape_string($conn, $inputData['password']);
        $confirmPassword = mysqli_real_escape_string($conn, $inputData['confirmPassword']);
        $status = 1;

        if ($password == $confirmPassword) {
            $hashPass = password_hash($password, PASSWORD_DEFAULT);

            $checkNameSql = "SELECT * FROM `admin_users` WHERE `name` = '$empName'";
            $nameResult = mysqli_query($conn, $checkNameSql);
            if (mysqli_num_rows($nameResult) > 0) {
                $data = [
                    'status' => 400,
                    'message' => 'This name already exists.'
                ];
                header("HTTP/1.0 400 Already exists");
                echo json_encode($data);
                exit;
            }

            $checkEmailSql = "SELECT * FROM `admin_users` WHERE `email` = '$empMail'";
            $emailResult = mysqli_query($conn, $checkEmailSql);
            if (mysqli_num_rows($emailResult) > 0) {
                $data = [
                    'status' => 400,
                    'message' => 'This email is already registered.'
                ];
                header("HTTP/1.0 400 Already exists");
                echo json_encode($data);
                exit;
            }

            $checkPhoneSql = "SELECT * FROM `admin_users` WHERE `phone` = '$empPhone'";
            $phoneResult = mysqli_query($conn, $checkPhoneSql);
            if (mysqli_num_rows($phoneResult) > 0) {
                $data = [
                    'status' => 400,
                    'message' => 'This phone no. is already registered.'
                ];
                header("HTTP/1.0 400 Already exists");
                echo json_encode($data);
                exit;
            }

            $sql = "INSERT INTO `admin_users`(`name`, `email`, `phone`, `password`, `status`, `user_type`, `user_role`) VALUES ('$empName','$empMail','$empPhone','$hashPass','$status','$userType','$empRole')";
            $result = mysqli_query($conn, $sql);

            if ($result) {
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
                    $mail->setFrom('noreply@ticketbay.in', 'noreply@ticketbay.in');
                    $mail->addAddress("$empMail", 'Employee');
                    $mail->Subject = 'Account has been created.';
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
                                                                <span style="position: relative; font-family: sans-serif; color: #222; font-size: 15px; line-height: 1.4;">Dear <b>' . $empName . '</b></span>
                                                            </p> 
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Your account has been created as an employee of our company for the role of <b>' . $empRole . '</b>. You can signin now in <a href="superadmin.ticketbay.in" style="color: #FC6736;" >superadmin.ticketbay.in</a> with the credentials:</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 6px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">User ID: <b>' . $empMail . '</b></span>
                                                            </p>
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Password: <b>' . $password . '</b></span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 6px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Later you can change the password by self.</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 15px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">Thanks & Regards,</span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 2px;">
                                                            <p style="position: relative;">
                                                                <span style="position: relative; font-family: cursive; color: #fc6736; font-size: 15px; line-height: 1.4;"><b>Shetty Ticket Counter Pvt. Ltd.</b></span>
                                                            </p>
                                                        </div>
                                                        <div style="position: relative; margin-top: 20px;">
                                                            <p style="position: relative;"><b style="position: relative; font-family: sans-serif; font-size: 13px; color: #f00;">*NOTE:- Please do not share this message with anyone else.</b></p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </body>
                                        </html>';
                    $mail->send();
                    $data = [
                        'status' => 200,
                        'message' => 'Employee created successfully.'
                    ];
                    header("HTTP/1.0 200 OK");
                    echo json_encode($data);
                } catch (Exception $e) {
                    $data = [
                        'status' => 500,
                        'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}",
                    ];
                    header("HTTP/1.0 500 Message could not be sent");
                }
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Database error: ' . $error
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else {
            $data = [
                'status' => 400,
                'message' => 'Password mismatch'
            ];
            header("HTTP/1.0 400 Validation error");
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
