<?php
session_start();
require_once '../components/connect.php'; 
require_once '../vendor/autoload.php';

// Initialize Google Client
$client = new Google_Client();
$client->setClientId('502512356932-b08caquk2r3lsqtotrl5u82surgi84sq.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost/digest_web_blog_5/google_handlers/call_back.php');
$client->addScope('email');
$client->addScope('profile');

// Generate the Google login URL and redirect
$authUrl = $client->createAuthUrl();
header('Location: ' . filter_var($authUrl, FILTER_SANITIZE_URL));
exit;
?>