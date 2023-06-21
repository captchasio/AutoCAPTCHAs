<?
			function _http_post($data, $url) {			
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_POST, TRUE);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);			
				curl_setopt($ch, CURLOPT_TCP_NODELAY, TRUE);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type: multipart/form-data", "Cookie: test=cookie"));
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE); 			
				
				$raw = curl_exec($ch);
				curl_close($ch);			
				
				return $raw;
			}
			
			function _http_get($url) {			
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, FALSE);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: test=cookie"));
				curl_setopt($ch, CURLOPT_TIMEOUT, 30);
				curl_setopt($ch, CURLOPT_TCP_NODELAY, TRUE);
				curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.1.2) Gecko/20090729 Firefox/3.5.2 GTB5');
				
				$raw=curl_exec($ch);
				curl_close($ch);		
				
				return $raw;
			}				
		
			$post = array(
				'key' => 'a0dce715-648c2c8881e5a4.33821112',
				'method' => 'userrecaptcha',
				'googlekey' => '6LcTV7IcAAAAAI1CwwRBm58wKn1n6vwyV1QFaoxr',
				'pageurl' => 'https://login.coinbase.com/',
				'version' => 3,
				'enterprise' => 1,
				'invisible' => 1
			);
			
			$_in = _http_post($post, "http://api.captchas.io/in.php");

			$_rest = explode("|", $_in);
			$_id = trim($_rest[1]);
			$captcha = "";
			$_ans = NULL;												
			
			while (empty($_ans) || $_ans == 'CAPCHA_NOT_READY') {
					$_ans = _http_get("http://api.captchas.io/res.php?key=a0dce715-648c2c8881e5a4.33821112&action=get&id=" . $_id);
					$_rest = explode("|", $_ans);
					$captcha = trim($_rest[1]);
			}	
			
			print $captcha;
?>
