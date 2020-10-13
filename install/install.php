<?php
/**
 * @package AutoCAPTCHAs
 * @website: http://autocaptchas.com
 * @author Glenn Prialde
 * @since 1.1.5
 */
ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE);
session_start();
require_once __DIR__.'/functions.php';

require_once(__DIR__.'/../lib/base.php');
require_once(__DIR__.'/../lib/db/sql.php');
require_once __DIR__.'/includes/classes/classInput.php';

require_once __DIR__.'/../lib/ini.php';
$input = new Input_Cleaner();

function autocaptchas_getquery() {
	$query = "SET NAMES utf8;SET time_zone = '+00:00';SET foreign_key_checks = 0;SET sql_mode = 'NO_AUTO_VALUE_ON_ZERO';DROP TABLE IF EXISTS `activity`;CREATE TABLE `activity` (`id` int(11) NOT NULL AUTO_INCREMENT,`action` varchar(50) NOT NULL,`notes` varchar(255) NOT NULL,`date` timestamp NOT NULL DEFAULT current_timestamp(),PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;DROP TABLE IF EXISTS `requests`;CREATE TABLE `requests` (`id` varchar(255) NOT NULL,`key` varchar(255) NOT NULL,`base64` text NOT NULL DEFAULT '',`answer` text NOT NULL DEFAULT '',`status` int(11) NOT NULL DEFAULT 0,`date` timestamp NOT NULL DEFAULT current_timestamp(),`displayed` int(11) NOT NULL DEFAULT 0,`recaptcha` int(11) NOT NULL DEFAULT 0,PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8;";	
	return $query;
}

function autocaptchas_saveconfigfile($db_host, $db_port, $db_name, $db_user, $db_password, $base_url, $site_name, $support, $recaptchas, $images, $audios, $admin_email, $package = 'AutoCAPTCHAs.COM', $version = '1.1.5'){
	$base_url = rtrim($base_url, '/');

$content = "[globals]\n\r" . PHP_EOL . "
DEBUG=3\n\r" . PHP_EOL . "
UI=ui/\n\r" . PHP_EOL . "
KEY='" . $_SESSION['reseler_key'] . "'\n\r" . PHP_EOL . "
BASEURL='" . $base_url . "'\n\r" . PHP_EOL . "
SITENAME='" . $site_name . "'\n\r" . PHP_EOL . "
ADMINEMAIL='" . $admin_email . "'\n\r" . PHP_EOL . "
SUPPORT='" . $support . "'\n\r" . PHP_EOL . "
DBHOST='" . $db_host . "'\n\r" . PHP_EOL . "
DBPORT='" . $db_port . "'\n\r" . PHP_EOL . "
DBNAME='" . $db_name . "'\n\r" . PHP_EOL . "
DBUSERNAME='" . $db_user . "'\n\r" . PHP_EOL . "
DBPASSWORD='" . $db_password . "'\n\r" . PHP_EOL . "
RECAPTCHARATE='" . $recaptchas . "'\n\r" . PHP_EOL . "
IMAGERATE='" . $images. "'\n\r" . PHP_EOL . "
AUDIORATE='" . $audios . "'\n\r" . PHP_EOL . "
PACKAGE='" . $package. "'\n\r" . PHP_EOL . "
VERSION='" . $version. "'\n\r" . PHP_EOL;

	
	if ( !file_put_contents(dirname(dirname(__FILE__)) . '/lib/config.ini', $content) ) {
		return false;
	} else {
		return true;	
	}
}


function autocaptchas_agreement() {
	autocaptchas_header();
	
	if (isset($_REQUEST['reseler_key'])) {
		$response = http_get("https://api.captchas.io/reseller/get_reseller_profile?key=" . trim($_REQUEST['reseler_key']));
		$reseller = json_decode($response, TRUE);
		$_SESSION['reseler_key'] = trim($_REQUEST['reseler_key']);
	} else {
		print '<script>window.location.href="../install/";</script>';
	}
?>
<h3>Welcome <?=$reseller['name']?></h3>
<p><div>PayPal.com Email: <b><?=$reseller['paypal']?></b></div></p>
<?
$error = FALSE;
if (empty($reseller['paypal'])) {
	$error = TRUE;
	print '<code><p><strong>ERROR</strong>: Your PayPal.com email is empty. Please visit <a href="https://www.paypal.com/" target="_blank">PayPal.com</a> to get your id and code. To set these values in your reseller account please go to this <a href="https://app.captchas.io/reseller/settings" target="_blank">page</a>.</p></code>';
}

?>
<p>Welcome to AutoCAPTCHAs installation process! This will be easy and fun. If you need help, take a look to the <a href="https://github.com/articlefr/AutoCAPTCHAs/blob/master/README.md" target="_new">ReadMe documentation</a></p>
    <p>If you have new ideas to improve the software, feel free to contact us:</p>
<ul>
<li><a href="http://autocaptchas.com">Live Website</a></li>
    <li><a href="https://autocaptchas.raiseaticket.com/support/#/newticket">Helpdesk ticket</a></li>
</ul>
<? if (!$error) { ?>
  	<form method="post" action="./install.php">
		<input type="hidden" name="license" value="agree" />
		<input type="submit" value="Continue" />
	</form>
<? } ?>
<?php
	autocaptchas_footer();
}

