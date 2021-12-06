<?php
/**
 * @package AutoCAPTCHAs
 * @website: http://autocaptchas.com
 * @author Glenn Prialde
 * @since 1.0.0
 */
 
ini_set('display_errors', 0);
error_reporting(0);

if (file_exists('./install/index.php')) {
	@header('Location: install');
	exit;
}

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
	
require_once('lib/curl.php');
require_once('lib/db/sql.php');
$f3=require('lib/base.php');

$f3->set('DEBUG', 0);
/*if ((float)PCRE_VERSION<7.9)
	trigger_error('PCRE version is out of date');
*/
$f3->config('lib/config.ini');

$config = parse_ini_file("lib/config.ini", 'globals');
$f3->set('APIKEY', $config['globals']['KEY']);
$f3->set('BASEURL', $config['globals']['BASEURL']);
$f3->set('SITENAME', $config['globals']['SITENAME']);
$f3->set('ADMINEMAIL', $config['globals']['ADMINEMAIL']);
$f3->set('SUPPORT', $config['globals']['SUPPORT']);
$f3->set('RECAPTCHARATE', $config['globals']['RECAPTCHARATE']);
$f3->set('IMAGERATE', $config['globals']['IMAGERATE']);
$f3->set('AUDIORATE', $config['globals']['AUDIORATE']);

$f3->set('PACKAGE', $config['globals']['PACKAGE']);
$f3->set('VERSION', $config['globals']['VERSION'] . '-Release');

//$db=new DB\SQL('mysql:host='.$config['globals']['DBHOST'].';port='.$config['globals']['DBPORT'].';dbname='.$config['globals']['DBNAME'],$config['globals']['DBUSERNAME'],$config['globals']['DBPASSWORD']);

$session = $f3->get('SESSION');
$profile = unserialize($session['profile']);
$ukey =  $profile['key'];

//$f3->set('solves_t', $db->exec("SELECT * FROM `requests` WHERE `key` = '" . $ukey . "' AND `status` = 1 AND DATE(`date`) = DATE(NOW())"));

$f3->route('GET|POST /',
	function($f3) {
		$f3->set('content','index.html');
		echo View::instance()->render('layout.html');
	}
);

$f3->route('GET /download',
	function($f3) {
		$f3->reroute('https://github.com/captchasio/AutoCAPTCHAs/releases');
	}
);

$f3->route('GET|POST /accounts',
	function($f3) {
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		$profile = unserialize($session['profile']);
		
		if ($authenticated === FALSE) {
			$f3->reroute($f3->get('BASEURL') . '/accounts/login');
		}
	
		$f3->set('email', $profile['email']);
		$f3->set('key', $profile['key']);
		$f3->set('accounts', 'active');
		
		$reseller_key = $f3->get('APIKEY');
		$user_key = $profile['key'];
		
		$curl = new Curl();
		
		$response = $curl->get('http://api.captchas.io/reseller/history?key=' . $reseller_key . '&user_key=' . $user_key);
	
		$history = json_decode($response, TRUE);
		
		$f3->set('activities', $history['solves']);
		
		$response2 = $curl->get('https://api.captchas.io/reseller/get_user?key=' . $reseller_key . '&user_key=' . $user_key);
		$user = json_decode($response2, TRUE);
		
		$f3->set('credits', $user['credits']);
		$f3->set('recaptcha_rate', $f3->get('RECAPTCHARATE'));
		$f3->set('image_rate', $f3->get('IMAGERATE'));
		$f3->set('audio_rate', $f3->get('AUDIORATE'));
		
		if ($history['total'] == NULL) {
			$solves = 0;
		} else {
			$solves = $history['total'];
		}
				
		$f3->set('recaptchas', $f3->nice_number($history['recaptcha']));
		$f3->set('images', $f3->nice_number($history['image']));
		$f3->set('audios', $f3->nice_number($history['audio']));
		$f3->set('solves', $f3->nice_number($solves));		
		
		$f3->set('content','app/index.html');
		echo View::instance()->render('app/layout.html');
	}
);

$f3->route('GET|POST /accounts/solves',
	function($f3) {
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		$profile = unserialize($session['profile']);
		
		$f3->set('email', $profile['email']);
		$f3->set('key', $profile['key']);
		
		$f3->set('accounts', '');
		$f3->set('document', '');
		$f3->set('profile', '');
		$f3->set('solves', 'active');
		
		if (!$authenticated) {
			$f3->reroute($f3->get('BASEURL') . '/accounts/login');
		}
		
		$reseller_key = $f3->get('APIKEY');
		$user_key = $profile['key'];
		
		$curl = new Curl();
		
		$response = $curl->get('http://api.captchas.io/reseller/history?key=' . $reseller_key . '&user_key=' . $user_key);
	
		$history = json_decode($response, TRUE);
		
		$f3->set('activities', $history['solves']);
		
		$f3->set('date', date('Y'));
		$f3->set('email', $profile['email']);
		
		$f3->set('content','app/solves.html');
		//echo \Template::instance()->render('app/table_layout.html');
		echo View::instance()->render('app/layout.html');
	}
);

