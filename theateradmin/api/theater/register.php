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

    require '../../../PHPMailer/Exception.php';
    require '../../../PHPMailer/PHPMailer.php';
    require '../../../PHPMailer/SMTP.php';

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $userId = mysqli_real_escape_string($conn, $inputData['userId']);
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);
        $userEmail = mysqli_real_escape_string($conn, $inputData['userEmail']);
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $gstNo = mysqli_real_escape_string($conn, $inputData['gstNo']);
        $screenNo = mysqli_real_escape_string($conn, $inputData['screenNo']);
        $state = mysqli_real_escape_string($conn, $inputData['state']);
        $city = mysqli_real_escape_string($conn, $inputData['city']);
        $location = mysqli_real_escape_string($conn, $inputData['location']);

        $findTheaterSql = "SELECT * FROM `registered_theaters` WHERE `name`='$theaterName'";
        $findTheaterResult = mysqli_query($conn, $findTheaterSql);

        if (mysqli_num_rows($findTheaterResult) > 0) {
            $data = [
                'status' => 400,
                'message' => 'Theater already registered',
            ];
            header("HTTP/1.0 400 Already registered");
            echo json_encode($data);
            exit;
        }

        $theaterSql = "INSERT INTO `registered_theaters`(`name`, `gst_no`, `screen_no`, `state`, `city`, `location`) VALUES ('$theaterName','$gstNo','$screenNo','$state','$city','$location')";
        $theaterResult = mysqli_query($conn, $theaterSql);

        if ($theaterResult) {
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
                $mail->addAddress("$userEmail", 'Admin');
                $mail->Subject = 'Thearter registration';
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
                                                                <span style="position: relative; font-family: sans-serif; color: #444; font-size: 15px; line-height: 1.4;">You have successfully registered your theater <b>"' . $theaterName . '"</b>. Now, please wait for admin reponse. You will be notified via email or you can check the status <a href="theateradmin.ticketbay.in" style="color: #FC6736;" >theateradmin.ticketbay.in</a> here.</span>
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
                $updateUserSql = "UPDATE `theater_users` SET `theater_name`='$theaterName' WHERE `id`='$userId'";
                $updateResult = mysqli_query($conn, $updateUserSql);

                if($updateResult) {
                    $data = [
                        'status' => 200,
                        'message' => 'Theater registration successful.',
                    ];
                    header("HTTP/1.0 200 Registered");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Internal Server Error',
                    ];
                    header("HTTP/1.0 500 Server Error");
                    echo json_encode($data);
                }
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
