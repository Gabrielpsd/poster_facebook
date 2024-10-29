<?php

// Initialize the session
session_start();

include 'config.php';

// Update the following variables
$facebook_oauth_app_id = FB_USER;
$facebook_oauth_app_secret = FB_PASS;
// Must be the direct URL to the facebook-oauth.php file
$facebook_oauth_redirect_uri = FB_REDIRECT_URL;
$facebook_oauth_version = GRAPH_VERSION;

print_r($facebook_oauth_app_id);
// Database connection variables
$db_host = DB_HOST;
$db_name = DB_NAME;
$db_user = DB_USER;
$db_pass = DB_PASS;

 // Attempt to connect to database
 try {
    // Connect to the MySQL database using PDO...
    $pdo = new PDO('mysql:host=' . $db_host . ';dbname=' . $db_name . ';charset=utf8', $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $exception) {
    // Could not connect to the MySQL database, if this error occurs make sure you check your db settings are correct!
    print_r($exception);
    exit('Failed to connect to database!');
}

// If the captured code param exists and is valid
if (isset($_GET['code']) && !empty($_GET['code'])) {
    // Execute cURL request to retrieve the access token
    echo $_GET['code'];
    
    $params = [
        'client_id' => $facebook_oauth_app_id,
        'client_secret' => $facebook_oauth_app_secret,
        'redirect_uri' => $facebook_oauth_redirect_uri,
        'code' => $_GET['code']
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/oauth/access_token');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $response = json_decode($response, true);

    // Make sure access token is valid
    if (isset($response['access_token']) && !empty($response['access_token'])) {
        // Execute cURL request to retrieve the user info associated with the Facebook account
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/' . $facebook_oauth_version . '/me/accounts?fields=instagram_business_account');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        print_r(['Authorization: Bearer ' . $response['access_token']]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        curl_close($ch);
        $profile = json_decode(curl_exec($ch), true);

/*         print_r($profile); */

        $_SESSION['ig_business_account'] = $profile['data'][0]['instagram_business_account']['id'];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://graph.facebook.com/' . $facebook_oauth_version . '/me?fields=name,email,picture,accounts');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        print_r(['Authorization: Bearer ' . $response['access_token']]);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $response['access_token']]);
        curl_close($ch);
        $profile = json_decode(curl_exec($ch), true);
        /*  print_r($profile); */
        // Make sure the profile data exists
        if (isset($profile['email'])) {

            $stmt = $pdo->prepare('select * from accounts where email = ?');
            $stmt->execute([$profile['email']]);
            $account = $stmt->fetch(PDO::FETCH_ASSOC);

            if(!$account)
            {
                $stmt = $pdo->prepare('insert into accounts (email,name,picture,registered,method) values (?,?,?,?,?)');
                $stmt->execute([ $profile['email'], $profile['name'], $profile['picture']['data']['url'], date('Y-m-d H:i:s'), 'facebook' ]);
                $id = $pdo->lastInsertId();
            }
            else
            {
                $id = $account['id'];
            }
            
            $_SESSION['facebook_loggedin'] = TRUE;
            $_SESSION['facebook_id'] = $id;
            $_SESSION['facebook_email'] = $profile['email'];
            $_SESSION['facebook_name'] = $profile['name'];
            $_SESSION['facebook_picture'] = $profile['picture']['data']['url'];
            $_SESSION['acessToken'] = $response['access_token'];
            $_SESSION['pageData'] = $profile['accounts'];
            header('Location: profile.php');
            exit();
        } else {
            exit('Could not retrieve profile information! Please try again later!');
        }
    } else {
        exit('Invalid access token! Please try again later!');
    }
} else {
    // Define params and redirect to Facebook OAuth page
    $params = [
        'client_id' => $facebook_oauth_app_id,
        'redirect_uri' => $facebook_oauth_redirect_uri,
        'response_type' => 'code',
        'scope' => 'email'.','.
        'instagram_content_publish'.','
        .'instagram_basic'.','
        .'business_management'.','
        .'pages_show_list'.','
        .'pages_manage_engagement'.','
        .'pages_read_engagement'.','
        .'pages_manage_posts'
];
    header('Location: https://www.facebook.com/dialog/oauth?' . http_build_query($params));
    exit();
}

?>