<?php

function random($type = 'str', $length){
	$chars = array('num' => '0123456789', 'str' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890');
	$result = '';
	$chars_length = strlen($chars[$type]) - 1;
	for ($i = 0; $i < $length; $i++){
		if($type == 'num' && $i == 0) $result .= $chars[$type]{rand(1, $chars_length)};
		else $result .= $chars[$type]{rand(0, $chars_length)};
	}
	return $result;
}

function dns_a($domain){
	if(is_windows()){
		$url = "http://opencdn.sinaapp.com/dns.php?domain={$domain}&type=A";
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$content = curl_exec($ch);
		curl_close($ch);
		return json_decode($content, true);
	}else return dns_get_record($domain, DNS_A);
}

function dns_cname($domain){
	if(is_windows()){
		$url = "http://opencdn.sinaapp.com/dns.php?domain={$domain}&type=CNAME";
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		$content = curl_exec($ch);
		curl_close($ch);
		return json_decode($content, true);
	}else return dns_get_record($domain, DNS_CNAME);
}


function is_windows(){
	if(PATH_SEPARATOR ==':') return false;
	return true;
}


?>