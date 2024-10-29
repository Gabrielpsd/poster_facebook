<?php

// Initialize the session
session_start();

include 'config.php';

// Database connection variables
$db_host = 'localhost';
$db_name = 'sociallogin';
$db_user = 'root';
$db_pass = '';


// If the captured code param exists and is valid
if (isset($_GET['code']) && !empty($_GET['code'])) {

    echo "<h1> Requisitando Token</h1>";

    $params = [
        'client_id' => IG_APP_USER,
        'client_secret' => IG_APP_PASS,
        'redirect_uri' => IG_REDIRECT_URL,
        'grant_type'=> 'authorization_code',
        'code' => $_GET['code']
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://api.instagram.com/oauth/access_token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, fopen('curl.log', 'a+')); // 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = json_decode(curl_exec($ch));
    
    curl_close($ch);

    print_r($response);

    if(isset($response->access_token) && !empty($response->access_token))
    {

        $parameters = [
            'fields' => 'user_id,username',
            'access_token' => $response->access_token
        ];

        $_SESSION['ig_acess_token'] = $response->access_token;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,"https://graph.instagram.com/".GRAPH_VERSION."/me?".http_build_query($parameters));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($ch));

        $_SESSION['ig_user_id'] = $response->user_id;
        $_SESSION['ig_id'] = $response->id;
        
        header('Location: profile_instagram.php');
        exit();
    }
} else {
   header('Location: '.IG_LOGIN_URL);
    exit;
}

?>