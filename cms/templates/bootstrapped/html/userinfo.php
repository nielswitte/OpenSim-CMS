<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<ul class="nav navbar-nav navbar-right">
    <li class="dropdown">
<?php if(isset($_SESSION["AccessToken"])) { ?>
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Account<b class="caret"></b></a>
        <ul class="dropdown-menu">
            <li>
                <div class="navbar-content">
                    <div class="row">
                        <div class="col-md-5">
                            <img src="http://lh5.googleusercontent.com/-b0-k99FZlyE/AAAAAAAAAAI/AAAAAAAAAAA/twDq00QDud4/s120-c/photo.jpg" alt="Alternate Text" class="img-responsive" />
                            <p class="text-center small"><a href="#">Change Photo</a></p>
                        </div>
                        <div class="col-md-7">
                            <span id="loginUserName">Anonymous</span>
                            <p class="text-muted small" id="loginUserEmail">mail@company.com</p>
                            <div class="divider"></div>
                            <a href="<?php echo SERVER_ROOT; ?>/cms/user/<?php echo $_SESSION['UserId']; ?>/" class="btn btn-primary btn-sm">View Profile</a>
                        </div>
                    </div>
                </div>
                <div class="navbar-footer">
                    <div class="navbar-footer-content">
                        <div class="row">
                            <div class="col-md-6">
                                <a href="#" class="btn btn-default btn-sm">Change Password</a>
                            </div>
                            <div class="col-md-6">
                                <a href="<?php echo SERVER_ROOT; ?>/cms/signout/" class="btn btn-default btn-sm pull-right">Sign Out</a>
                            </div>
                        </div>
                    </div>
                </div>
            </li>
        </ul>

        <script type="text/javascript">
            jQuery(document).ready(function($) {
                client.user.read(<?php echo $_SESSION['UserId']; ?> ,{ token: api_token }).done(function(data) {
                    $('#loginUserName').text(data.userName);
                    $('#loginUserEmail').text(data.email);
                });
            });
        </script>

<?php } else { ?>
        <a href="#" class="dropdown-toggle" data-toggle="dropdown">Sign In<b class="caret"></b></a>
        <ul class="dropdown-menu">
            <li>
                <form method="POST" action="<?php echo SERVER_ROOT; ?>/cms/signin/" role="form" class="form-horizontal">
                    <div class="navbar-content">
                        <div class="row">
                            <div class="form-group">
                                <label for="LoginUserName" class="col-sm-4 control-label">Username</label>
                                <div class="col-sm-7">
                                    <input type="text" name="userName" class="form-control" id="LoginUserName" placeholder="username">
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="LoginPassword" class="col-sm-4 control-label">Password</label>
                                <div class="col-sm-7">
                                    <input type="password" name="password" class="form-control" id="LoginPassword" placeholder="password">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="navbar-footer">
                        <div class="navbar-footer-content">
                            <div class="row">
                                <div class="col-md-11">
                                    <input type="submit" name="signIn" class="btn btn-primary btn-sm" value="Sign In">
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" value="<?php echo $_SERVER['REQUEST_URI']; ?>" name="currentPage">
                </form>
            </li>
        </ul>
<?php } ?>
    </li>
</ul>