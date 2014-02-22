<?php
if(EXEC != 1) {
	die('Invalid request');
}
?>
<?php
    // Prevent going back to the signout page
    $previousPage = (isset($postData['currentPage']) ? htmlentities($postData['currentPage']) : SERVER_ROOT .'/cms/');
    if(strpos($previousPage, '/signout/') !== FALSE) {
        $previousPage = SERVER_ROOT .'/cms/';
        $text = 'main page';
    } else {
        $text = 'previous page';
    }
?>
<div class="page-header">
    <h1>Signed In <small>Welcome</small></h1>
</div>
<p>You have been signed in. Click <a href="<?php echo $previousPage; ?>">here</a> to return to the <?php echo $text; ?>.</p>