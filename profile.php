<?php
// Initialize the session - is required to check the login state.
    session_start();

    include 'config.php';

    // Check if the user is logged in, if not then redirect to login page
    if (!isset($_SESSION['facebook_loggedin'])) {
        header('Location: login.php');
        exit;
    } 

    $_SESSION['pageID'] = $_SESSION['pageData']['data'][0]['id'];
    $_SESSION['pageToken'] = $_SESSION['pageData']['data'][0]['access_token'];

   /*  print_r($_SESSION['ig_business_account']); */
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
        // Could not connect to the MySQL database! Check db credentials...
        exit('Failed to connect to database!');
    }

    $stmt = $pdo->prepare('select * from accounts where id = ? ');
    $stmt->execute([$_SESSION['facebook_id']]) ;
    $account = $stmt->fetch(PDO::FETCH_ASSOC);


    $facebook_loggedin = $_SESSION['facebook_loggedin'];
    $facebook_email = $account['email'];
    $facebook_name = $account['name'];
    $facebook_picture = $account['picture'];

?>

<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>Profile</title>
		<link href="./public/Assets/style.css" rel="stylesheet" type="text/css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head>
	<body>

    <nav class="navbar bg-body-tertiary">
        <div class="container">
            <img src="<?=$facebook_picture?>"  onerror="this.src='./public/assets/icons8-customer.gif';"width="40" height="30">
        </div>
        <div class="box">
            <a href="logout.php" class="logout-btn">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" height="1em" viewBox="0 0 512 512"><!--! Font Awesome Free 6.4.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license (Commercial License) Copyright 2023 Fonticons, Inc. --><path d="M377.9 105.9L500.7 228.7c7.2 7.2 11.3 17.1 11.3 27.3s-4.1 20.1-11.3 27.3L377.9 406.1c-6.4 6.4-15 9.9-24 9.9c-18.7 0-33.9-15.2-33.9-33.9l0-62.1-128 0c-17.7 0-32-14.3-32-32l0-64c0-17.7 14.3-32 32-32l128 0 0-62.1c0-18.7 15.2-33.9 33.9-33.9c9 0 17.6 3.6 24 9.9zM160 96L96 96c-17.7 0-32 14.3-32 32l0 256c0 17.7 14.3 32 32 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32l-64 0c-53 0-96-43-96-96L0 128C0 75 43 32 96 32l64 0c17.7 0 32 14.3 32 32s-14.3 32-32 32z"/></svg>
                </span>
                Logout
            </a>
        </div>
    </nav>
            <?php
                if(isset($_POST["upload-image"]))
                {
                    header('Location: uploadImage.php');
                    exit();
                }
                if(isset($_POST["upload-storie"]))
                {
                    header('Location: post_storie.php');
                    exit();
                }
                if(isset($_GET['message'])  && $_GET['message']=='photo')
                {
                    echo "<h3>Imagem postada com sucesso</h3>";
                }
                if(isset($_GET['message'])  && $_GET['message']=='storie')
                {
                    echo "<h3>Storie postado com sucesso</h3>";
                }
            ?>
        <div class="px-4 py-5 my-5 text-center">
            <h1 class="display-5 fw-bold text-body-emphasis">Postagem Automatica</h1>
            <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">Automatizador de postagens em redes sociais </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <form action="" method="post" enctype="multipart/form-data">
                    <button class="btn btn-primary" type="submit" name="upload-image">Deseja Postar uma foto ?</button>
                </form>
                <form action="" method="post" enctype="multipart/form-data">
                    <button class="btn btn-primary" type="submit" name="upload-storie">Deseja Postar um Storie ?</button>
                </form>
            </div>
        </div>
	</body>
    <script src="./public/assets/function.js"></script>
</html>