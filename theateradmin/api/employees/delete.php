<?php

require __DIR__ . "/../../../utils/headers.php";
if ($requestMethod == 'POST') {
} else {
    $data = [
        'status' => 405,
        'message' => $requestMethod . ' Method Not Allowed',
    ];
    header("HTTP/1.0 405 Method Not Allowed");
    echo json_encode($data);
}