$f3->route('GET|POST /accounts/orders',
	function($f3) {
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		$profile = unserialize($session['profile']);
		
		$reseller_key = $f3->get('APIKEY');
		$user_key = $profile['key'];
		
		if (!$authenticated) {
			$f3->reroute($f3->get('BASEURL') . '/accounts/login');
		}
	
		$curl = new Curl();
		
		$response = $curl->get('https://api.captchas.io/reseller/user_orders?key=' . $reseller_key . '&user_key=' . $user_key);
		$orders = json_decode($response, TRUE);
		
		$f3->set('email', $profile['email']);
		$f3->set('orders', $orders['orders']);
		
		$f3->set('content','app/orders.html');
		echo View::instance()->render('app/layout.html');
	}
);

$f3->route('GET|POST /accounts/document',
	function($f3) {
		$f3->set('document', 'active');
		
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		$profile = unserialize($session['profile']);
		
		if (!$authenticated) {
			$f3->reroute('/accounts/login');
		}
	
		$f3->set('email', $profile['email']);
		$f3->set('key', $profile['key']);
		$f3->set('base', $f3->get('BASEURL') . "/");
		
		$f3->set('content','app/document.html');
		echo View::instance()->render('app/layout.html');
	}
);

$f3->route('GET|POST /accounts/profile',
	function($f3) {
		$f3->set('profile', 'active');
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		$profile = unserialize($session['profile']);
		
		if (!$authenticated) {
			$f3->reroute($f3->get('BASEURL') . '/accounts/login');
		}
	
		$f3->set('name', $profile['name']);
		$f3->set('email', $profile['email']);
		$f3->set('key', $profile['key']);
		$f3->set('password', $profile['password']);
		
		$params = $f3->get('POST');
		$key = $f3->get('APIKEY');
		$ip = $f3->get('IP');
		
		if (!empty($params['save'])) {	
			$curl = new Curl();
			
			if($profile['password'] == $params['password']) {
				$data = array(
					'key' => $key,
					'email' => strtolower($params['email']),
					'name'=> ucwords($params['name']),
					'user_id' => $profile['id'],
					'password' => $params['new_password']
				);				
			} else {
				$data = array(
					'key' => $key,
					'email' => strtolower($params['email']),
					'name'=> ucwords($params['name']),
					'user_id' => $profile['id']
				);				
			}
			
			$response = $curl->post('https://api.captchas.io/reseller/update_user', $data);
			
			$profile = json_decode($response, TRUE);

			if (!empty($profile['id'])) {
				$f3->set('SESSION.authenticated', TRUE);
				$f3->set('SESSION.profile', serialize($profile));
				
				$f3->set('name', $profile['name']);
				$f3->set('email', $profile['email']);
				$f3->set('key', $profile['key']);
				$f3->set('password', $profile['password']);
		
				$f3->set('ESCAPE',FALSE);
				$f3->set('alerts', '<div><div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Success!</strong> Profile has been successfully updated.</div></div>');				
			} else {
				$f3->set('ESCAPE',FALSE);
				$f3->set('alerts', '<div><div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> Something went wrong please check your data.</div></div>');				
			}
		}
		
		$f3->set('content','app/profile.html');
		echo View::instance()->render('app/layout.html');
	}
);

$f3->route('GET|POST /accounts/login',
	function($f3) {
		$get = $f3->get('GET');
		$ref = empty($get['ref']) ? NULL : $get['ref'];
		
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		
		if ($authenticated && $ref == NULL) {
			$f3->reroute($f3->get('BASEURL') . '/accounts');
		} else if ($authenticated && $ref != NULL) {
			$f3->reroute($ref);
		}			
		
		$f3->set('ref', $ref);
		
		$params = $f3->get('POST');
		$key = $f3->get('APIKEY');

		if (isset($params['s'])) {	
			$curl = new Curl();
	
			$response = $curl->get('https://api.captchas.io/reseller/login_user?key=' . $key . '&user_email=' . $params['email'] . '&user_password=' . $params['password']);

			if ($response == 'ERROR_INVALID_PASSWORD') {
				$f3->set('ESCAPE',FALSE);
				$f3->set('errors', '<div><div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> Invalid password!</div></div>');
			} else {
				$f3->set('errors', '');
				
				$profile = json_decode($response, TRUE);
				
				if (!empty($profile['id'])) {
					$f3->set('SESSION.authenticated', TRUE);
					$f3->set('SESSION.profile', serialize($profile));

					if ($ref == NULL || empty($ref)) {
						$f3->reroute('/accounts');					
					} else if ($ref != NULL || !empty($ref)) {
						$f3->reroute($ref);
					} else {
						$f3->reroute($f3->get('BASEURL') . '/accounts');
					}
				} else {
					$f3->set('ESCAPE',FALSE);
					$f3->set('errors', '<div><div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> Check your email or password!</div></div>');					
				}
			}
		} else {
			if (!empty($params['s'])) {
				$f3->set('ESCAPE',FALSE);
				$f3->set('errors', '<div><div class="alert alert-danger"><button type="button" class="close" data-dismiss="alert">&times;</button><strong>Error!</strong> All fields are required for login!</div></div>');
			} else {
				$f3->set('errors', '');
			}
		}
		
		$f3->set('content','app/login.html');
		echo View::instance()->render('app/login_layout.html');
	}
);