function autocaptchas_checksetup(){
	$error_msg = array();
    if ( function_exists('version_compare') && version_compare(PHP_VERSION,'5.0.0','<') ){
		$error_msg[] = 'PHP version <b>5.0+</b> required, you are using: <b>' . PHP_VERSION . '</b>';
	}
	if ( ! function_exists('mysql_connect') && ! function_exists('mysqli_connect') ){
		$error_msg[] = 'MySQL is disabled.';
	}
	if ( ! is_writable(dirname(dirname(__FILE__)).'/lib') )
	{
		// -> try to CHMOD it
		if ( function_exists('chmod') )
		{
			@chmod(dirname(dirname(__FILE__)).'/lib', 777);
		}

		// -> test again
		if ( ! is_writable(dirname(dirname(__FILE__)).'/lib') )
		{
			$error_msg[] = 'File <strong>' . dirname(dirname(__FILE__)).'/lib</strong> is not writable by PHP.';
		}
	}
	
    $attach_dir = dirname(dirname(__FILE__)) . '/lib/config.ini';
	if ( ! file_exists($attach_dir) )
	{
	    @mkdir($attach_dir, 0755);
	}
	
	if ( is_file($attach_dir) )
    {
	    if ( ! is_writable($attach_dir) )
	    {
			@chmod($attach_dir, 0777);
			if ( ! is_writable($attach_dir) )
			{
				$error_msg[] = '>File ' . dirname(dirname(__FILE__)) . '/lib/<strong>config.ini</strong> is not writable by PHP.';
		   	}
	    }
	}
	else
	{
		$error_msg[] = 'File ' . dirname(dirname(__FILE__)) . '/lib/<strong>config.ini</strong> is missing.';
	}
	
    if ( count($error_msg) ){
		autocaptchas_header();
		echo '<h3>Check Setup</h3>';
		echo '<div class="error_box">';
        foreach ($error_msg as $err)
        {
        	echo $err.'<br>';
        }
		echo '</div>';
		autocaptchas_footer();	
	}else{
		autocaptchas_database();	
	}
}

