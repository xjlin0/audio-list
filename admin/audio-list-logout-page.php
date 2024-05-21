<?php
/*
Template Name: Audio List Logout Page
*/


<?php
// Handle logout logic
if (is_user_logged_in() && isset($_POST['logout'])) {
    wp_logout();
    wp_redirect(home_url()); // Redirect to homepage after logout
    exit;
}

<div class="wrap">
    <h1>Custom Logout Page</h1>
    <p>You have successfully logged out.</p>
    <p><a href="<?php echo home_url(); ?>">Home</a> | <a href="<?php echo wp_login_url(); ?>">Login</a></p>
</div>
