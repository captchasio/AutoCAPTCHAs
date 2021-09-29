<?php
require_once('sqlite3.php');

class API {
	private $version = '1.1.6';
	private $db = NULL;
	private $ini = NULL;
	private $settings = NULL;
	private $settings_file = NULL;
	
	function __construct() {
		$this->ini = parse_ini_file(dirname(dirname(__FILE__)) . "/lib/config.ini");
		$this->db = new Database($this->ini["DBHOST"], $this->ini["DBUSERNAME"], $this->ini["DBPASSWORD"], $this->ini["DBNAME"], $this->ini["DBPORT"]);
	}
	
	function get_db() {
		return $this->db;
	}	

	function get_key() {
		return $this->ini["KEY"];
	}
	
	function recaptcha_rate() {
		return $this->ini["RECAPTCHARATE"];
	}

	function image_rate() {
		return $this->ini["IMAGERATE"];
	}
	
	function audio_rate() {
		return $this->ini["AUDIORATE"];
	}
	
	function save_request($id, $answer, $base64, $status, $recaptcha, $key) {		
		$content = json_encode(array('answer' => $answer, 'base64' => $base64, 'recaptcha' => $recaptcha, 'status' => $status));
		
		$recaptcha = empty($recaptcha) ? 0 : $recaptcha;
		
		$this->db->query("REPLACE INTO `requests`(`id`, `key`, `base64`, `answer`, `status`, `recaptcha`) VALUES ('" . $id . "', '" . $key . "', '" . $base64 . "', '" . $answer . "', " . $status . ", " . $recaptcha . ")");	
		
		return $id;
	}	

	function set_request_status($id, $status) {
		$return = $this->db->query("UPDATE `requests` SET `status` = " . $status . " WHERE `id` = '" . $id . "'");
		
		return $return;
	}
	
	function set_request_data($data, $id, $status) {
		$data = json_decode($data, TRUE);
		
		$displayed = empty($data['displayed']) || $data['displayed'] == NULL ? 0 : 1;
		
		$return = $this->db->query("UPDATE `requests` SET 
									`status` = " . $status . ",
									`base64` = '" . $data['base64'] . "',
									`answer` = '" . $data['answer'] . "',
									`recaptcha` = '" . $data['recaptcha'] . "',
									`displayed` = " . $displayed . " 
									WHERE `id` = '" . $id . "'");
		
		return $return;
	}	
	
	function get_request_data($id) {
		if ($id) {
			$result = $this->db->squery("SELECT `id`, `base64`, `answer`, `recaptcha`, `status` FROM `requests` WHERE `id` = '" . $id . "'");
			$data = json_encode($result);
			
			return $data;
		} else {
			return NULL;
		}
	}
	
	function is_displayed($id) {
		$result = $this->db->squery("SELECT * FROM `requests` WHERE `id` = '" . trim($id) . "'");
		
		return $result['displayed'];
	}
	
	function get_answer($id) {
		if ($id) {
			$result = $this->db->squery("SELECT `answer` FROM `requests` WHERE `id` = '" . $id . "'");
			
			return $result['answer'];
		} else {
			return NULL;
		}
	}

	function get_base64($id) {
		if ($id) {
			$result = $this->db->squery("SELECT `base64` FROM `requests` WHERE `id` = '" . $id . "'");
			
			return $result['base64'];
		} else {
			return NULL;
		}
	}
	
	function get_recaptcha_id($id) {
		if ($id) {
			$result = $this->db->squery("SELECT `recaptcha` FROM `requests` WHERE `id` = '" . $id . "'");
			//$data = $result->fetchArray();
			
			return $result['recaptcha'];
		} else {
			return NULL;
		}
	}	
	
	function to_base64($image) {
		$type = pathinfo($image, PATHINFO_EXTENSION);
		$data = file_get_contents($image);
		$base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);	
		
		return $base64;
	}
	
	function upload($captcha) {
		$originalName = $captcha['tmp_name'];
		
		$_jpg = 'jpg';
		$_jpeg = 'jpeg';
		$_jpeg2 = 'pjpeg';
		$_gif = 'gif';
		$_png = 'png';					
		$_bmp = 'bmp';
		
		$types = explode(".", $originalName);
		$type = $types[count($types) - 1];
		
		$_captcha_dir = dirname(__FILE__) . "/data/captchas/";
		$_captcha_file = md5($originalName . time());	
		
		$tail = preg_replace('/(image\/([a-zA-Z]))/is', '$2', $type);
						
		$_captcha_file = $_captcha_dir . $_captcha_file . "." . $tail;
			
		if (move_uploaded_file($captcha['tmp_name'], $_captcha_file)) {
			$_captcha_file = realpath($_captcha_file);

			return $_captcha_file;
		}		
		
		return FALSE;
	}
	
	function solve_image($captcha) {
		$starttime = microtime(true);
		
		$postData=array();
		$postData['method']='post';
		$postData['key']=$this->get_key();
		$postData['file']=$captcha;
		
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL,'http://' . $this->get_server_ip() . ':' . $this->get_server_port() . '/in.php');
		curl_setopt($ch,CURLOPT_HEADER,0);
		curl_setopt($ch,CURLOPT_POST,1);
		curl_setopt($ch,CURLOPT_HTTPHEADER,array('Content-Type: multipart/form-data'));
		curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($ch,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,5);
		curl_setopt($ch,CURLOPT_TIMEOUT,20);
		$_raw=curl_exec($ch);
		curl_close($ch);	
		
		$_rest = explode("|", $_raw);
		$id = trim($_rest[1]);    

		$response = file_get_contents('http://' . $this->get_server_ip() . ':' . $this->get_server_port() . '/res.php?action=get&id='.$id.'&key=' . $this->get_key());

		$answer_raw = explode("|", $response);
		$answer = trim($answer_raw[1]); 
		
		$endtime = microtime(true);
		$elapsed = $endtime - $starttime;
		
		$return = json_encode(array('answer' => $answer, 'captcha' => $captcha, 'elapsed' => $elapsed));
					
		return $return;
	}	
	
	function save_config($array, $path) {
		unset($content, $arrayMulti);

		# See if the array input is multidimensional.
		foreach($array AS $arrayTest){
			if(is_array($arrayTest)) {
			  $arrayMulti = true;
			}
		}

		# Use categories in the INI file for multidimensional array OR use basic INI file:
		if ($arrayMulti) {
			foreach ($array AS $key => $elem) {
				$content .= "[" . $key . "]\n";
				foreach ($elem AS $key2 => $elem2) {
					if (is_array($elem2)) {
						for ($i = 0; $i < count($elem2); $i++) {
							$content .= $key2 . "[] = \"" . $elem2[$i] . "\"\n" . PHP_EOL;
						}
					} else if ($elem2 == "") {
						$content .= $key2 . " = \n" . PHP_EOL;
					} else {
						$content .= $key2 . " = \"" . $elem2 . "\"\n" . PHP_EOL;
					}
				}
			}
		} else {
			foreach ($array AS $key2 => $elem2) {
				if (is_array($elem2)) {
					for ($i = 0; $i < count($elem2); $i++) {
						$content .= $key2 . "[] = \"" . $elem2[$i] . "\"\n" . PHP_EOL;
					}
				} else if ($elem2 == "") {
					$content .= $key2 . " = \n" . PHP_EOL;
				} else {
					$content .= $key2 . " = \"" . $elem2 . "\"\n" . PHP_EOL;
				}
			}
		}

		if (!$handle = fopen($path, 'w')) {
			return false;
		}
		if (!fwrite($handle, $content)) {
			return false;
		}
		fclose($handle);
		return true;
	}	
}	
?>