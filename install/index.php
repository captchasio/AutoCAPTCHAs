<?php
/**
 * @package AutoCAPTCHAs
 * @website: http://autocaptchas.com
 * @author Glenn Prialde
 * @since 1.1.3
 */
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once __DIR__.'/functions.php';
autocaptchas_header();
?>
<form action="install.php" method="post" data-parsley-validate="">
	<center>
		<div>Welcome to AutoCAPTCHAs v<?php echo AUTOCAPTCHAS_VERSION;?> Installer. Please ENTER Your CAPTCHAs.IO Reseller Key</div>
		<div><input type="text" name="reseler_key" value="" size="10" autocomplete="off" required /></div>
		<div>If you do not have a reseller key please get one <a href="https://app.captchas.io/reseller/onboard/" target="_new">HERE</a>.</div>
	</center>
	<br>
    <div align="center">
        <button type="submit">Install a Fresh Copy of AutoCAPTCHAs</button>
    </div>
</form>	
<?php
autocaptchas_footer();
?>