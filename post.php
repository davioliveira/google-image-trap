<?php
define('BASE_URL', 'http://www.thesite.com');
define('IMAGE_BASE_URL', 'http://images.thesite.com');
define('TRAP_BASE_URL', 'http://cacheimages.thesite.com');

function is_google_bot() {
	return (strpos($_SERVER['HTTP_USER_AGENT'], 'Googlebot') !== FALSE);
}

// Example on posts

if (is_google_bot()) {
	echo '<img src="' . TRAP_BASE_URL .'/full-images/cat.jpg" />';
} else {
	echo '<img src="' . IMAGE_BASE_URL .'/full-images/cat.jpg" />';
}



?>