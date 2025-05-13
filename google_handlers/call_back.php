<?php
session_start();
require_once '../components/connect.php';
require_once '../vendor/autoload.php';

// Initialize Google Client
$client = new Google_Client();
$client->setClientId('502512356932-b08caquk2r3lsqtotrl5u82surgi84sq.apps.googleusercontent.com'); // Replace with your actual client ID
$client->setClientSecret('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'); // Replace with your actual client secret
$client->setRedirectUri('http://localhost/digest_web_blog_5/google_handlers/call_back.php');

try {
    // Exchange authorization code for access token
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);
    $client->setAccessToken($token);

    // Get user information
    $google_oauth = new Google_Service_Oauth2($client);
    $google_account_info = $google_oauth->userinfo->get();

    $email = $google_account_info->email;
    $name = $google_account_info->name;
    $google_id = $google_account_info->id;

    // Split name into firstname and lastname (simple split)
    $name_parts = explode(' ', $name, 2);
    $firstname = $name_parts[0];
    $lastname = isset($name_parts[1]) ? $name_parts[1] : '';

    // Check if user exists
    $check_user = $conn->prepare("SELECT * FROM `accounts` WHERE email = ? OR google_id = ?");
    $check_user->execute([$email, $google_id]);

    if($check_user->rowCount() > 0) {
        // User exists, log them in
        $row = $check_user->fetch(PDO::FETCH_ASSOC);
        $_SESSION['account_id'] = $row['account_id'];
        header('location: ../index.php');
        exit;
    } else {
        // Create new user
        $user_name = strtolower($firstname . '_' . $lastname) . rand(100, 999);
        $password = bin2hex(random_bytes(8)); // Random password

        $insert_user = $conn->prepare("INSERT INTO `accounts` (firstname, lastname, user_name, email, password, role, google_id) VALUES (?, ?, ?, ?, ?, 'user', ?)");
        $insert_user->execute([$firstname, $lastname, $user_name, $email, password_hash($password, PASSWORD_DEFAULT), $google_id]);

        $_SESSION['account_id'] = $conn->lastInsertId();
        header('location: ../index.php');
        exit;
    }

} catch (Exception $e) {
    // Error handling
    $_SESSION['message'] = 'Google authentication failed. Please try again.';
    header('location: ../user_content/register.php');
    exit;
}
?>