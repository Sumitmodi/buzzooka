<?php

class Flash extends MY_Controller
{
    public function index()
    {
        if (!isset($_GET['url'])) {
            return;
        }
        $url = $_GET['url'];
        ob_start();
        ?>
        <html>
        <head>
            <title>Flash display test</title>
        </head>
        <body>
        <iframe src="<?php echo $url; ?>" frameborder="0" style="width:100%;min-height:640px;"></iframe>
        </body>
        </html>
        <?php
        $out = ob_get_contents();
        ob_end_clean();
        echo $out;
        exit;
    }
}