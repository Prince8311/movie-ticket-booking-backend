<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../../utils/headers.php";
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

    $inputData = json_decode(file_get_contents("php://input"), true);

    if (!empty($inputData)) {
        $userName = mysqli_real_escape_string($conn, $inputData['userName']);
        $userEmail = mysqli_real_escape_string($conn, $inputData['userEmail']);
        $userPhone = mysqli_real_escape_string($conn, $inputData['userPhone']);
        $theaterName = mysqli_real_escape_string($conn, $inputData['theaterName']);
        $movieName = mysqli_real_escape_string($conn, $inputData['movieName']);
        $language = mysqli_real_escape_string($conn, $inputData['language']);
        $format = mysqli_real_escape_string($conn, $inputData['format']);
        $day = mysqli_real_escape_string($conn, $inputData['day']);
        $startTime = mysqli_real_escape_string($conn, $inputData['startTime']);
        $startDate = mysqli_real_escape_string($conn, $inputData['startDate']);
        $screen = mysqli_real_escape_string($conn, $inputData['screen']);
        $screenId = mysqli_real_escape_string($conn, $inputData['screenId']);
        $section = mysqli_real_escape_string($conn, $inputData['section']);
        $seats = mysqli_real_escape_string($conn, $inputData['seats']);
        $ticketPrice = mysqli_real_escape_string($conn, $inputData['ticketPrice']);
        $baseConvenience = mysqli_real_escape_string($conn, $inputData['baseConvenience']);
        $gst = mysqli_real_escape_string($conn, $inputData['gst']);
        $theaterCommission = mysqli_real_escape_string($conn, $inputData['theaterCommission']);
        $cleanUserName  = preg_replace('/\s+/', '', $userName);
        $cleanMovieName = preg_replace('/\s+/', '', $movieName);

        $userChar  = isset($cleanUserName[1])  ? strtoupper($cleanUserName[1])  : 'x';
        $movieChar = isset($cleanMovieName[1]) ? strtoupper($cleanMovieName[1]) : 'x';

        $randomThree = random_int(100, 999); 
        $randomOne   = random_int(0, 9);

        $bookingId = 'TKB' . $randomThree . $userChar . $movieChar . $randomOne;

        $validTime = '';
        $validDate = '';

        $movieSql = "SELECT * FROM `theater_shows` WHERE `theater_name` = '$theaterName' AND `screen` = '$screen' AND `screen_id` = '$screenId' AND `movie_name` = '$movieName' AND `start_date` = '$startDate' AND `start_time` = '$startTime'";
        $movieResult = mysqli_query($conn, $movieSql);

        if ($movieResult && mysqli_num_rows($movieResult) === 1) {
            $movieData = mysqli_fetch_assoc($movieResult);
            $validDate = $movieData['end_date'];
            $validTime = $movieData['end_time'];
        } else {
            $validTime = '';
            $validDate = '';
            $data = [
                'status' => 500,
                'message' => 'Database error: ' . $error
            ];
            header("HTTP/1.0 500 Internal Server Error");
            echo json_encode($data);
            exit;
        }

        $currentDateTime = new DateTime();
        $currentDateTime->add(new DateInterval('PT30M'));
        $expiryDateTime = $currentDateTime->format('Y-m-d H:i:s');
        $production = false;

        // Payment Credentials
        $apiKey = $production ? 'dd3ac85a-750a-42d8-bca2-08b7afabee0c' : 'aa2fbb7d-de50-4e3e-b628-c5ee22468e47';
        $merchantId = $production ? 'M22EJS7CELBPU' : 'TICKETBAYUAT';
        $paymentURL = $production ? 'https://api.phonepe.com/apis/hermes/pg/v1/pay' : 'https://api-preprod.phonepe.com/apis/pg-sandbox/pg/v1/pay';

        $keyIndex = 1;
        $merchantTransactionId = "MT" . time() . rand(1000, 9999);
        $callbackURL = 'https://api.ticketbay.in/user/api/booking/payment/payment-response.php';

        $paymentData = array(
            'merchantId' => $merchantId,
            'merchantTransactionId' => $merchantTransactionId,
            'merchantUserId' => "MUIDSHETTY",
            'amount' => $amount * 100,
            'redirectUrl' => $callbackURL,
            'redirectMode' => "POST",
            'callbackUrl' => $callbackURL,
            'merchantOrderId' => "MOID" . uniqid(),
            'mobileNumber' => $userPhone,
            'message' => "Payment of ₹" . $amount,
            'email' => $userEmail,
            'shortName' => "Shetty Ticket Counter",
            'paymentInstrument' => array(
                'type' => 'PAY_PAGE',
            ),
        );

        $jsonencode = json_encode($paymentData);
        $payloadMain = base64_encode($jsonencode);
        $payload = $payloadMain . "/pg/v1/pay" . $apiKey;
        $sha256 = hash("sha256", $payload);
        $final_x_header = $sha256 . '###' . $keyIndex;
        $request = json_encode(array('request' => $payloadMain));

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $paymentURL,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $request,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "X-VERIFY: " . $final_x_header,
                "Accept: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            echo "CURL Error #:" . $err;
        } else {
            $res = json_decode($response);
            if (isset($res->success) && $res->success === true) {
                $payURL = $res->data->instrumentResponse->redirectInfo->url;
                $sql = "INSERT INTO `online_bookings`(`booking_id`, `username`, `theater_name`, `movie_name`, `language`, `format`, `day`, `start_date`,  `start_time`, `valid_date`, `valid_time`, `screen`, `screen_id`, `section`, `seats`, `ticket_price`, `base_convenience`, `gst`, `theater_commission`, `merchant_transaction_id`, `expires_at`) VALUES ('$bookingId','$userName','$theaterName','$movieName','$language','$format','$day','$startDate','$startTime','$validDate','$validTime','$screen','$screenId','$section','$seats','$ticketPrice','$baseConvenience','$gst','$theaterCommission','$merchantTransactionId','$expiryDateTime')";
                $result = mysqli_query($conn, $result);

                if ($result) {
                    $data = [
                        'status' => 200,
                        'success' => true,
                        'paymentUrl' => $payURL
                    ];
                    header("HTTP/1.0 200 OK");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'success' => false,
                        'message' => 'Database error: ' . $error
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 400,
                    'success' => false,
                    'code' => $res->code,
                    'message' => $res->message
                ];
                header("HTTP/1.0 400 Failed");
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
