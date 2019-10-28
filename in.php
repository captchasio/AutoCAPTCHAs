<?
	ini_set('display_errors', 1);
	error_reporting(E_ERROR);	
	
	require_once('api/system.php');
	
	$api = new API();
	
	$key = $api->get_key();
	
	$_user_key = trim($_REQUEST['key']);
	$_method = trim($_REQUEST['method']);
	$_ip = $_SERVER['REMOTE_ADDR'];
	$_json = $_REQUEST['json'] == 1 ? 1 : 0;
	
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
	
	$credits = http_get('https://api.captchas.io/reseller/get_user_balance?key=' . $key . '&user_key=' . $_user_key);
	
	if ($credits >= 1) {
		if (strtolower(trim($_method)) == 'post') {	
			$size = $_FILES['file']['size'];
			$type = $_FILES['file']['type'];			
			$originalName = $_FILES['file']['name'];													
			
			$jpg = 'image/jpg';
			$jpeg = 'image/jpeg';
			$jpeg2 = 'image/pjpeg';
			$gif = 'image/gif';
			$png = 'image/png';
			
			$_jpg = 'jpg';
			$_jpeg = 'jpeg';
			$_jpeg2 = 'pjpeg';
			$_gif = 'gif';
			$_png = 'png';					
			$_bmp = 'bmp';
			
			$_captcha_dir = dirname(__FILE__) . "/api/data/captchas/";
			$_captcha_file = md5($originalName . time());		
			$md5 = md5($originalName . time());
			$_name = hash_hmac("crc32", $md5, "hUasr8345LKnrjh1");		
			
			$types = explode(".", $originalName);
			$type = $types[count($types) - 1];
			
			$tail = preg_replace('/(image\/([a-zA-Z]))/is', '$2', $type);
			 
			$final_filename = $_captcha_file;
			$filename = $final_filename . "." . $tail;
			$final_filename = $final_filename . "." . $tail;					
			$_captcha_file = $_captcha_dir . $final_filename;						
			
			if (move_uploaded_file($_FILES['file']['tmp_name'], $_captcha_file)) {
				$_captcha_file = realpath($_captcha_file);
				$base64 = to_base64($_captcha_file);
				$_final_file = getCurlValue($_captcha_file, $type, $final_filename);
				@shell_exec("chmod 775 " . $_captcha_file);				
			}																																										

			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, 'https://api.captchas.io/reseller/image_task');
			curl_setopt($ch,CURLOPT_HEADER, FALSE);
			curl_setopt($ch,CURLOPT_POST, TRUE);
			curl_setopt($ch,CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
			curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1a2pre) Gecko/2008073000 Shredder/3.0a2pre ThunderBrowse/3.2.1.8");					
			curl_setopt($ch,CURLOPT_POSTFIELDS, $postData);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION, TRUE);			
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 300);
			curl_setopt($ch,CURLOPT_TIMEOUT, 300);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				'key' => $key,
				'user_key' => $_user_key,
				'body' => $base64
			));			
			$raw = curl_exec($ch);
			curl_close($ch);		
			
			$raw = explode("|", $raw);
			$answer = $raw[2];
			$elapsed = $raw[1];	
						
			$id = $api->save_request($answer, NULL, to_base64($_captcha_file), 0, 0, $_user_key);
			$api->set_request_status($id, 1);
			
			if ($_json == 1) {
				$return = array('status' => 1, 'request' => $id);
				$json_return = json_encode($return);
				header('Content-Type: application/json');
				print $json_return;
			} else {
				print 'OK|'. $id;	
			}				
		} else if (strtolower(trim($_method)) == 'base64') {				
			$captcha = $_REQUEST['body'];
			
			$_captcha_dir = dirname(__FILE__) . "/api/data/captchas/";
			$_file = md5($captcha . time()) . '.jpg';					
			$_captcha_file = $_captcha_dir . $_file;
			$_captcha_file = $_captcha_file . '.jpg';
			
			$filename = $_file;
			
			$_captcha_file = base64_to_file($captcha, $_captcha_file);															
			$_captcha_file = realpath($_captcha_file);
			@shell_exec('chmod 775 ' . $_captcha_file);
			
			$base64 = to_base64($_captcha_file);
			
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL, 'https://api.captchas.io/reseller/image_task');
			curl_setopt($ch,CURLOPT_HEADER, FALSE);
			curl_setopt($ch,CURLOPT_POST, TRUE);
			curl_setopt($ch,CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
			curl_setopt($ch, CURLOPT_USERAGENT,  "Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.9.1a2pre) Gecko/2008073000 Shredder/3.0a2pre ThunderBrowse/3.2.1.8");					
			curl_setopt($ch,CURLOPT_POSTFIELDS, $postData);
			curl_setopt($ch,CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch,CURLOPT_FOLLOWLOCATION, TRUE);			
			curl_setopt($ch,CURLOPT_CONNECTTIMEOUT, 300);
			curl_setopt($ch,CURLOPT_TIMEOUT, 300);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array(
				'key' => $key,
				'user_key' => $_user_key,
				'body' => $base64
			));			
			$raw = curl_exec($ch);
			curl_close($ch);		
			
			$raw = explode("|", $raw);
			$answer = $raw[2];
			$elapsed = $raw[1];				
						
			$id = $api->save_request($answer, NULL, $base64, 0, 0, $_user_key);
			$api->set_request_status($id, 1);
			
			if ($_json == 1) {
				$return = array('status' => 1, 'request' => $id);
				$json_return = json_encode($return);
				header('Content-Type: application/json');
				print $json_return;
			} else {
				print 'OK|'. $id;	
			}				
		} else if (strtolower(trim($_method)) == 'userrecaptcha') {
			$_proxy = urldecode(trim($_REQUEST['proxy']));	
			$_proxy_type = urldecode(trim($_REQUEST['proxy_type']));
				
			$url = 'https://api.captchas.io/reseller/recaptcha_task?key='.$key.'&user_key=' . $_user_key . '&version=' . trim($_REQUEST['version']) . '&min_score=' . trim($_REQUEST['min_score']) . '&method=userrecaptcha&googlekey=' . trim($_REQUEST['googlekey']) . '&pageurl=' . urlencode(urldecode(trim($_REQUEST['pageurl'])));
			$answer = http_get($url);

			$_rest = explode("|", $answer);	
			$token = trim($_rest[2]);
			$elapsed = $_rest[1];
					
			$data = json_encode(array('answer' => '', 'recaptcha' => 1, 'elapsed' => $elapsed, 'token' => NULL, 'images' => array('base64' => NULL)));
						
			$id = $api->save_request($token, NULL, 'CAPCHA_NOT_READY', 0, 1, $_user_key);
		
			if ($_json == 1) {
				$return = array('status' => 1, 'request' => $id);
				$json_return = json_encode($return);
				header('Content-Type: application/json');
				print $json_return;
			} else {
				print 'OK|'. $id;	
			}																					
		} else {			
			print 'ERROR_WRONG_METHOD_VALUE';
		}
	} else {
		print 'ERROR_ZERO_BALANCE';
	}
?>		
