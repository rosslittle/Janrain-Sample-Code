<?php
/**
 * Copyright 2011
 * Janrain Inc.
 * All rights reserved.
 */
/**
 * Below is a very simple PHP 5 script that 
 * implements an Engage token URL to collect 
 * and output the results from auth_info.
 * The code below assumes you have the 
 * CURL HTTP fetching library with SSL and 
 * PHP JSON support.
 */

ob_start();
require_once('../library/engage.lib.php');
require_once('../library/engage.activity.lib.php');
$debug_array = array('Debug out:');

/**
 * For a production script it would be better 
 * to include (require_once) the apiKey in 
 * from a file outside the web root to 
 * enhance security.
 * 
 * Set your API key (secret) in this file.
 * The varable is $api_key
 *
 * Set the "Pro" status in this file.
 * The variable is $engage_pro
 */
require_once('engage-conf.php');

$token = $_POST['token'];
$format = ENGAGE_FORMAT_JSON;
$extended = true;

$result = engage_auth_info($api_key, $token, $format, $extended);
if ($result === false) {
	$errors = engage_get_errors();
	foreach ($errors as $error=>$label) {
		$debug_array[] = 'Error: '.$error;
	}
} else {
	$array_out = true;
/**
 * On a successful authentication the 
 * variable (array) $auth_info_array 
 * will contain the resulting data. 
 */
	$auth_info_array = engage_parse_result($result, $format, $array_out);
	$debug_array[] = print_r($auth_info_array, true);
}

/* Can we use get_contacts?  Is this a Pro application? */
$go_contacts = false;
if ($engage_pro === true && $do_get_contacts === true) {
	if (is_array($auth_info_array)) {
		if (engage_get_contacts_provider($auth_info_array['profile']['providerName'])) {
			$go_contacts = true;
		}
	}
}

/* Can we perform an activity post? Is this a Pro application? */
$go_activity = false;
if ($engage_pro === true && $do_activity === true) {
  if (is_array($auth_info_array)) {
    if (isset($auth_info_array['profile']['providerName']) && isset($auth_info_array['profile']['identifier'])) {
      /* First define the base share. This sets up the basic elements required to post. */
      $activity_base = engage_activity_base('http://www.janrain.com/', 'Share', date(DATE_RFC822), $title=NULL, $description=NULL);
      
      /* Next one media item can be added. There are three media items types available. */
      
      /* The image media item can have up to five members on Facebook. Only the first will be used for other providers that support images. */
      $media_image = engage_activity_media_image('http://www.janrain.com/sites/default/themes/janrain/logo.png', 'http://www.janrain.com/', $media_image=NULL);
      $media_image = engage_activity_media_image('http://www.janrain.com/sites/default/files/engage-trio.png', 'http://www.janrain.com/products', $media_image);
      $media_image = engage_activity_media_image('http://www.janrain.com/sites/default/files/engage.png', 'http://www.janrain.com/products/engage', $media_image);
      $media_image = engage_activity_media_image('http://www.janrain.com/sites/default/files/capture.png', 'http://www.janrain.com/products/capture', $media_image);
      $media_image = engage_activity_media_image('http://www.janrain.com/sites/default/files/federate.png', 'http://www.janrain.com/products/federate', $media_image);

      /* The flash media item requires a URL to a thumbnail and one to the SWF. Use the "old" style embed URL for YouTube. */
      $media_flash = engage_activity_media_flash(
        'http://www.youtube.com/v/vBMyz9jrx5U?version=3&amp;hl=en_US&amp;rel=0', 
        'http://i3.ytimg.com/vi/vBMyz9jrx5U/default.jpg',
        90, 31, 398, 265, $media_flash=NULL);

      /* The mp3 media item is uses mp3 URL, title, artist, and album. */ 
      $media_mp3 = engage_activity_media_mp3(
        'http://ontherecordpodcast.com/pr/otro/electronic/Get_Facebook_Friends_and_Twitter_Followers_While_You_Sleep.mp3',
        'Get Facebook Friends and Twitter Followers While You Sleep', 
        'Tore Steen', 'On The Record Online', $media_mp3=NULL);

      /* Only one media item can be posted. */
      $activity_media = $media_image; 
      
      /* Combine the base with the optional items to create the activity item. */
      $activity_item = engage_activity_item($activity_base, $activity_media, $action_links=NULL, $properties=NULL);
      
      /* Post the item. */
      $activity_result = engage_activity($api_key, $auth_info_array['profile']['identifier'], $activity_item);
      if ($activity_result !== false) {
        $go_activity = true;
      }
    }
  }
}

$errors = engage_get_errors(ENGAGE_ELABEL_ERROR);
foreach ($errors as $error=>$label) {
	$error_array[] = 'Error: '.$error;
}

/*
 * Uncomment lines below to get SDK level
 * debug data. Caution: This could result in 
 * revealing the api_key.
 */
//$debugs = engage_get_errors(ENGAGE_ELABEL_DEBUG);
//foreach ($debugs as $debug=>$label) {
//	$debug_array[] = 'Debug: '.$debug;
//}

$the_buffer = ob_get_contents();
if (!empty($the_buffer)) {
	$debug_array[] = 'Buffer: '.$the_buffer;
}
/* The variable (string) $the_debug will contain debug data. */
$the_debug = implode("\n", $debug_array);
$the_error = implode("\n", $error_array);
ob_end_clean();
?>
<html>
	<head>
		<title>Janrain Engage token URL example</title>
	</head>
	<body>
<?php 
/**
 * For this get_contacts sample to work you 
 * need to set $engage_pro to true. 
 */
if ($go_contacts === true) {  
?>
		<h4>get_contacts</h4>
		<p>Loaded in an iframe with a trigger link to allow the parent page to render while this loads.</p>
		<iframe src="engage-contacts.php?identifier=<?php 
		echo urlencode($auth_info_array['profile']['identifier']); 
		?>" style="width:100%;height:240px"></iframe>
<?php 
}
if ($go_activity == true) {
  if ($activity_result !== false) {
    echo '<p> Activity posted to '.$auth_info_array['profile']['providerName'].'</p>';
  }
}
?>
		<pre>
<?php echo $the_error; ?>

<?php echo $the_debug; ?>
		</pre>
	</body>
</html>
