<?php

date_default_timezone_set('Asia/Kolkata');
require "../../../utils/headers.php";

if ($requestMethod == 'GET') {
    require "../../../_db-connect.php";
    global $conn;

    if (isset($_GET['location']) && isset($_GET['name'])) {
        $location = mysqli_real_escape_string($conn, $_GET['location']);
        $movieName = mysqli_real_escape_string($conn, $_GET['name']);

        // Date & Time 
        $currentDate = date("Y-m-d");
        $currentTime = date("H:i:s");

        // Theater list
        $theaterSql = "SELECT `name` FROM `registered_theaters` WHERE `city`='$location'";
        $theaterResult = mysqli_query($conn, $theaterSql);
        $theaters = [];
        while ($row = mysqli_fetch_assoc($theaterResult)) {
            $theaters[] = $row['name'];
        }
        if (empty($theaters)) {
            echo json_encode([
                'status' => 200,
                'message' => 'No theaters found for this location.',
                'totalCount' => 0,
                'currentPage' => $page,
                'movies' => []
            ]);
            exit;
        }
        $theaterList = "'" . implode("','", $theaters) . "'";

        $sql = "SELECT m.*, GROUP_CONCAT(DISTINCT ts.language) AS available_languages, GROUP_CONCAT(DISTINCT ts.format) AS available_formats FROM movies m LEFT JOIN theater_shows ts ON ts.movie_name = m.name AND ts.theater_name IN ($theaterList) AND (ts.start_date > '$currentDate' OR (ts.start_date = '$currentDate' AND ts.start_time > '$currentTime')) WHERE m.name = '$movieName' GROUP BY m.id";
        $result = mysqli_query($conn, $sql);

        if ($result && mysqli_num_rows($result) > 0) {
            $movie = mysqli_fetch_assoc($result);
            unset($movie['languages']);
            unset($movie['formats']);

            // ============================
            // CASTS
            // ============================
            $castNames = array_filter(array_map('trim', explode(',', $movie['casts'])));
            $casts = [];

            if (!empty($castNames)) {
                $castList = "'" . implode("','", $castNames) . "'";
                $castSql = "SELECT `name`, `profile_image` FROM `movie_casts_crews` WHERE `name` IN ($castList)";
                $castResult = mysqli_query($conn, $castSql);

                while ($row = mysqli_fetch_assoc($castResult)) {
                    $casts[] = [
                        'name' => $row['name'],
                        'profile_image' => $row['profile_image']
                    ];
                }
            }

            // ============================
            // CREWS
            // ============================
            $crewNames = array_filter(array_map('trim', explode(',', $movie['crews'])));
            $crews = [];

            if (!empty($crewNames)) {
                $crewList = "'" . implode("','", $crewNames) . "'";
                $crewSql = "SELECT `name`, `profile_image` FROM `movie_casts_crews` WHERE `name` IN ($crewList)";
                $crewResult = mysqli_query($conn, $crewSql);

                while ($row = mysqli_fetch_assoc($crewResult)) {
                    $crews[] = [
                        'name' => $row['name'],
                        'profile_image' => $row['profile_image']
                    ];
                }
            }

            // Replace casts & crews with structured arrays
            unset($movie['casts']);
            unset($movie['crews']);

            $movie['casts'] = $casts;
            $movie['crews'] = $crews;

            $data = [
                'status' => 200,
                'message' => 'Movie details fetched.',
                'movie' => $movie
            ];
            header("HTTP/1.0 200 Details fetched");
            echo json_encode($data);
        } else {
            $data = [
                'status' => 404,
                'message' => 'Movie not found or no valid showtimes.'
            ];
            header("HTTP/1.0 404 Not found");
            echo json_encode($data);
        }
    } else {
        $data = [
            'status' => 400,
            'message' => 'Location and name is required'
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
