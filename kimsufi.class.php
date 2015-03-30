<?php
class Kimsufi {
	private $models = array(
		'KS-1'     => '%7B%22gamme%22%3A%22150sk10%22%7D',
		'KS-2'     => '%7B%22gamme%22%3A%22150sk20%22%7D',
		'KS-2 SSD' => '%7B%22gamme%22%3A%22150sk22%22%7D',
		'KS-3'     => '%7B%22gamme%22%3A%22150sk30%22%7D',
		'KS-4'     => '%7B%22gamme%22%3A%22150sk40%22%7D',
		'KS-5'     => '%7B%22gamme%22%3A%22150sk50%22%7D',
		'KS-6'     => '%7B%22gamme%22%3A%22150sk60%22%7D',
	);

	private $base = 'https://ws.ovh.com/dedicated/r2/ws.dispatcher/'.
	                'getElapsedTimeSinceLastDelivery?'.
	                'callback=Request.JSONP.request_map.request_1&params=';

	private $url    = null;
	private $model  = null;
	private $apikey = null;
	
	private $debug  = true;
	
	function __construct($model, $key) {
		if(!isset($this->models[$model]))
			die("[-] model not found\n");
			
		$this->url    = $this->base.$this->models[$model];
		$this->apikey = $key;
		$this->model  = $model;
		
		$this->check();
	}
	
	function check() {
		echo "[+][".date('H:i:s')."] checking for ".$this->model."\n";

		if(!($data = file_get_contents($this->url)))
			die("[-] download failed\n");

		$data = substr($data, 36);
		$data = substr($data, 0, -2);
		$data = json_decode($data);

		if($this->debug)
			print_r($data);

		$last = time() - $data->answer;

		echo "[+] last date: ";
		echo date("d/m/Y H:i:s\n", $last);
		
		if(!is_numeric($data->answer) || $data->answer < 1000) {
			echo "[+] kimsufi available !\n";
			$this->notify();
		
		} else echo "[-] kimsufi unavailable\n";
	}
	
	function notify() {
		echo "[+] sending notification\n";
		
		$post = '{"type": "note", "title": "Kimsufi", "body": "'.$this->model.'"}';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.pushbullet.com/v2/pushes'); 
		curl_setopt($ch, CURLOPT_HEADER, false); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Content-Type: application/json',
			'Authorization: Bearer '.$this->apikey
		));
		
		$data = curl_exec($ch);
		curl_close($ch); 
		
		if($this->debug) {
			$data = json_decode($data);
			print_r($data);
			
			sleep(60);
		}
	}
}
?>
