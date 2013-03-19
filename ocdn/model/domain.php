<?php
/*站点模型*/
class domain extends model{

	public function creat($domain, $cname, $token, $user_id, $source_ip, $ips, $port = 80){
		$insertArray = array(
			'user_id'			=> $user_id,
			'domain_name'		=> $domain,
			'source_ip'			=> $source_ip,
			'cname_domain'		=> $cname,
			'token'				=> $token,
			'last_update_time'	=> time(),
			'cname_ip'			=> $ips,
			'source_port'		=> $port
		);
		//if($dnspod) $insertArray['dnspod'] = true;
		$result = $this->db()->insert('domain', $insertArray);
		if($result === false) return false;		//可能会有问题
		return $this->db()->insertId();
	}

	public function cnameGet($ips, $captcha){
		$result = $this->http('http://opencdn.sinaapp.com/?apply', 
			array('captcha' => $captcha, 'ips' => $ips)
		);
		if($result['result'] === false) return $result['msg'];
		else return $result['data'];
	}

	public function cnameUpdate($ips, $token, $domain){
		$result = $this->http('http://opencdn.sinaapp.com/?update', 
			array('token' => $token, 'domain' => $domain, 'ips' => $ips)
		);
		if($result['result'] === false) return $result['msg'];
		else return $result['data'];
	}

	public function cnameDel($domain, $token){
		$result = $this->http('http://opencdn.sinaapp.com/?delete', 
			array('domain' => $domain, 'token' => $token)
		);
		if($result['result'] === false) return $result['msg'];
		else return $result['data'];
	}

