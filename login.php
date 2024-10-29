<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width,minimum-scale=1">
		<title>Login with Facebook</title>
		<link href="./public/Assets/style.css" rel="stylesheet" type="text/css">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"></head>
	
	</head>
	<body>

		<div class="content home">

			<h1>
                Login
            </h1>

            <p class="login-txt">Please login using the button below. You'll be redirected to login page.</p>
            <div class="px-4 py-5 my-5 text-center">
            <h1 class="display-5 fw-bold text-body-emphasis">Postagem Automatica</h1>
            <div class="col-lg-6 mx-auto">
            <p class="lead mb-4">Automatizador de postagens em redes sociais </p>
            <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                <form action="" method="post" enctype="multipart/form-data">
                    <button class="btn btn-primary" type="submit" name="facebook-post">Postar no Facebook</button>
                </form>
                <form action="" method="post" enctype="multipart/form-data">
                        <button class="btn btn-primary" type="submit" name="instagram-post">Postar no instagram</button>
                        <?php

                            include 'config.php';
                            
                            if(isset($_POST['instagram-post']))
                            {
                                header('location:'.IG_LOGIN_PAGE);
                                exit();
                            }
                            if(isset($_POST['facebook-post']))
                            {
                                header('location:'.FB_REDIRECT_URL);
                                exit();
                            }
                        ?>
                </form>
                </div>
            </div>
		</div>

	</body>
</html>