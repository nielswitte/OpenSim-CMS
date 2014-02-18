<?php
if (EXEC != 1) {
    die('Invalid request');
}

$path   = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);
$pages  = explode('/', trim($path, '/'));

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>OpenSim-CMS <?php echo ($pages[0] != '' ? ' - '. ucfirst($pages[0]) : ''); ?></title>

        <!-- Bootstrap CSS -->
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/less/main.less" rel="stylesheet/less" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- Important JS files that need to be loaded before body -->
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/jquery-2.1.0.min.js" type="text/javascript"></script>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/jquery.rest.js" type="text/javascript"></script>
        <script type="text/javascript">
            jQuery(document).ready(function($){
                var client = new $.RestClient('/OpenSim-CMS/api/', {
                    cache: 5,
                    cachableMethods: ["GET"]
                });
            });
        </script>
    </head>
    <body>
        <!-- Fixed navbar -->
        <div class="navbar navbar-default navbar-fixed-top" role="navigation">
            <div class="container">
                <div class="navbar-header">
                    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="<?php echo SERVER_ROOT; ?>/cms/">OpenSim-CMS</a>
                </div>
                <div class="navbar-collapse collapse">
                    <ul class="nav navbar-nav">
                        <li class="<?php echo (in_array($pages[0], array('presentations', 'presentation')) ? 'active' : ''); ?>"><a href="<?php echo SERVER_ROOT; ?>/cms/presentations/">Presentations</a></li>
                    </ul>
                </div><!--/.nav-collapse -->
            </div>
        </div>

        <!-- Content -->
        <div class="container">
<?php
    switch ($pages[0]) {
        case 'presentations':
            include dirname(__FILE__) . '/html/presentations.php';
        break;
        case 'presentation':
            include dirname(__FILE__) . '/html/presentation.php';
        break;
        default:

        break;
    }
?>          <hr>
            <footer class="footer">
                <p>&copy; OpenSim-CMS 2014</p>
            </footer>
        </div>
        <!-- Additional JS files -->
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/less-1.6.3.min.js" type="text/javascript"></script>
    </body>
</html>