	public function http($url, $param = array(), $cookie = false){
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
		curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.md5($_COOKIE['PHPSESSID']));
		$content = curl_exec($ch);
		curl_close($ch);
		return json_decode($content, true);
	}

	public function captchaGet(){
		$url = 'http://opencdn.sinaapp.com/?captcha';
		$ch = curl_init();
		curl_setopt($ch,CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.md5($_COOKIE['PHPSESSID']));
		$content = curl_exec($ch);
		curl_close($ch);
		return $content;
	}

	public function domainList($start = 0, $limit = 0, $where = ''){
		if($limit == 0 && $start == 0) $limitSql = '';
		else $limitSql = " LIMIT {$start},{$limit} ";
		if(!empty($where)) $where = ' WHERE '.$where;
		$sql = "SELECT * FROM domain {$where} {$limitSql}";
		return $this->db()->query($sql, 'array');
	}

	public function update($updateArray, $where){
		return $this->db()->update('domain', $updateArray, $where);
	}

	public function remove($id){
		$sql = "DELETE FROM domain WHERE domain_id = '{$id}' ";
		return $this->db()->query($sql, 'exec');
	}

	public function confUpate($domain_id, $domain, $sourceip, $sourceport = 80){
		$path = '../conf_rsync/vhost/'.str_replace('.','_', $domain).'.conf';
		//$path = '/usr/share/conf_rsync/vhost/'.str_replace('.','_', $domain).'.conf';
		//$path = '../nginx/conf.d/'.str_replace('.','_', $domain).'.conf';

		$head = "server {\n";
		$head .= "\tlisten 80;\n";
		$head .= "\tserver_name {$domain};\n";
		$head .= "\tgzip on;\n";
		$head .= "\tif (-d \$request_filename) {\n";
		$head .= "\t\trewrite ^/(.*)([^/])$ \$scheme://\$host/$1$2/ permanent;\n";
		$head .= "\t}\n";

		$cacheC = "\t\tif (\$http_Cache_Control ~ \"no-cache\") {\n";
		$cacheC .= "\t\t\trewrite ^(.*)$ /purge$1 last;\n";
		$cacheC .= "\t\t}\n";
		$log = "\t\taccess_log /usr/local/opencdn/pipe/access.pipe access;\n";

		$off = "\t\tproxy_pass	http://{$sourceip}:{$sourceport};\n";
		$off .= "\t\tproxy_redirect off;\n";
		$off .= "\t\tproxy_set_header Host \$host;\n";
		$off .= "\t\tproxy_set_header X-Real-IP \$remote_addr;\n";
		$off .= "\t\tproxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;\n";
		//$off .= "\t\taccess_log	/usr/local/opencdn/pipe/access.pipe access;\n";
		//$off .= "\t\tif (\$http_Cache_Control ~ \"no-cache\") {\n";
		//$off .= "\t\t\trewrite ^(.*)$ /purge$1 last;\n";
		//$off .= "\t\t}\n";

		$on = "\t\tproxy_cache cache_one;\n";
		$on .= "\t\tproxy_cache_valid 200 304 <day>d;\n";
		$on .= "\t\tproxy_cache_key \$host\$uri\$is_args\$args;\n";
		$on .= "\t\tproxy_redirect off;\n";
		$on .= "\t\tproxy_pass http://{$sourceip}:{$sourceport};\n";
		$on .= "\t\tproxy_set_header Host \$host;\n";
		$on .= "\t\tproxy_set_header X-Real-IP \$remote_addr;\n";
		$on .= "\t\tproxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;\n";
		//$on .= "\t\taccess_log /usr/local/opencdn/pipe/access.pipe access;\n";
		$on .= "\t\texpires 2d;\n";
		//$on .= "\t\tif (\$http_Cache_Control ~ \"no-cache\") {\n";
		//$on .= "\t\t\trewrite ^(.*)$ /purge$1 last;\n";
		//$on .= "\t\t}\n";

		$foot = "\tlocation ~ /purge(/.*) {\n";
		$foot .= "\t\tallow all;\n";
		$foot .= "\t\tproxy_cache_purge cache_one \$host\$1\$is_args\$args;\n";
		$foot .= "\t\terror_page 405 =200 /purge\$1;\n";
		$foot .= "\t}\n";
		$foot .="}\n";

		$content = $head;

		$sql = "SELECT * FROM conf WHERE domain_id = '{$domain_id}' ORDER BY weight ASC";
		$confs = $this->db()->query($sql, 'array');
		foreach ($confs as $key => $value) {
			if($value['rule_type'] == 'dir'){
				$content .= "\tlocation {$value['rule']} {\n";
			}elseif($value['rule_type'] == 'file'){
				$content .= "\tlocation ~ .*\.({$value['rule']})$ {\n";
			}else{
				$content .= "\tlocation {$value['rule']} {\n";
			}
			if($value['cache'] > 0){
				$cache = str_replace('<day>', $value['cache'], $on);
				$content .= $cache;
			}else{
				$content .= $off;
			}
			if($value['rule_type'] == 'dir' && $value['rule'] == '/'){
				$content .= $cacheC;
				$content .= $log;
			}
			$content .= "\t}\n";
		}

		$content .= $foot;

		if (!file_exists($path)) touch($path);
		$file = fopen($path, 'w');
		fwrite($file, $content);
		fclose($file); 
	}

	public function confList($start = 0, $limit = 0, $where = ''){
		if($limit == 0 && $start == 0) $limitSql = '';
		else $limitSql = " LIMIT {$start},{$limit} ";
		if(!empty($where)) $where = ' WHERE '.$where;
		$sql = "SELECT * FROM conf {$where} ORDER BY weight ASC {$limitSql}";
		return $this->db()->query($sql, 'array');
	}

	public function confAdd($domain_id, $name, $rule, $ruleType, $cache, $weight = 0){
		$insertArray = array(
			'domain_id'			=> $domain_id,
			'name'				=> $name,
			'rule'				=> $rule,
			'rule_type'			=> $ruleType,
			'cache'				=> $cache,
			'weight'			=> $weight
		);
		$result = $this->db()->insert('conf', $insertArray);
		if($result === false) return false;		//可能会有问题
		return $this->db()->insertId();		
	}

	public function confFileRemove($domain){
		$path = '../conf_rsync/vhost/'.str_replace('.','_', $domain).'.conf';
		//$path = '../nginx/conf.d/'.str_replace('.','_', $domain).'.conf';
		if (file_exists($path)) return unlink($path);
		return false;
	}

	public function confEdit($name, $rule, $ruleType, $cache, $weight = 0){

	}

	public function confRemove($conf_id){
		$sql = "DELETE FROM conf WHERE conf_id = '{$conf_id}' ";
		return $this->db()->query($sql, 'exec');
	}

	public function confRemoveDomain($domain_id){
		$sql = "DELETE FROM conf WHERE domain_id = '{$domain_id}' ";
		return $this->db()->query($sql, 'exec');
	}

	public function confRepeat($domain_id, $rule, $ruleType){
		$sql = "SELECT domain_id FROM conf WHERE domain_id = {$domain_id} AND rule = '{$rule}' AND rule_type = '{$ruleType}'";
		$reuslt = $this->db()->query($sql, 'row');
		if(empty($reuslt)) return false;
		return true;
	}

}


?>