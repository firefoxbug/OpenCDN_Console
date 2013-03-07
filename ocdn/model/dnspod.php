<?php
/*dnspod模型*/
class dnspod extends model{
	public $user;
	public $pass;

	public function bind($user_id){
		if($this->check()){
			return $this->db()->update('user', 
				array('dnspod_user' => $this->user, 'dnspod_pass' => $this->pass, 'dnspod' => 'valid'),
				"user_id = {$user_id}");
		}
		return false;
	}

	public function check(){
		$result = $this->http('https://dnsapi.cn/User.Detail');
		if($result['status']['code'] == 1) return true;
		return false;
	}

	public function http($url, $param = array()){
		$param['format'] = 'json';
		$param['login_password'] = $this->pass;
		$param['login_email'] = $this->user;
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		$content = curl_exec($ch);
		curl_close($ch);
		return json_decode($content, true);
	}

	public function domain($domain_name, $domain_id){
		$domainArray = array_reverse(explode('.', $domain_name));
		if(count($domainArray) > 1){
			$doLast = array_shift($domainArray);
			$testDomain = '';
			foreach ($domainArray as $key => $value) {
				$testDomain = "{$value}.".$testDomain;
				$result = $this->http('https://dnsapi.cn/Domain.Info', array('domain' => "{$testDomain}{$doLast}"));
				if($result['status']['code'] == 1){
					$this->db()->update('domain', array('dnspod' => $result['domain']['id']), "domain_id = {$domain_id}");
					return true;
				}
			}
		}
		$this->db()->update('domain', array('dnspod' => 0), "domain_id = {$domain_id}");
		return false;		
	}

	public function cname($domain_id, $sub_domain, $cname_domain){
		$result = $this->http('https://dnsapi.cn/Domain.Info', array('domain_id' => $domain_id));
		if($result['status']['code'] != 1) return false;
		$mainDomain = $result['domain']['name'];
		if($mainDomain == $sub_domain) $sub_domain = '';
		$sub_domain = str_replace(".{$mainDomain}", '',$sub_domain);
		$result = $this->http('https://dnsapi.cn/Record.List', 
			array('domain_id' => $domain_id, 'offset' => 0, 'length' => 999, 'sub_domain' => $sub_domain)
		);
		if($result['status']['code'] != 10){	//进行删除操作
			if(empty($sub_domain)){
				foreach ($result['records'] as $key => $value) {
					if($value['name'] == '@' && ($value['type'] == 'A' || $value['type'] == 'CNAME')){
						$remove = $this->http('https://dnsapi.cn/Record.Remove',
							array('domain_id' => $domain_id, 'record_id' => $value['id'])
						);
					}
				}
				//$sub_domain = '@';
			}else{
				foreach ($result['records'] as $key => $value) {
					$remove = $this->http('https://dnsapi.cn/Record.Remove',
						array('domain_id' => $domain_id, 'record_id' => $value['id'])
					);
				}
			}
		}
		$param = array(
			'domain_id' => $domain_id,
			'record_type' => 'CNAME',
			'record_line'	=> '默认',
			'value'	=> $cname_domain,
			'mx'	=> 0,
			'ttl'	=> 600
		);
		if(!empty($sub_domain)) $param['sub_domain'] = $sub_domain;
		$add = $this->http('https://dnsapi.cn/Record.Create', $param);
		if($add['status']['code'] != 1) return false;
		return true;
	}

	public function a($domain_id, $sub_domain, $iparray){
		$result = $this->http('https://dnsapi.cn/Domain.Info', array('domain_id' => $domain_id));
		if($result['status']['code'] != 1) return false;
		$mainDomain = $result['domain']['name'];
		if($mainDomain == $sub_domain) $sub_domain = '';
		$sub_domain = str_replace(".{$mainDomain}", '',$sub_domain);
		$result = $this->http('https://dnsapi.cn/Record.List', 
			array('domain_id' => $domain_id, 'offset' => 0, 'length' => 999, 'sub_domain' => $sub_domain)
		);
		if($result['status']['code'] != 10){	//进行删除操作
			if(empty($sub_domain)){
				foreach ($result['records'] as $key => $value) {
					if($value['name'] == '@' && ($value['type'] == 'A' || $value['type'] == 'CNAME')){
						$remove = $this->http('https://dnsapi.cn/Record.Remove',
							array('domain_id' => $domain_id, 'record_id' => $value['id'])
						);
					}
				}
				//$sub_domain = '@';
			}else{
				foreach ($result['records'] as $key => $value) {
					$remove = $this->http('https://dnsapi.cn/Record.Remove',
						array('domain_id' => $domain_id, 'record_id' => $value['id'])
					);
				}
			}
		}
		foreach ($iparray as $key => $value){
			$param = array(
				'domain_id' => $domain_id,
				'record_type' => 'A',
				'record_line'	=> '默认',
				'value'	=> $value,
				'mx'	=> 0,
				'ttl'	=> 600
			);
			if(!empty($sub_domain)) $param['sub_domain'] = $sub_domain;
			$add = $this->http('https://dnsapi.cn/Record.Create', $param);
			//if($add['status']['code'] != 1) return false;
		}
		return true;
	}
}


?>