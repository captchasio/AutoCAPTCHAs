<?php
	ini_set('display_errors', 1);
	error_reporting(E_ERROR);	
	
	@header('Cache-Control: no-cache, no-store, must-revalidate');
	@header('Pragma: no-cache');
	@header('Expires: 0');	
	
	session_start();
	ob_start();
	
	require_once('api/system.php');
	
	$api = new API();
	
	$db = $api->get_db();
	$key = $api->get_key();
	$_user_key = trim($_REQUEST['key']);
	$_method = trim($_REQUEST['method']);
	$_json = $_REQUEST['json'] == 1 ? 1 : 0;
	
	function http_get($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);					
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 300);
		curl_setopt($ch, CURLOPT_TIMEOUT, 300);
		curl_setopt($ch, CURLOPT_TCP_NODELAY, TRUE);
		curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1a2pre) Gecko/2008073000 Shredder/3.0a2pre ThunderBrowse/3.2.1.8");	
		curl_setopt($ch, CURLOPT_TCP_NODELAY, TRUE);
		$raw=curl_exec($ch);
		curl_close($ch);		
		
		return $raw;
	}
	
	function to_base64($image) {
		$type = pathinfo($image, PATHINFO_EXTENSION);
		$data = file_get_contents($image);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);	
		
		return $base64;
	}
	
	function base64_to_file($base64_string, $output_file) {
		$data = explode(',', $base64_string);
		file_put_contents($output_file, base64_decode($data[1]));		
					
		return $output_file; 
	}	
	
	function getCurlValue($filename, $contentType = NULL, $postname = NULL) {
		if (function_exists('curl_file_create')) {
			return curl_file_create($filename, $contentType, $postname);
		}
	 
		$value = "@{$filename};filename=" . $postname;
		if ($contentType) {
			$value .= ';type=' . $contentType;
		}
	 
		return $value;
	}
	
	function get_result($url) {
		$answer = trim(http_get($url));
		
		$_rest = explode("|", $answer);	
		$ok = trim($_rest[0]);	
		$token = trim($_rest[2]);			
		$elapsed = trim($_rest[1]);
			
		if (empty($token) && $ok == 'OK') {
			$answer = trim(get_result($url));
		}
		
		return $answer;
	}
	
	if (strtolower(trim($_REQUEST['action'])) == "get") {			
		$base64 = $api->get_base64($_REQUEST['id']);
		$recaptcha = empty($api->get_recaptcha_id($_REQUEST['id'])) ? 0 : $api->get_recaptcha_id($_REQUEST['id']);
		$hcaptcha = empty($api->get_hcaptcha_id($_REQUEST['id'])) ? 0 : $api->get_hcaptcha_id($_REQUEST['id']);
		
		if ($recaptcha == 1) {
			$url = 'https://api.captchas.io/reseller/recaptcha_result?key='.$key.'&captcha_id=' . $_REQUEST['id'];
			//$answer = trim(get_result($url));		
			$answer = trim(http_get($url));			
			$_rest = explode("|", $answer);	
			$token = trim($_rest[2]);			
			$elapsed = trim($_rest[1]);						
			
			if ($answer == "CAPCHA_NOT_READY") {				
				$data = json_encode(array('recaptcha' => 1, 'answer' => 'CAPCHA_NOT_READY', 'base64' => NULL));
				$api->set_request_data($data, trim($_REQUEST['id']), 0);			
				
				//print 'CAPCHA_NOT_READY';
				if ($_json == 1) {
					$return = array('status' => 0, 'request' => 'CAPCHA_NOT_READY');
					$json_return = json_encode($return);
					header('Content-Type: application/json');
					print $json_return;
				} else {
					print 'CAPCHA_NOT_READY';	
				}				
			} else if ($answer == 'ERROR_CAPTCHA_UNSOLVABLE' || $answer == 'ERROR_WRONG_CAPTCHA_ID') {			
				$data = json_encode(array('recaptcha' => 1, 'answer' => 'ERROR_CAPTCHA_IS_UNSOLVABLE', 'base64' => NULL));
				$api->set_request_data($data, trim($_REQUEST['id']), 2);			
				
				//print 'ERROR_CAPTCHA_IS_UNSOLVABLE';				
				if ($_json == 1) {
					$return = array('status' => 0, 'request' => 'ERROR_CAPTCHA_IS_UNSOLVABLE');
					$json_return = json_encode($return);
					header('Content-Type: application/json');
					print $json_return;
				} else {
					print 'ERROR_CAPTCHA_IS_UNSOLVABLE';	
				}				
			} else {
				if (empty($token) && ($answer == 'ERROR_CAPTCHA_UNSOLVABLE' || $answer == 'ERROR_WRONG_CAPTCHA_ID' || $answer == 'CAPCHA_NOT_READY' || $answer == 'ERROR')) {
					$error = 1;
					print $answer;
				} else {
					$error = 0;
					
					if ($_json == 1) {
						$return = array('status' => 1, 'request' => $token);
						$json_return = json_encode($return);
						header('Content-Type: application/json');
						print $json_return;
					} else {
						print 'OK|'. $token;	
					}
				}		

				$displayed = $api->is_displayed($_REQUEST['id']);	
				
				if ($displayed == 0 && $error == 0) {
					$user = http_get('https://api.captchas.io/reseller/get_user?key='.$key.'&user_key='.$_user_key);
					$user = json_decode($user, TRUE);
					
					$rate = $api->recaptcha_rate();
					$credits = $user['credits'] - $rate;
					
					http_get('https://api.captchas.io/reseller/update_user?key='.$key.'&user_id='.$user['id'].'&credits='.$credits);
				}
				
				$data = json_encode(array('recaptcha' => 1, 'answer' => $token, 'base64' => NULL, 'displayed' => 1));
				
				$api->set_request_status(trim($_REQUEST['id']), 1);
				$api->set_request_data($data, trim($_REQUEST['id']), 1);				
			}				
		} else if ($hcaptcha == 1) {
			$url = 'https://api.captchas.io/reseller/hcaptcha_result?key='.$key.'&captcha_id=' . $_REQUEST['id'];
			//$answer = trim(get_result($url));		
			$answer = trim(http_get($url));			
			$_rest = explode("|", $answer);	
			$token = trim($_rest[2]);			
			$elapsed = trim($_rest[1]);						
			
			if ($answer == "CAPCHA_NOT_READY") {				
				$data = json_encode(array('hcaptcha' => 1, 'answer' => 'CAPCHA_NOT_READY', 'base64' => NULL));
				$api->set_request_data($data, trim($_REQUEST['id']), 0);			
				
				//print 'CAPCHA_NOT_READY';
				if ($_json == 1) {
					$return = array('status' => 0, 'request' => 'CAPCHA_NOT_READY');
					$json_return = json_encode($return);
					header('Content-Type: application/json');
					print $json_return;
				} else {
					print 'CAPCHA_NOT_READY';	
				}				
			} else if ($answer == 'ERROR_CAPTCHA_UNSOLVABLE' || $answer == 'ERROR_WRONG_CAPTCHA_ID') {			
				$data = json_encode(array('hcaptcha' => 1, 'answer' => 'ERROR_CAPTCHA_IS_UNSOLVABLE', 'base64' => NULL));
				$api->set_request_data($data, trim($_REQUEST['id']), 2);			
				
				//print 'ERROR_CAPTCHA_IS_UNSOLVABLE';				
				if ($_json == 1) {
					$return = array('status' => 0, 'request' => 'ERROR_CAPTCHA_IS_UNSOLVABLE');
					$json_return = json_encode($return);
					header('Content-Type: application/json');
					print $json_return;
				} else {
					print 'ERROR_CAPTCHA_IS_UNSOLVABLE';	
				}				
			} else {
				if (empty($token) && ($answer == 'ERROR_CAPTCHA_UNSOLVABLE' || $answer == 'ERROR_WRONG_CAPTCHA_ID' || $answer == 'CAPCHA_NOT_READY' || $answer == 'ERROR')) {
					$error = 1;
					print $answer;
				} else {
					$error = 0;
					
					if ($_json == 1) {
						$return = array('status' => 1, 'request' => $token);
						$json_return = json_encode($return);
						header('Content-Type: application/json');
						print $json_return;
					} else {
						print 'OK|'. $token;	
					}
				}		

				$displayed = $api->is_displayed($_REQUEST['id']);	
				
				if ($displayed == 0 && $error == 0) {
					$user = http_get('https://api.captchas.io/reseller/get_user?key='.$key.'&user_key='.$_user_key);
					$user = json_decode($user, TRUE);
					
					$rate = $api->hcaptcha_rate();
					$credits = $user['credits'] - $rate;
					
					http_get('https://api.captchas.io/reseller/update_user?key='.$key.'&user_id='.$user['id'].'&credits='.$credits);
				}
				
				$data = json_encode(array('recaptcha' => 1, 'answer' => $token, 'base64' => NULL, 'displayed' => 1));
				
				$api->set_request_status(trim($_REQUEST['id']), 1);
				$api->set_request_data($data, trim($_REQUEST['id']), 1);				
			}				
		} else {
			$url = 'https://api.captchas.io/reseller/image_result?key='.$key.'&captcha_id=' . $_REQUEST['id'];
			//$answer = trim(get_result($url));
			$answer = trim(http_get($url));
			
			$_rest = explode("|", $answer);	
			$token = trim($_rest[2]);			
			$elapsed = trim($_rest[1]);	
			$response = trim($_rest[0]);
						
			if ($answer == "CAPCHA_NOT_READY") {
				$data = json_encode(array('recaptcha' => 0, 'answer' => 'CAPCHA_NOT_READY', 'base64' => $base64));
				$api->set_request_data($data, trim($_REQUEST['id']), 0);			
				
				//print 'CAPCHA_NOT_READY';	
				if ($_json == 1) {
					$return = array('status' => 0, 'request' => 'CAPCHA_NOT_READY');
					$json_return = json_encode($return);
					header('Content-Type: application/json');
					print $json_return;
				} else {
					print 'CAPCHA_NOT_READY';	
				}				
			} else if ($answer == 'ERROR_CAPTCHA_UNSOLVABLE' || $answer == 'ERROR_WRONG_CAPTCHA_ID') {
				$data = json_encode(array('recaptcha' => 0, 'answer' => 'ERROR_CAPTCHA_IS_UNSOLVABLE', 'base64' => $base64));
				$api->set_request_data($data, trim($_REQUEST['id']), 2);			
				
				//print 'ERROR_CAPTCHA_IS_UNSOLVABLE';	
				if ($_json == 1) {
					$return = array('status' => 0, 'request' => 'ERROR_CAPTCHA_IS_UNSOLVABLE');
					$json_return = json_encode($return);
					header('Content-Type: application/json');
					print $json_return;
				} else {
					print 'ERROR_CAPTCHA_IS_UNSOLVABLE';	
				}				
			} else {
				if (empty($token) && ($answer == 'ERROR_CAPTCHA_UNSOLVABLE' || $answer == 'ERROR_WRONG_CAPTCHA_ID' || $answer == 'CAPCHA_NOT_READY' || $answer == 'ERROR')) {
					$error = 1;
					print $answer;						
				} else {
					$error = 0;
					
					if ($_json == 1) {
						$return = array('status' => 1, 'request' => $token);
						$json_return = json_encode($return);
						header('Content-Type: application/json');
						print $json_return;
					} else {
						print 'OK|'. $token;	
					}					
				}	

				$displayed = $api->is_displayed($_REQUEST['id']);

				if ($displayed == 0 && $error == 0) {
					$user = http_get('https://api.captchas.io/reseller/get_user?key='.$key.'&user_key='.$_user_key);
					$user = json_decode($user, TRUE);
					
					$rate = $api->image_rate();
					$credits = $user['credits'] - $rate;
					
					http_get('https://api.captchas.io/reseller/update_user?key='.$key.'&user_id='.$user['id'].'&credits='.$credits);
				}

				$data = json_encode(array('recaptcha' => 0, 'answer' => $token, 'base64' => $base64, 'displayed' => 1));
				
				$api->set_request_status(trim($_REQUEST['id']), 1);
				$api->set_request_data($data, trim($_REQUEST['id']), 1);				
			}	
		}
	} else if (strtolower(trim($_REQUEST['action'])) == "getbalance") {
		$user = http_get('https://api.captchas.io/reseller/get_user?key='.$key.'&user_key='.$_user_key);
		$user = json_decode($user, TRUE);
		
		$credits = $user['credits'];

		if ($_json == 1) {
			$return = array('status' => 1, 'request' => $credits);			
			$json_return = json_encode($return);
			
			@header('Content-Type: application/json');
			
			print $json_return;
		} else {
			print 'OK|'. $credits;	
		}		
	} else if (strtolower(trim($_REQUEST['action'])) == "userinfo") {
		$user = http_get('https://api.captchas.io/reseller/get_user?key='.$key.'&user_key='.$_user_key);
		$user = json_decode($user, TRUE);
		
		if (!empty($user['email'])) {
			$credits = $user['credits'];
		
			$return = array('email' => $user['email'], 'user_id' => 1, 'valute' => 'USD', 'balance' => $credits, 'key_type' => 'customer');
			$json_return = json_encode($return);
			
			@header('Content-Type: application/json');		
			print $json_return;			
		} else {
			print 'ERROR_API_KEY_NOT_FOUND';
		}		
	}
?>