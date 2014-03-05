<?php
if (EXEC != 1) {
    die('Invalid request');
}
?>
<!DOCTYPE html>
<html lang="en" ng-app="OpenSim-CMS" ng-controller="MainCntl">
    <head>
        <base href="<?php echo SERVER_PROTOCOL .'://'. SERVER_ADDRESS .':'. SERVER_PORT . SERVER_ROOT; ?>/cms/" />
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ Page.title() }}</title>

        <!-- Bootstrap CSS -->
        <link href="templates/restangular/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="templates/restangular/css/angular-motion.min.css" rel="stylesheet" type="text/css">
        <link href="templates/restangular/css/select2.css" rel="stylesheet" type="text/css">
        <link href="templates/restangular/css/select2-bootstrap.css" rel="stylesheet" type="text/css">
        <link href="templates/restangular/less/main.less" rel="stylesheet/less" type="text/css">

        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
          <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
        <![endif]-->

        <!-- Important JS files that need to be loaded before body -->
        <script type="text/javascript">
            var server_address = "<?php echo SERVER_PROTOCOL; ?>://<?php echo SERVER_ADDRESS; ?>";
            var base_url = "<?php echo SERVER_ROOT; ?>";
        </script>
    </head>
    <body>
        <!-- Fixed navbar -->
        <header class="navbar navbar-default navbar-fixed-top" role="navigation" ng-controller="toolbarController" bs-navbar>
            <div class="container">
                <div class="navbar-header">
                    <button class="navbar-toggle" type="button" ng-click="toggleNavigation()">
                        <span class="sr-only">Toggle navigation</span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <a class="navbar-brand" href="">OpenSim-CMS</a>
                </div>
                <div class="collapse navbar-collapse" id="bs-navbar">
                    <ul class="nav navbar-nav" ng-include src="getMainNavigation()"></ul>
                    <ul class="nav navbar-nav navbar-right">
                        <li class="dropdown" ng-include src="getUserToolbar()"></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Content container -->
        <div class="container">
            <!-- Loading spinner -->
            <div id="loading">
                <div class="spinner">
                    <div class="cube1"></div>
                    <div class="cube2"></div>
                </div>
                <p class="text-center">Loading... please be patient</p>
            </div>

            <!-- Main content -->
            <div id="main" ng-view></div>
            <hr>
            <footer class="footer">
                <p>&copy; OpenSim-CMS 2014</p>
            </footer>
        </div>

        <!-- Alert container -->
        <div id="alerts"></div>

        <!-- Additional JS files -->
        <script src="templates/restangular/js/libs/jquery-2.1.0.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/angular-1.2.14.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/angular-animate-1.2.14.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/angular-route-1.2.14.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/angular-strap-2.0.0-rc.3.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/angular-strap.tpl-2.0.0-rc.3.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/restangular-1.3.1.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/underscore-1.6.0.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/moment-2.5.1.min.js" type="text/javascript"></script>

        <script src="templates/restangular/js/libs/select2-3.4.5.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/libs/less-1.6.3.min.js" type="text/javascript"></script>
        <script src="templates/restangular/js/main.js" type="text/javascript"></script>
        <script src="templates/restangular/js/app.js" type="text/javascript"></script>
        <script src="templates/restangular/js/controllers.js" type="text/javascript"></script>
    </body>
</html>