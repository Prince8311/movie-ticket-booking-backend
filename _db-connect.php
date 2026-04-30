<?php

$server = "localhost";
$username = "u181314319_abhay";
$password = 'Abhay$ticketbay@2024';
$database = "u181314319_movieTicket";

$conn = mysqli_connect($server, $username, $password, $database);

if (!$conn) {
    http_response_code(500);
    echo json_encode([
        "status" => 500,
        "message" => "Database connection failed",
        "error" => mysqli_connect_error()
    ]);
    exit;
}

// TEMP DEBUG (remove later)
echo json_encode([
    "status" => 200,
    "message" => "Database connected successfully"
]);
exit;