$f3->route('GET|POST /accounts/logout',
	function($f3) {
		$f3->set('SESSION.authenticated', FALSE);
		$f3->set('SESSION.profile', NULL);
		$f3->set('SESSION', session_destroy());
		
		$f3->reroute($f3->get('BASEURL') . '/');
	}
);

$f3->route('GET|POST /accounts/register',
	function($f3) {
		$params = $f3->get('POST');
		$key = $f3->get('APIKEY');
		$ip = $f3->get('IP');
		
		if (!empty($params['email']) && !empty($params['password']) && !empty($params['name'])) {	
			$curl = new Curl();
			
			$data = array(
				'key' => $key,
				'email' => strtolower($params['email']),
				'password' => $params['password'],
				'name'=> ucwords($params['name']),
				'ip' => $ip
			);
			
			$response = $curl->post('https://api.captchas.io/reseller/register_user', $data);
			
			$profile = json_decode($response, TRUE);

			if (!empty($profile['id'])) {
				$f3->set('SESSION.authenticated', TRUE);
				$f3->set('SESSION.profile', serialize($profile));
				$f3->reroute($f3->get('BASEURL') . '/accounts');					
			}
		}
		
		$f3->reroute('/');
	}
);

$f3->route('GET|POST /buy/@amount',
	function($f3) {
		$session = $f3->get('SESSION');
		$authenticated = $session['authenticated'] ? TRUE : FALSE;
		$profile = unserialize($session['profile']);
		
		$amount = $f3->get('PARAMS.amount');
		$submit = $f3->get('POST.submit');

		$f3->set('amount', $amount);
		$f3->set('name', $profile['name']);
		$f3->set('email', $profile['email']);
		
		if (isset($submit) && $submit == 'pay') {
			$curl = new Curl();

			$key = $f3->get('APIKEY');
			
			$data = array(
				'key' => $key,
				'user_key' => $profile['key'], 
				'product_name' => $f3->get('SITENAME') . ' Credits Top-up',
				'price' => $amount,
				'currency' => 'USD',
				'image_url' => 'https://captchas.io/images/icon-200.png',
				'return_url' => $f3->get('BASEURL') . "/" . 'accounts',
				'payment_method' => 'paypal'
			);
			
			$response = $curl->post('https://api.captchas.io/reseller/create_order', $data);	
			
			$pay = json_decode($response, TRUE);
			$f3->reroute($pay['payment_link']);
		} 
		
		if (isset($submit) && $submit == 'register') {
			$password = $f3->get('POST.password');
			$email = strtolower(trim($f3->get('POST.email')));
			$name = ucwords(trim($f3->get('POST.name')));
			$ip = $f3->get('IP');
			$key = $f3->get('APIKEY');
			
			if (!empty($email) && !empty($password) && !empty($name)) {	
				$curl = new Curl();
				
				$data = array(
					'key' => $key,
					'email' => $email,
					'password' => $password,
					'name'=> $name,
					'ip' => $ip
				);
				
				$response = $curl->post('https://api.captchas.io/reseller/register_user', $data);
				
				$profile = json_decode($response, TRUE);

				if (!empty($profile['id'])) {
					$f3->set('SESSION.authenticated', TRUE);
					$f3->set('SESSION.profile', serialize($profile));
					$f3->reroute($f3->get('BASEURL') . '/buy/' . $amount);			
				}
			}			
		}
		
		if ($authenticated) {
			$f3->set('content','buy/index.html');
		} else {
			$f3->set('content','buy/register.html');
		}
		
		echo View::instance()->render('buy/layout.html');
	}
);

$f3->route('POST /buy',
	function($f3) {
		$params = $f3->get('POST');
		$amount = $params['amount'];
		
		$f3->set('amount', $amount);
		$f3->reroute('/buy/' . $amount);	
	}
);

$f3->run();
