<?php
$scheme = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ||
           $_SERVER['SERVER_PORT']==443 ||
		   (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO']=='https')
		  ) ? 'https':'http';
$host = $_SERVER['HTTP_HOST'];
$self = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$self = preg_replace("/index.php$/","",$self);
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Resumeable uploads</title>

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400" rel="stylesheet">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <link href="https://transloadit.edgly.net/releases/uppy/v0.23.2/dist/uppy.min.css" rel="stylesheet">

    <script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    <script type="text/javascript" src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <style>
        body {
            background: #eee;
            letter-spacing: 0.5px;
            line-height: 1.5em;
            font-family: Lato, Helvetica Neue, Helvetica, Arial, sans-serif;
            margin-top: 40px;
        }

        .container {
            position: relative;
            background: #fff;
            margin: 0 auto;
            font-weight: 300;
            font-size: 1.1em;
            border-top: 5px solid #70B7FD;
        }

        h1 {
            font-size: 2em;
            line-height: 1.3em;
        }

        .project-info {
            text-align: center;
            padding: 5px;
            margin-top: 10px;
        }

        a.github-corner svg {
            z-index: 9999;
        }

        .uppy-Dashboard-inner {
            width: 100%;
            margin-bottom: 15px;
        }

        .github-corner:hover .octo-arm {
            animation: octocat-wave 560ms ease-in-out
        }

        @keyframes octocat-wave {
            0%, 100% {
                transform: rotate(0)
            }
            20%, 60% {
                transform: rotate(-25deg)
            }
            40%, 80% {
                transform: rotate(10deg)
            }
        }

        @media (max-width: 500px) {
            .github-corner:hover .octo-arm {
                animation: none
            }

            .github-corner .octo-arm {
                animation: octocat-wave 560ms ease-in-out
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="row">

        <div class="col-md-12">
            <h1>Resumable File Upload</h1><br/>

            <div class="panel-body">
                <div id="uppyUploader"></div>
            </div>
        </div>

    </div>
</div>

<script src="https://transloadit.edgly.net/releases/uppy/v0.23.2/dist/uppy.min.js"></script>
<script>
  const uppy = Uppy.Core({debug: true, autoProceed: false})
    .use(Uppy.Dashboard, {target: '#uppyUploader', inline: true})
    .use(Uppy.Tus, {
<?php
	echo "endpoint: '".$scheme."://".$host.$self."files/',";
	echo "chunkSize: ".(5*1024*1024);
?>
	})
    .run()

  uppy.on('upload-success', (file, response) => {
    uppy.setFileState(file.id, { uploadURL: `${response.url}/get` })
  })
</script>
</body>
</html>
