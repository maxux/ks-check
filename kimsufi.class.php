<?php
class Kimsufi {
	private $models = array(
		'KS-1'     => '150sk10',
		'KS-2'     => '150sk20',
		'KS-2 SSD' => '150sk22',
		'KS-3'     => '150sk30',
		'KS-4'     => '150sk40',
		'KS-5'     => '150sk50',
		'KS-6'     => '150sk60',
	);

	private $url    = 'https://ws.ovh.com/dedicated/r2/ws.dispatcher/getAvailability2';
	private $model  = null;
	private $apikey = null;
	
	private $debug  = true;
	
	function __construct($model, $key) {
		if(!isset($this->models[$model]))
			die("[-] model not found\n");
			
		$this->apikey = $key;
		$this->model  = $model;
		
		if($this->check()) {
			echo "[+] kimsufi available !\n";
			$this->notify($this->model);
			
		} else echo "[-] kimsufi unavailable\n";
	}
	
	function check() {
		echo "[+][".date('H:i:s')."] checking for ".$this->model."\n";

		if(!($data = file_get_contents($this->url)))
			die("[-] download failed\n");

		$data = json_decode($data);
		
		foreach($data->answer->availability as $item) {
			if(!in_array($item->reference, $this->models))
				continue;
			
			if($item->reference == $this->models[$this->model])
				return $this->parse($item);
		}
		
		die("[-] kimsufi not found\n");
	}
	
	function parse($item) {
		foreach($item->zones as $zone)
			if($zone->availability != 'unknown')
				return true;

		return false;
	}
	
	function notify($model) {
		echo "[+] sending notification\n";
		
		$data = array(
			'type'  => 'note',
			'title' => $model,
			'body'  => $model.' available'
		);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://api.pushbullet.com/v2/pushes'); 
		curl_setopt($ch, CURLOPT_HEADER, false); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
		
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
