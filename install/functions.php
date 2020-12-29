<?php
define('AUTOCAPTCHAS_VERSION' , '1.1.7');
define('AUTOCAPTCHAS_PATH', dirname(__FILE__).'/');

function http_get($url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_HEADER, FALSE);					
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
	curl_setopt($ch, CURLOPT_TIMEOUT, 300);
	$raw=curl_exec($ch);
	curl_close($ch);		
	
	return $raw;
}
	
function autocaptchas_header(){
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>AutoCAPTCHAs v<?php echo AUTOCAPTCHAS_VERSION;?> Installation</title>
        <link href="install.css" type="text/css" rel="stylesheet" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/parsley.js/2.9.1/parsley.min.js" type="text/javascript"></script>
    </head>

    <body>
    <div id="wrapper">
    <div id="logo"><h2 class="text-info">AutoCAPTCHAs</h2></div>
    <div class="login_box">
<?php
} 

function autocaptchas_header_nologo(){
?>
    <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
    <html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>AutoCAPTCHAs v<?php echo AUTOCAPTCHAS_VERSION;?> Installation</title>
        <link href="install.css" type="text/css" rel="stylesheet" />
		<script src="https://cdnjs.cloudflare.com/ajax/libs/parsley.js/2.9.1/parsley.min.js" type="text/javascript"></script>
    </head>

    <body>
    <div id="wrapper">
    <div class="login_box">
<?php
} 

function autocaptchas_footer(){
?>
    </div>
    <div class="footer">
        AutoCAPTCHAs v<?=AUTOCAPTCHAS_VERSION?> Powered by CAPTCHAs.IO<br>
    </div>
    </div>
    </body>
    </html>
<?php
}
function autocaptchas_footer_nocopy(){
?>
    </div>
    </div>
    </body>
    </html>
<?php
}
?>