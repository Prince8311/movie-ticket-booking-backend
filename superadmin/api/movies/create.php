<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";
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

    function escapeOrNull($conn, $value)
    {
        if (!isset($value) || $value === '') {
            return null;
        }
        return mysqli_real_escape_string($conn, $value);
    }

    function sqlValue($value)
    {
        return $value === null ? "NULL" : "'$value'";
    }

    if (isset($_POST['inputs']) && isset($_FILES['image'])) {
        $inputData = json_decode($_POST['inputs'], true);

        $nameRaw = $inputData['name'] ?? 'movie'; // raw name for file
        $name = escapeOrNull($conn, $nameRaw);
        $formats = escapeOrNull($conn, $inputData['formats'] ?? null);
        $languages = escapeOrNull($conn, $inputData['languages'] ?? null);
        $time = escapeOrNull($conn, $inputData['time'] ?? null);
        $ageCategory = escapeOrNull($conn, $inputData['ageCategory'] ?? null);
        $genres = escapeOrNull($conn, $inputData['genres'] ?? null);
        $trailer = escapeOrNull($conn, $inputData['trailer'] ?? null);
        $casts = escapeOrNull($conn, $inputData['casts'] ?? null);
        $crews = escapeOrNull($conn, $inputData['crews'] ?? null);
        $description = escapeOrNull($conn, $inputData['description'] ?? null);
        $releaseYear = escapeOrNull($conn, $inputData['releaseYear'] ?? null);
        $releaseDateRaw = trim($inputData['releaseDate'] ?? '');
        if ($releaseDateRaw === '') {
            $releaseDate = null;
        } else {
            $normalizedDate = str_replace(',', '', $releaseDateRaw);
            $releaseDateFormatted = date("d M, Y", strtotime($normalizedDate));
            $releaseDate = mysqli_real_escape_string($conn, $releaseDateFormatted);
        }

        $imageData = $_FILES['image'];
        $folder = "../../../posters/movies/";
        $timestamp = date('YmdHis');
        $safeNameForFile = preg_replace("/[^a-zA-Z0-9_\-]/", "_", $nameRaw);
        $imageName = $safeNameForFile . $timestamp . '.png';
        $imageDirectory = $folder . $imageName;
        $image = getimagesize($imageData['tmp_name']);

        if ($image !== false) {
            $save = move_uploaded_file($imageData['tmp_name'], $imageDirectory);
            if ($save) {
                $sql = "INSERT INTO `movies`(`name`, `poster_image`, `release_date`, `release_year`, `total_time`, `languages`, `formats`, `age_category`, `genres`, `casts`, `crews`, `trailer`, `description`) VALUES (" . sqlValue($name) . ",'$imageName'," . sqlValue($releaseDate) . "," . sqlValue($releaseYear) . "," . sqlValue($time) . "," . sqlValue($languages) . "," . sqlValue($formats) . "," . sqlValue($ageCategory) . "," . sqlValue($genres) . "," . sqlValue($casts) . "," . sqlValue($crews) . "," . sqlValue($trailer) . "," . sqlValue($description) . ")";
                $result = mysqli_query($conn, $sql);
                if ($result) {
                    $data = [
                        'status' => 200,
                        'message' => 'Movie uploaded successfully.'
                    ];
                    header("HTTP/1.0 200 Uploaded");
                    echo json_encode($data);
                } else {
                    $data = [
                        'status' => 500,
                        'message' => 'Database error: ' . mysqli_error($conn)
                    ];
                    header("HTTP/1.0 500 Internal Server Error");
                    echo json_encode($data);
                }
            } else {
                $data = [
                    'status' => 500,
                    'message' => 'Sorry, there was an error uploading your file.'
                ];
                header("HTTP/1.0 500 Internal Server Error");
                echo json_encode($data);
            }
        } else {
            $data = [
                'status' => 400,
                'message' => 'File is not an image.'
            ];
            header("HTTP/1.0 400 Bad Request");
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
