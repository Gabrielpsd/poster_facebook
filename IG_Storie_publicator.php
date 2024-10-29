<?php
session_start();

    include "config.php";

    require 'vendor/autoload.php';
    use Aws\S3\S3Client;

    
    print_r("Acess Token: ".$_SESSION['ig_acess_token']);
    print_r("Acess ID: ".$_SESSION['ig_user_id']);
    print_r("ID IG".$_SESSION['ig_id']);
    $allowTypes = array('pdf','jpeg','jpg','png','PDF','JPEG','JPG','PNG');

    if(isset($_POST['upload']) && !empty($_FILES ) && !(empty($_POST['upload'])))
    {   

        /*Amazon Bucket Operation Setup */
        $awsRegion = 'us-east-2';
        $access_key_id = AWS_ID_KEY;
        $access_key_KEY = AWS_TOKEN_KEY;
        $BucketName = 'imagefacepost';

        /* echo "<p>post</p>";
        print_r($_POST);
        echo "<p>Files</p>";
        print_r($_FILES); */
        $count = count($_FILES['uploadfile']['name']);
        if($count == 1)
        {
            /* print_r("Quantidade: ".$count); */

            $filename = basename($_FILES['uploadfile']['name'][0]);
            $tempname = $_FILES['uploadfile']['tmp_name'][0];
            $filetype = pathinfo($filename,PATHINFO_EXTENSION);

            $s3 = new S3Client
            (
                [
                    'version' => 'latest',
                    'region' => $awsRegion,
                    'credentials' => 
                    [
                        'key' => $access_key_id,
                        'secret' => $access_key_KEY
                    ]
                ]
            );

            try{

                $result = $s3->putObject(
                    [   'Bucket' => $BucketName,
                        'Key' => $filename,
                        'ACL' => 'public-read',
                        'SourceFile' => $tempname
                    ]
                ); 

                $resultArray = $result->toArray();
                
                //$resultArray['ObjectURL'] = 'https://imagefacepost.s3.us-east-2.amazonaws.com/IMG_368223.JPEG';

                if(!empty($resultArray['ObjectURL']))
                {

                    $Container = 0;

                    while($Container < 2)
                    {

                        $s3FileLink = $resultArray['ObjectURL'];
                        echo "<h3> Amazon Link</h3>";
                        print_r($s3FileLink);
                        echo "<h3> Json content </h3>";
                        $Json = json_encode(['image_url'=>$s3FileLink, "media_type" => "STORIES"]);
                        
                        $ch = curl_init();
                        //?acess_token=".$_SESSION['ig_acess_token'] $_SESSION['ig_user_id']'
                        curl_setopt($ch, CURLOPT_URL,"https://graph.instagram.com/".GRAPH_VERSION."/".$_SESSION['ig_user_id']."/media?access_token=".$_SESSION['ig_acess_token']);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $Json);
                        curl_setopt($ch, CURLOPT_VERBOSE, true);
                        curl_setopt($ch, CURLOPT_STDERR, fopen('curl.log', 'a+')); // 
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $response = json_decode(curl_exec($ch));
                        print_r('Media constructor: \n');
                        print_r($response);
                        curl_close($ch);

                        if(isset($response->id) && !empty($response->id))
                        {   
                            print_r($response->id);
                            print_r($response);
                            print_r(json_encode(['creation_id'=>$response->id]));
                            
                            $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL,"https://graph.instagram.com/".GRAPH_VERSION."/".$response->id."?fields=status_code&access_token=".$_SESSION['ig_acess_token']);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                            print_r('Media constructor Status');
                            print_r(curl_exec($ch));
                            curl_close($ch);

                            $published = false;
                            $tentativa = 0;
                            
                            while($published || ($tentativa < 2))
                            {
                                
                                $ch = curl_init();
                                curl_setopt($ch, CURLOPT_URL,"https://graph.instagram.com/".GRAPH_VERSION."/".$_SESSION['ig_user_id']."/media_publish?access_token=".$_SESSION['ig_acess_token']);
                                curl_setopt($ch, CURLOPT_POST, true);
                                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['creation_id'=>$response->id]));
                                curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
                                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                $response2 = json_decode(curl_exec($ch));
                                echo "<h3> Imprimindo informações da requisição </h3>";
                                print_r(curl_getinfo($ch));
                                curl_close($ch);
                                print_r($response2);
                                
                                if(isset($response2->id) && !empty($response2->id))
                                {
                                    $published = true;
                                    header('Location: profile_instagram.php?message=photo');
                                    exit();
                                }
                                
                                if($tentativa = 0)
                                {
                                    sleep(30);
                                    $tentativa = $tentativa + 1 ;
                                }
                                
                                if($tentativa = 1)
                                {
                                    sleep(60);
                                    $tentativa = $tentativa + 1 ;
                                }
                            }
                        }
                        else
                        {
                            echo "<h3> Um erro ocorreu ao tentar postar imagem (002)</h3>";
                        }

                        $Container = $Container + 1 ;
                    }

                    if($tentativa = 2)
                    {
                        echo "<h3> Várias tentativas foram feitas </h3>";
                    }
                }
            }catch(Aws\S3\Exception\S3Exception $e)
            {
                print_r($e->getMessage());
                exit();
            }


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
                <input class="form-control" multiple="multiple" type="file" accept=".jpeg,.mp4" name="uploadfile[]">
            </div>

            <div class="mb-3">
                <label for="exampleFormControlInput1" class="form-label">Título da Publicação</label>
                <input type="text" class="form-control" id="exampleFormControlInput1" placeholder="Título" name="title" >
            </div>

            <div class="mb-3">
                <label for="exampleFormControlTextarea1" class="form-label">Coloque uma Descrição</label>
                <textarea class="form-control" id="exampleFormControlTextarea1" rows="3" placeholder="Descrição ... " name="description"></textarea>
            </div>

            <div class="form-group">
                <button class="btn btn-primary" type="submit" name="upload" value="post">Publicar</button>
            </div>
        </form>
            
</body>
</html>