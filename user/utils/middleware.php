<?php

require_once __DIR__ . '/../../utils/auth-helper.php';
require_once __DIR__ . '/../../_db-connect.php';

function authenticateRequest()
{
    global $conn;
    $cookieToken = $_COOKIE['authToken'] ?? '';

    // 1. Check cookie token
    if (empty($cookieToken)) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Authentication error'
        ];
    }

    // 2. Validate frontend header token
    $authHeader = getAuthorizationHeader();
    if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $frontendToken = $matches[1];
        if ($cookieToken !== $frontendToken) {
            return [
                'authenticated' => false,
                'status' => 401,
                'message' => 'Authentication mismatch'
            ];
        }
    }

    // ---------------------------
    // 3. Check token in database
    // ---------------------------
    $escapedToken = mysqli_real_escape_string($conn, $cookieToken);
    $userSql = "SELECT * FROM `users` WHERE `auth_token`='$escapedToken'";
    $userResult = mysqli_query($conn, $userSql);

    if (mysqli_num_rows($userResult) === 0) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Invalid token'
        ];
    }

    $tokenRow = mysqli_fetch_assoc($userResult);
    $userId = $tokenRow['id'];
    $expiryTime = strtotime($tokenRow['expires_at']);
    $currentTime = time();

    // ------------------------------------------------
    // 4. If token is still valid → return success
    // ------------------------------------------------
    if ($currentTime < $expiryTime) {
        return [
            'authenticated' => true,
            'token' => $cookieToken,
            'refreshed' => false,
            'userId' => $userId
        ];
    }

    // ------------------------------------------------
    // 5. TOKEN EXPIRED → Extract user data and refresh
    // ------------------------------------------------

    // Decode: base64 → "json | salt"
    $decoded = base64_decode($cookieToken);
    list($jsonPayload, $salt) = explode('|', $decoded, 2);

    $payload = json_decode($jsonPayload, true);

    if (!$payload || !isset($payload['id'])) {
        return [
            'authenticated' => false,
            'status' => 401,
            'message' => 'Expired token corrupted'
        ];
    }

    $newRandom = bin2hex(random_bytes(64));
    $newData   = json_encode($payload) . "|" . $newRandom;
    $newToken  = base64_encode($newData);

    // 7. Update DB token
    $newExpiry = date("Y-m-d H:i:s", time() + 21600);
    $updateSql = "UPDATE `users` SET `auth_token`='$newToken',`expires_at`='$newExpiry' WHERE `id`='$userId'";
    mysqli_query($conn, $updateSql);

    setcookie(
        "authToken",
        $newToken,
        [
            'expires' => time() + 21600,
            'path' => '/',
            'domain' => 'ticketbay.in',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]
    );

    return [
        'authenticated' => true,
        'token' => $newToken,
        'refreshed' => true,
        'userId' => $userId
    ];
}
