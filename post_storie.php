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

        $filename = basename($_FILES['uploadfile']['name']);
        $tempname = $_FILES['uploadfile']['tmp_name'];
        $filetype = pathinfo($filename,PATHINFO_EXTENSION);
        $allowTypes = array('pdf','jpeg','jpg','png','PDF','JPEG','JPG','PNG','MP4', 'mp4');

        if(!in_array($filetype,$allowTypes))
        {
            echo "<h3> Formato do arquivo não suportado </h3>";
        }

        if(in_array($filetype,['0'=> 'mp4','1'=>'MP4']))
        {
            /* POST A VIDEO */ 
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/".GRAPH_VERSION."/".$_SESSION['pageID'].'/video_stories?access_token='.$_SESSION['pageToken']);
            curl_setopt($ch, CURLOPT_POST, true);
            print_r(json_encode(['upload_phase'=> 'start']));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode(['upload_phase' => "start"]));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = json_decode(curl_exec($ch));
            curl_close($ch);
            $videoData = $response;

            if(isset($response->upload_url))
            {
                $fileSize = $_FILES['uploadfile']['size'];
                $filePath = './public/Assets/'.$filename;
                $acessToken = $_SESSION['acessToken'];
                   
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
                    $pageToken = $_SESSION['acessToken'];

                    $url = $response->upload_url.'?access_token='.$_SESSION['pageToken'];
                    print($url);
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ["file_url: $s3_fileLink" /*, Authorization OAuth $pageToken"*/]);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = json_decode(curl_exec($ch));
                    curl_close($ch); 

                    if($response->success = true)
                    {   
                        $url = "https://graph.facebook.com/".GRAPH_VERSION."/".$pageId."/video_stories?access_token=".$_SESSION['pageToken'];
                        $ch = curl_init();
                        curl_setopt($ch, CURLOPT_URL,$url);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                        curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode(["video_id" => $videoData->video_id,"upload_phase"=>"finish"]));
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        $response = json_decode(curl_exec($ch));
                        curl_close($ch); 

                        if(isset($response->post_id))
                        {
                            header('Location: profile.php?message=storie');
                            exit();
                        }

                    }
                }
            }
        }else{
   
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

                $ch = curl_init();
               curl_setopt($ch, CURLOPT_URL,"https://graph.facebook.com/".GRAPH_VERSION."/".$_SESSION['pageID'].'/photos?access_token='.$_SESSION['pageToken']);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode(['url' => $s3_fileLink, 'published' => "false"]));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $response = json_decode(curl_exec($ch));
                curl_close($ch);

                if(isset($response->id))
                {
                    $url = "https://graph.facebook.com/".GRAPH_VERSION."/".$pageId."/photo_stories?access_token=".$_SESSION['pageToken'];
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL,$url);
                    curl_setopt($ch, CURLOPT_POST, true);
                    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                    curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode(["photo_id" => $response->id]));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $response = json_decode(curl_exec($ch));
                    curl_close($ch); 
                    print_r($response);
                    
                    header('Location: profile.php?message=storie');
                    exit();
                }
                
            }
            else
            {
                $apiError = 'Upload Failed! s3 Object URL not found';
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
        <h1>Postar um Storie </h1>
    </div>
    <div id="content" class="content">
        <form method="POST" action="" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="formFile" class="form-label">Deseja colocar uma Imagem ?</label>
                <input class="form-control" type="file" accept=".pdf,.jpeg,.jpg,.png,.mp4" name="uploadfile">
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
    </div>
    </div>
            
</body>
</html>