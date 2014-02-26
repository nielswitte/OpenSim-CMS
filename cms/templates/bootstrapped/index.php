<?php
if (EXEC != 1) {
    die('Invalid request');
}

// Content pages
$contentPages = array(
    // List with additional header code, which is loaded before any HTML output is done
    // Body code is included as the main body of this page
    'grids'         => array('header' => '',                    'body' => 'grids.php',          'auth' => TRUE),
    'grid'          => array('header' => '',                    'body' => 'grid.php',           'auth' => TRUE),
    'meetings'      => array('header' => 'meetings.php',        'body' => 'meetings.php',       'auth' => TRUE),
    'presentations' => array('header' => '',                    'body' => 'presentations.php',  'auth' => TRUE),
    'presentation'  => array('header' => '',                    'body' => 'presentation.php',   'auth' => TRUE),
    'signout'       => array('header' => 'signout.php',         'body' => 'signout.php',        'auth' => FALSE),
    'signin'        => array('header' => 'signin.php',          'body' => 'signin.php',         'auth' => FALSE)
);

// Get request parameters
$requestPath    = filter_input(INPUT_GET, '_url', FILTER_SANITIZE_SPECIAL_CHARS);
$pagesRequest   = explode('/', trim($requestPath, '/'));
$pageRequest    = htmlentities($pagesRequest[0]);

// user is authed?
$isAuthorized = isset($_SESSION["AccessToken"]) ? TRUE : FALSE;

// Show page content
if(isset($pageRequest) && array_key_exists($pageRequest, $contentPages)){
    $content        = $contentPages[$pageRequest]['body'];
    $header         = $contentPages[$pageRequest]['header'];
    $authRequired   = $contentPages[$pageRequest]['auth'];
} else {
    $content        = 'default.php';
    $header         = '';
    $authRequired   = FALSE;
}

if($header != '' && $isAuthorized >= $authRequired) {
    include dirname(__FILE__) .'/html/head/'. $header;
}

?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>OpenSim-CMS <?php echo ($pageRequest != '' ? ' - '. ucfirst($pageRequest) : ''); ?></title>

        <!-- Bootstrap CSS -->
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/css/select2.css" rel="stylesheet" type="text/css">
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/css/select2-bootstrap.css" rel="stylesheet" type="text/css">
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/less/main.less" rel="stylesheet/less" type="text/css">
<?php
    if(isset($extraCss)) {
        foreach($extraCss as $css) {
?>
        <link href="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/css/<?php echo $css; ?>" rel="stylesheet" type="text/css">
<?php
        }
    }
?>

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- Important JS files that need to be loaded before body -->
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/jquery-2.1.0.min.js" type="text/javascript"></script>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/jquery.rest.js" type="text/javascript"></script>
        <script type="text/javascript">
            var client;
            var api_token = "<?php echo isset($_SESSION['AccessToken']) ? $_SESSION['AccessToken'] : ''; ?>";
            var base_url = "<?php echo SERVER_ROOT; ?>";
            var pages = [ <?php foreach($pagesRequest as $page) { echo '"'. $page .'",'; } ?> ];

            jQuery(document).ready(function($){
                client = new $.RestClient('/OpenSim-CMS/api/', {
                    cache: 10
                });

                client.add('grids');
                client.add('grid');
                client.add('meetings');
                client.add('meeting');
                client.add('presentations');
                client.add('presentation');
                client.add('user');
            });
        </script>
<?php
    if(isset($extraJs)) {
        foreach($extraJs as $js) {
?>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/<?php echo $js; ?>" type="text/javascript"></script>
<?php
        }
    }
?>
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
<?php if($isAuthorized) { ?>
                        <li class="<?php echo (in_array($pageRequest, array('grids', 'grid')) ? 'active' : ''); ?>"><a href="<?php echo SERVER_ROOT; ?>/cms/grids/">Grids</a></li>
                        <li class="<?php echo (in_array($pageRequest, array('meetings', 'meeting')) ? 'active' : ''); ?>"><a href="<?php echo SERVER_ROOT; ?>/cms/meetings/">Meetings</a></li>
                        <li class="<?php echo (in_array($pageRequest, array('presentations', 'presentation')) ? 'active' : ''); ?>"><a href="<?php echo SERVER_ROOT; ?>/cms/presentations/">Presentations</a></li>
<?php } ?>
                    </ul>
                    <?php include dirname(__FILE__) .'/html/userinfo.php'; ?>
                </div><!--/.nav-collapse -->
            </div>
        </div>

        <!-- Content -->
        <div class="container">
            <div id="loading">
                <div class="spinner">
                    <div class="cube1"></div>
                    <div class="cube2"></div>
                </div>
            </div>
            <div id="alerts"></div>
<?php
// Authorization required?
if($isAuthorized >= $authRequired) {
    include dirname(__FILE__) .'/html/body/'. $content;
} else {
    include dirname(__FILE__) .'/html/body/notauthorized.php';
}
?>
            <hr>
            <footer class="footer">
                <p>&copy; OpenSim-CMS 2014</p>
            </footer>
        </div>
        <!-- Additional JS files -->
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/bootstrap.min.js" type="text/javascript"></script>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/select2-3.4.5.min.js" type="text/javascript"></script>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/libs/less-1.6.3.min.js" type="text/javascript"></script>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/main.js" type="text/javascript"></script>
<?php if(file_exists(dirname(__FILE__) . '/js/'. $pageRequest .'.js') && $isAuthorized >= $authRequired) { ?>
        <script src="<?php echo SERVER_ROOT; ?>/cms/templates/bootstrapped/js/<?php echo $pageRequest; ?>.js" type="text/javascript"></script>
<?php } ?>
    </body>
</html>