function autocaptchas_database($error_msg =null){
	if($error_msg !== null){
		autocaptchas_header_nologo();
		echo '<div class="error_box">'.$error_msg.'</div>';	
		autocaptchas_footer_nocopy();
	} else {
		autocaptchas_header();
	?>
    <h3>Database settings</h3>
	<form action="install.php" method="post" data-parsley-validate>
	<table cellspacing="10" cellpadding="10">
	<tr>
	<td width="200">Database Host:</td>
	<td><input type="text" name="db_host" value="<?php echo htmlspecialchars($_REQUEST['db_host']);?>" size="40" autocomplete="off" required /></td>
	</tr>
	<tr>
	<td width="200">Database Port:</td>
	<td><input type="text" name="db_port" value="<?php echo htmlspecialchars($_REQUEST['db_port']);?>" size="40" autocomplete="off" required /></td>
	</tr>	
	<tr>
	<td width="200">Database Name:</td>
	<td><input type="text" name="db_name" value="<?php echo htmlspecialchars($_REQUEST['db_name']);?>" size="40" autocomplete="off" required /></td>
	</tr>
	<tr>
	<td width="200">Database User (login):</td>
	<td><input type="text" name="db_user" value="<?php echo htmlspecialchars($_REQUEST['db_user']);?>" size="40" autocomplete="off" required /></td>
	</tr>
	<tr>
	<td width="200">User Password:</td>
	<td><input type="text" name="db_password" value="<?php echo htmlspecialchars($_REQUEST['db_password']);?>" size="40" autocomplete="off" required /></td>
	</tr>
    </table>
	
	<br>
	
    <h3>Website settings</h3>

	<table cellspacing="10" cellpadding="10">
	<tr>
	<td width="200">Base URL:</td>
	<td><input type="url" name="base_url" value="<?php echo htmlspecialchars($_REQUEST['base_url']);?>" size="40" autocomplete="off" required /></td>
	</tr>
	
	<tr>
	<td width="200">Site Name:</td>
	<td><input type="text" name="site_name" value="<?php echo htmlspecialchars($_REQUEST['site_name']);?>" size="40" autocomplete="off" required /></td>
	</tr>
	
	<tr>
	<td width="200">Admin Email:</td>
	<td><input type="email" name="admin_email" value="<?php echo htmlspecialchars($_REQUEST['admin_email']);?>" size="40" autocomplete="off" required /></td>
	</tr>

	<tr>
	<td width="200">Support URL:</td>
	<td><input type="url" name="support" value="<?php echo htmlspecialchars($_REQUEST['support']);?>" size="40" autocomplete="off" required /></td>
	</tr>
	</table>
	
	<br>
	
	<h3>Price Rate</h3>
	
	<?
		$_REQUEST['recaptchas'] = empty($_REQUEST['recaptchas'])? '0.001' : $_REQUEST['recaptchas'];
		$_REQUEST['images'] = empty($_REQUEST['images'])? '0.0005' : $_REQUEST['images'];
		$_REQUEST['audios'] = empty($_REQUEST['audios'])? '0.0007' : $_REQUEST['audios'];
	?>
	
	<table cellspacing="10" cellpadding="10">
	<tr>
	<td width="200">reCAPTCHA Price:</td>
	<td>
		<input type="text" name="recaptchas" value="<?php echo htmlspecialchars($_REQUEST['recaptchas']);?>" size="40" autocomplete="off" required />
		<div><small>reCAPTCHA solve rate per 1000 solves. Default: 0.001 for $1 per 1000 solves.</small></div>
	</td>
	</tr>

	<tr>
	<td width="200">Images Price:</td>
	<td>
		<input type="text" name="images" value="<?php echo htmlspecialchars($_REQUEST['images']);?>" size="40" autocomplete="off" required />
		<div><small>Images solve rate per 1000 solves. Default: 0.0005 for $0.5 per 1000 solves.</small></div>
	</td>
	</tr>

	<tr>
	<td width="200">Audio Price:</td>
	<td>
		<input type="text" name="audios" value="<?php echo htmlspecialchars($_REQUEST['audios']);?>" size="40" autocomplete="off" required />
		<div><small>Audio solve rate per 1000 solves. Default: 0.0007 for $0.7 per 1000 solves.</small></div>
	</td>
	</tr>

	<tr>
		<td></td>
		<td>
		<input type="hidden" name="license" value="agree" />
		<input type="hidden" name="install" value="install" />
		<input type="submit" name="btn" value="Install AutoCAPTCHAs Website" />
		</td>
	</tr>
	</table>
    </form>
    <?php
	autocaptchas_footer();
	}	
}

function autocaptchas_completed(){
	autocaptchas_header();	
?>
	<h3>Installation Completed</h3>
    <p>Installation has been successfully completed, <strong>do not forget to remove</strong> <strong style="color:red">/install</strong> folder</p>
    <p><a href="/" target="_blank">Click here to open the website</a></p>
<?php
	autocaptchas_footer();	
}

if($_REQUEST['license'] == 'agree'){
	if($_REQUEST['install'] == 'install'){
		$error_msg = "";
		
		if(!empty($error_msg)){
			autocaptchas_database($error_msg);
		}else{
			$query = autocaptchas_getquery();			
			
			$db=new DB\SQL('mysql:host='.$_REQUEST['db_host'].';port='.$_REQUEST['db_port'].';dbname='.$_REQUEST['db_name'],$_REQUEST['db_user'],$_REQUEST['db_password']);
			$db->exec($query);
			
			autocaptchas_saveconfigfile($_REQUEST['db_host'], $_REQUEST['db_port'], $_REQUEST['db_name'], $_REQUEST['db_user'], $_REQUEST['db_password'], $_REQUEST['base_url'], $_REQUEST['site_name'], $_REQUEST['support'], $_REQUEST['recaptchas'], $_REQUEST['images'], $_REQUEST['audios'], $_REQUEST['admin_email']);
			header('location: install.php?result=completed');
		}
	}
	autocaptchas_checksetup();
}else{
	if($_REQUEST['result'] == 'completed'){
		autocaptchas_completed();
	}else{
		autocaptchas_agreement();
	}
}
?>