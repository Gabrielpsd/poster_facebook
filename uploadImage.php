<?php
session_start();

    include 'config.php';

    require 'vendor/autoload.php';
    use Aws\S3\S3Client;

    $awsRegion = 'us-east-2';
    $access_key_id = AWS_ID_KEY;
    $access_key_KEY = AWS_TOKEN_KEY;
    $BucketName = 'imagefacepost';

    $statusMsg = '';
    $status = 'danger';

    $_SESSION['post'] = false;

    $pageId = $_SESSION['pageData']['data'][0]['id'];

    if(!(empty($_POST['upload'])) && isset($_POST['upload']))
    {   
        print_r($_POST);
        
        /*print_r($_FILES); */
        $filename = basename($_FILES['uploadfile']['name']);
        $tempname = $_FILES['uploadfile']['tmp_name'];
        $filetype = pathinfo($filename,PATHINFO_EXTENSION);

        $allowTypes = array('pdf','jpeg','jpg','png','PDF','JPEG','JPG','PNG');

        $folder = './image/' . $filename;

        $title = $_POST['title'];
        $description = $_POST['description'];

        if(in_array($filetype,$allowTypes))
        {
            $s3 = new S3Client
            (
                [
                    'version' => 'latest',
                    'region' => $awsRegion,
                    'credentials' => [
                        'key' => $access_key_id,
                        'secret' => $access_key_KEY
                        ]
                        ]
                    );
                   
                try{
                    echo "<p> Iniciarei a requisição</p>";
                    $result = $s3->putObject(
                        ['Bucket' => $BucketName,
                        'Key'=> $filename,
                        'ACL' => 'public-read',
                        'SourceFile' => $tempname] 
                    );
                    
                $resultArray = $result->toArray();

                if(!empty($resultArray['ObjectURL']))
                {
                    $s3_fileLink = $resultArray['ObjectURL'];

                    $params = [
                        'message' => $description,
                        'url' => $s3_fileLink
                    ];
                    
                    if(!empty($_POST['dataTime']))
                    {   
                        echo "<h4> data </h4>";
                        echo "<p> --- </p>";
                        print_r($_POST);
                        echo "<p> --- </p>";
                        
                        print_r($_POST['dataTime']);
                        echo "<p> --- </p>";
                        echo "<p> --- </p>";
                        echo "<p> --- </p>";
                        
                        $postDate = date_create($_POST['dataTime'],new DateTimeZone('America/Cuiaba'));
                        $today = date_create('now',new DateTimeZone('America/Cuiaba'));
                        if($postDate >  $today)
                        {
                            $params['published'] = "false";
                            $params['scheduled_publish_time'] = strtotime($_POST['dataTime']);
                        }

                    }

                    echo "<h4> Parametros</h4>";
                    
                    print_r($params);
                    
                    /* Publishing the content */ 
                    $ch = curl_init();
                    print_r("https://graph.facebook.com/".GRAPH_VERSION."/".$_SESSION['pageID'].'/photos?access_token='.$_SESSION['pageToken']); 
                    curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/".GRAPH_VERSION."/".$_SESSION['pageID'].'/photos?access_token='.$_SESSION['pageToken']);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($params));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    
                    
                    echo "<h4> Agendamento </h4>";
                    print_r($response);

                    move_uploaded_file($tempname,$folder);

                    $db_host = DB_HOST;
                    $db_name = DB_NAME;
                    $db_user = DB_USER;
                    $db_pass = DB_PASS;

                    $db = mysqli_connect(DB_HOST,DB_USER,password:DB_PASS,database:DB_NAME);
                    $sql = "insert into posts (title,description,imageLink) values ('$title','$description','$s3_fileLink')";

                    mysqli_query($db,$sql);
                    
                    
                    curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/".GRAPH_VERSION."/".$_SESSION['ig_business_account'].'/media?access_token='.$_SESSION['pageToken']);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query([
                        'message' => $description,
                        'image_url' => $s3_fileLink
                    ]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = json_decode(curl_exec($ch));
                    curl_close($ch);
                    
                    echo "<h3> Instagram create container</h3>";
                    print_r($response);

                    curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/".GRAPH_VERSION."/".$_SESSION['ig_business_account'].'/media_publish?access_token='.$_SESSION['pageToken']);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query(['creation_id'=>$response->id]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = curl_exec($ch);
                    curl_close($ch);
                    echo "<h3> Instagram post container</h3>";
                    print_r($response);

                   /*  header('Location: profile.php?message=photo'); */
                    exit();
                }
                else
                {
                    $apiError = 'Upload Failed! s3 Object URL not found';
                }
            }catch(Aws\S3\Exception\S3Exception $e){
                $apiError = $e->getMessage();
            }

                if(empty($apiError))
                {
                    $status = 'sucess';
                    $statusMsg = '  File was uploaded successfully !';
                }
        }
        else
        {
            echo "<h3> Image not uploaded sucessfully !</h3>";
        } 

    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="public/Assets/style.css" type="text/css">
</head>
<body>
    <div class="title">
        <h1>Criar uma postagem </h1>
    </div>
    <div id="content" class="content">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="formFile" class="form-label">Deseja colocar uma Imagem ?</label>
                <input class="form-control" type="file" accept=".pdf,.jpeg,.jpg,.png" name="uploadfile">
            </div>

            <div class="mb-3">
                <label for="exampleFormControlInput1" class="form-label">Título da Publicação</label>
                <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Título" name="title" >
            </div>

            <div class="mb-3">
                <label for="exampleFormControlTextarea1" class="form-label">Coloque uma Descrição</label>
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" placeholder="Descrição ... " name="description"></textarea>
            </div>

            <div class="mb-3">
                <label for="exampleFormControlTextarea1" class="form-label">Deseja Agendar a postagem ?</label>
                <input type="date" name="dataTime" id="data" >
            </div>

            <div class="form-group">
                <button class="btn btn-primary" type="submit" name="upload" value="post">Publicar</button>
            </div>
        </form>
            
</body>
</html>