<?php
/*
    BaltSMS - SMS Atslēgas vārda sistēma
    BaltSMS ir aplikācija, kura saistās ar baltgroup.eu hostinga un SMS pakalpojumu piedāvātāju. Šo aplikācija drīkst izmantot tikai baltgroup.eu klienti, kuriem ir vajadzīgie dati, lai aizpildītu konfigurāciju un izveidotu savienojumu
    Aplikāciju un pluginus veidoja Miks Zvirbulis
    http://twitter.com/MiksZvirbulis
*/
class baltsms{
	# BaltSMS API Saite uz kuru tiks izsaukts pieprasījums
	protected $baltsms_api_url = "//zb.baltgro.lv/1/";
	# Atbilde
	public $response;
	# Cenas kods
	protected $price_code;
	# Saņemtais atslēgas kods
	protected $code;

	public static function alert($string, $type){
		return '<div class="alert alert-' . $type . '">' . $string . '</div>';
	}

	public static function createTable($plugin, $table){
		global $db;
		if($plugin == "donate"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `name` varchar(32) NOT NULL, `message` varchar(250) NOT NULL, `amount` int(5) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_group"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `mc_group` varchar(25) NOT NULL, `length` int(5) NOT NULL, `time` varchar(10) NOT NULL, `expires` varchar(10) NOT NULL, PRIMARY KEY (`id`) ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_money"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `amount` int(10) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_crate"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_exp"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `exp` int(10) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_fpower"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `power` int(10) NOT NULL, `time` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_fpower-expiry"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `power` int(10) NOT NULL, `time` varchar(10) NOT NULL, `expires` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}elseif($plugin == "mc_fpeaceful"){
			$db->insert("CREATE TABLE `$table` (`id` int(11) NOT NULL AUTO_INCREMENT, `nickname` varchar(55) NOT NULL, `server` varchar(25) NOT NULL, `length` int(5) NOT NULL, `time` varchar(10) NOT NULL, `expires` varchar(10) NOT NULL, PRIMARY KEY (`id`)) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;");
		}
	}

	public static function returnPrice($price_code){
		$price_code = $price_code * 0.01;
		return number_format($price_code, 2, ".", "");
	}

	public static function instructionTemplate($template, $data = array()){
		global $c;
		return str_replace(
			array(
				"<PRICE>",
				"<CODE>",
				"<LENGTH>",
				"<NUMBER>",
				"<KEYWORD>",
				),
			array(
				isset($data['code']) ? '<span id="price">' . $data['price'] . '</span>' : '',
				isset($data['code']) ? '<span id="code">' . $data['code'] . '</span>' : '',
				isset($data['length']) ? '<span id="length">' . $data['length'] . '</span>' : '',
				$c['sms']['number'],
				$c['sms']['keyword']
				),
			$template
			);
	}

	public function setPrice($price_code){
		$this->price_code = $price_code;
	}

	public function setCode($code){
		$this->code = $code;
	}

	private function baltGroupCall($url){
		$bGu['ip'] = isset($_SERVER['HTTP_CF_CONNECTING_IP'])? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];
		$curl = curl_init();
		
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_USERAGENT, 'baltGroupAPI/1.0'); // drošībai, bloķējam liekos pieprasījumus
		curl_setopt($curl, CURLOPT_HTTPHEADER, array( // pievienojam mazu info par apmeklētāju un serveri
			'User-Ip: ' . $bGu['ip'],
			'Server-Ip: ' . $_SERVER['SERVER_ADDR'],
			'Server-Url: ' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
		));
		
		$data = curl_exec($curl);
		curl_close($curl);
		
		return $data;
	}

	public function sendRequest(){
		global $c;
		global $p;
		if($c['sms']['debug'] === true AND $this->code == $c['sms']['debug_code']){
			$debug = fopen("../debug.txt", "a+");
			fwrite($debug, "Debugged at: " . date("d/m/y H:i") . "; Price: " . self::returnPrice($this->price_code) . " EUR; Unlock code: " . $this->code . "; Service: " . $p);
			fwrite($debug, PHP_EOL);
			fclose($debug);
			$this->response['response']['isOk'] = 1;
		}else{
			$this->response = $this->baltGroupCall($baltsms_api_url . 'premiumsms/charge/code/'.$this->code.'/client/'.$c['sms']['client_id'].'/price/' . $this->price_code);
			$this->response = json_decode($this->response, true);
		}
	}

	public function getResponse(){
		global $c;
		if($this->response['response']['isOk'] === 1){
			return true;
		}else{
			return self::alert($c['lang']['lv']['code_unkown_response'] . '<b>' . $this->response['response']['answer'] . '</b>', "danger");
		}
	}
}