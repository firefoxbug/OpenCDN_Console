<?php
/*Twwy's art*/
if(!preg_match('/\?.+$/', $_SERVER['REQUEST_URI'], $match)) header('Location: ./?default');
$uri = substr($match[0], 1);		//cut ?

/*数据库*/
require('./database.php');
$db = new database;

/*路由*/
$router = Array();
function router($path, $func){
	global $router;
	$router[$path] = $func;
}

/*视图*/
function view($page, $data = Array(), $onlyBody = false){
	foreach ($data as $key => $value) $$key = $value;
	if($onlyBody) return require("./view/{$page}");
	require("./view/header.html");
	require("./view/{$page}");
	require("./view/footer.html");
}

/*会话*/
session_start();

/*JSON格式*/
function json($result, $value){
	if($result) exit(json_encode(array('result' => true, 'data' => $value)));
	exit(json_encode(array('result' => false, 'msg' => $value)));
}

/*POST过滤器*/	//符合rule返回字符串，否则触发callback，optional为真则返回null
function filter($name, $rule, $callback, $optional = false){
	if(isset($_POST[$name]) && preg_match($rule, $post = iconv('UTF-8', 'GB2312//IGNORE', trim($_POST[$name])))) return iconv('GB2312', 'UTF-8//IGNORE', $post);
	elseif(!$optional){
		if(is_object($callback)) return $callback();
		else json(false, $callback);
	}
	return null;
}

/*模型*/
class model{
	function db(){
		global $db;
		return $db;
	}
}//model中转db类
function model($value){
	require("./model/{$value}.php");
	return new $value;
}

/*扩展函数*/
require('common.php');

/*================路由表<开始>========================*/

router('default',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){});
	if($user_id) header('Location: ./?domain');

	view('default.html', array(), true);
});

router('reg',function(){
	$user = model('user');
	if($user->regAccess()) view('reg.html', array(), true);
	else exit('禁止注册');
});

router('api:reg',function(){
	$mail = filter('mail', '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', '邮箱格式不符');
	$pass = filter('pass', '/^.{6,30}$/', '密码需要为6-30位字符');

	$user = model('user');
	if(!$user->regAccess()) json(false, '禁止注册');
	$find = $user->find($mail);
	if(!empty($find)) json(false, '该邮箱已注册');
	$user_id = $user->creat($mail, $pass);

	$_SESSION['user_id'] = $user_id;
	json(true, '注册成功');

});

router('api:login', function(){
	$mail = filter('mail', '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', '邮箱格式不符');
	$pass = filter('pass', '/^.{6,30}$/', '密码需要为6-30位字符');

	$user = model('user');
	$find = $user->find($mail);
	if(empty($find)) json(false, '邮箱不存在，登陆失败');
	$enPass = $user->passEncode($pass, $find['salt']);
	if(strcasecmp($enPass, $find['passwd']) !== 0) json(false, '邮箱或密码错误，登陆失败');

	$_SESSION['user_id'] = $find['user_id'];
	json(true, '登陆成功');
});

router('node',function(){
	$user = model('user');
	$user_id = $user->sessionCheck();

	$node = model('node');
	$data = array();
	$info = $user->get($user_id);
	$data['list'] = $node->nodeList();
	$data['info'] = $info;
	view('node.html', $data);
});

router('api:nodeAdd',function(){
	$user = model('user');
	$user->sessionCheck(function(){
		json(false, '未登录');
	});

	$ip =  filter('ip', '/^([0-9]{1,3}.){3}[0-9]{1,3}$/', '节点IP格式错误');
	$node = model('node');

	$find = $node->find($ip);
	if(!empty($find)) json(false, '节点IP重复');

	$node->creat($ip);
	$list = $node->nodeList();
	$ipArray = array();
	foreach ($list as $key => $value) $ipArray[] = $value['NodeIP'];
	$node->confUpdate($ipArray);
	json(true, '节点添加成功');
});

router('api:nodeRemove',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$id =  filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');
	$node = model('node');

	$info = $user->get($user_id);
	if($info['auth'] != 1) json(false, '没有操作权限');

	$node->remove($id);
	$list = $node->nodeList();
	$ipArray = array();
	foreach ($list as $key => $value) $ipArray[] = $value['NodeIP'];
	$node->confUpdate($ipArray);
	json(true, '节点删除成功');
});

router('api:nodeName',function(){
	$user = model('user');
	$user->sessionCheck(function(){
		json(false, '未登录');
	});

	$id =  filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');
	$name =  filter('name', '/^[\x{a1}-\x{ff}a-zA-Z0-9\-]+$/', '节点名格式错误');
	$name = mb_substr($name, 0, 100, 'utf-8');
	$node = model('node');

	$node->update(array('node_name' => $name), "node_id = $id");
	json(true, '节点名字更改成功');
});

router('nodeInfo:([0-9]{1,6})',function($matches){
	$user = model('user');
	$user->sessionCheck();
	/*echo $matches[1];*/
	$node = model('node');
	$data = $node->find($matches[1]);
	view('nodeInfo.html', $data);
});

router('domain',function(){
	$user = model('user');
	$user_id = $user->sessionCheck();

	$domain = model('domain');
	$data = array();
	$data['list'] = $domain->domainList(0,0, "user_id = {$user_id}");

	$node = model('node');
	$nodeList = $node->nodeList(0, 0, true);
	$iparray = array();

	$info = $user->get($user_id);
	if(empty($info)) exit('该ID信息不存在');
	$data['info'] = $info;

	$data['node'] = $nodeList;
	view('domain.html', $data);
});

router('api:domainAdd',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$captcha = filter('captcha', '/^[a-zA-z]{1,8}$/', '验证码格式错误');
	$ip =  filter('ip', '/^([0-9]{1,3}.){3}[0-9]{1,3}(\:[0-9]{1,6})?$/', '源站IP格式错误');
	$domain =  filter('domain', '/^[a-zA-z0-9\-\.]+$/', '域名格式错误');

	$doModel = model('domain');
	$domainEle = $doModel->domainList(0,0, "domain_name = '{$domain}'");
	if(count($domainEle) != 0) json(false, '该域名已存在,无法添加');

	//源站IP切割
	$ip = explode(':', $ip);
	if(count($ip) == 1) $port = 80;
	else $port = $ip[1];
	$ip = $ip[0];
	if(empty($port)) json(false, '端口填写错误');

	$node = model('node');
	$nodeList = $node->nodeList(0, 0, true);
	if(empty($nodeList)) json(false, '无可用加速节点');
	$iparray = array();
	foreach ($nodeList as $key => $value) $iparray[] = $value['NodeIP'];
	$ips = implode(',', $iparray);

	$cname = $doModel->cnameGet($ips, $captcha);
	if(!is_array($cname)) json(false, $cname);

	$add = $doModel->creat(strtolower($domain), $cname['domain'], $cname['token'], $user_id, $ip, $ips, $port);
	if(!$add) json(false, '插入失败');

	//判断是否可以DNSPod操作
	$dnspod = model('dnspod');
	$user = $user->get($user_id);
	if(!empty($user['dnspod_user'])){
		$dnspod->user = $user['dnspod_user'];
		$dnspod->pass = $user['dnspod_pass'];
		$is_dnspod = $dnspod->domain(strtolower($domain), $add);
	}

	//写入vhost默认规则
	$doModel->confAdd($add, '全局缓存', '/', 'dir', 2, 0);
	$doModel->confAdd($add, '排除动态脚本', 'php|jsp|cgi|asp|aspx|flv|swf|xml|do|rar|zip|rmvb|mp3|doc|docx|xls|pdf|gz|tgz|rm|exe', 'file', 0, 1);
	$doModel->confUpate($add, $domain, $ip, $port);
	json(true, '域名添加成功');
});

router('api:domainCaptcha',function(){
	$user = model('user');
	$user->sessionCheck(function(){
		json(false, '未登录');
	});

	$domain = model('domain');
	header('Content-type: image/png');
	echo $domain->captchaGet();
});

router('api:domainSourceEdit',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$ip =  filter('ip', '/^([0-9]{1,3}.){3}[0-9]{1,3}(\:[0-9]{1,6})?$/', '源站IP格式错误');
	$id = filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');

	$domain = model('domain');
	$domainEle = $domain->domainList(0,0, "domain_id = {$id}");
	if(count($domainEle) == 0) json(false, 'ID不存在');
	$domainEle = $domainEle[0];
	if($domainEle['user_id'] != $user_id) json(false, '不能操作他人的域名');

	//源站IP切割
	$ip = explode(':', $ip);
	if(count($ip) == 1) $port = 80;
	else $port = $ip[1];
	$ip = $ip[0];
	if(empty($port)) json(false, '端口填写错误');

	$result = $domain->update(array('source_ip' => $ip, 'source_port' => $port), "domain_id = {$id}");
 	if($result == false) json(false, '数据库操作失败');

 	$domain->confUpate($id, $domainEle['domain_name'], $ip, $port);
 	json(true, '源站IP更改成功');
});

router('api:domain2ip',function(){
	$user = model('user');
	$user->sessionCheck(function(){
		json(false, '未登录');
	});

	$domain =  filter('domain', '/^[a-zA-z0-9\-\.]+$/', '域名格式错误');

	json(true, gethostbyname($domain));
});

router('api:domainRemove',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$id =  filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');
	//$id = 14;

	$domain = model('domain');
	$domainEle = $domain->domainList(0,0, "domain_id = {$id}");
	if(count($domainEle) == 0) json(false, 'ID不存在');
	$domainEle = $domainEle[0];
	if($domainEle['user_id'] != $user_id) json(false, '不能操作他人的域名');

	$domain->cnameDel($domainEle['cname_domain'], $domainEle['token']);	//删CNAME
	$domain->remove($domainEle['domain_id']);		//删数据库域名
	$domain->confRemoveDomain($domainEle['domain_id']);	//删数据库的规则
	$domain->confFileRemove($domainEle['domain_name']);	//删配置文件

	json(true, '删除成功');
});

router('api:domainDnspodCname',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$id =  filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');

	$domain = model('domain');
	$domainEle = $domain->domainList(0,0, "domain_id = {$id}");
	if(count($domainEle) == 0) json(false, 'ID不存在');
	$domainEle = $domainEle[0];
	if($domainEle['user_id'] != $user_id) json(false, '不能操作他人的域名');
	if($domainEle['dnspod'] == 0) json(false, 'DNSPod不能操作');

 	$dnspod = model('dnspod');
	$user = $user->get($user_id);
	if(empty($user['dnspod_user'])) json(false, '未绑定DNSPod账号');
	$dnspod->user = $user['dnspod_user'];
	$dnspod->pass = $user['dnspod_pass'];
 	$result = $dnspod->cname($domainEle['dnspod'], $domainEle['domain_name'], $domainEle['cname_domain']);
 	if($result == false) json(false, 'DNSPod操作失败');
 	json(true, 'DNSPod操作成功,请等待生效');
});

router('api:domainDnspodA',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$id =  filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');

	$domain = model('domain');
	$domainEle = $domain->domainList(0,0, "domain_id = {$id}");
	if(count($domainEle) == 0) json(false, 'ID不存在');
	$domainEle = $domainEle[0];
	if($domainEle['user_id'] != $user_id) json(false, '不能操作他人的域名');
	if($domainEle['dnspod'] == 0) json(false, 'DNSPod不能操作');

 	$dnspod = model('dnspod');
	$user = $user->get($user_id);
	if(empty($user['dnspod_user'])) json(false, '未绑定DNSPod账号');
	$dnspod->user = $user['dnspod_user'];
	$dnspod->pass = $user['dnspod_pass'];

	$node = model('node');
	$nodeList = $node->nodeList(0, 0, true);
	if(empty($nodeList)) json(false, '无可用加速节点');
	$iparray = array();
	foreach ($nodeList as $key => $value) $iparray[] = $value['NodeIP'];

 	$result = $dnspod->a($domainEle['dnspod'], $domainEle['domain_name'], $iparray);
 	if($result == false) json(false, 'DNSPod操作失败');
 	json(true, 'DNSPod操作成功,请等待生效');
});

router('api:domainCheck',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$id =  filter('id', '/^[0-9]{1,8}$/', 'ID格式错误');
	//$id = 224;

	$domain = model('domain');
	$domainEle = $domain->domainList(0,0, "domain_id = {$id}");
	if(count($domainEle) == 0) json(false, 'ID不存在');
	$domainEle = $domainEle[0];

	//判断是否可以DNSPod操作
	$is_dnspod = false;
	$dnspod = model('dnspod');
	$user = $user->get($user_id);
	if(!empty($user['dnspod_user'])){
		$dnspod->user = $user['dnspod_user'];
		$dnspod->pass = $user['dnspod_pass'];
		$is_dnspod = $dnspod->domain($domainEle['domain_name'], $id);
	}

	//查询CNAME记录是否存在
	$cname = dns_cname($domainEle['domain_name']);
	if(count($cname) != 0){
		if($cname[0]['target'] == $domainEle['cname_domain']){	//CNAME生效
			$update = $domain->update(array('status' => 'cname'), "domain_id = {$id}");
			if($update === false) json(false, '数据库操作失败');
			json(true, array('status' => 'cname', 'dnspod' => $is_dnspod));
		}else{		//CNAME指向错误
			$update = $domain->update(array('status' => 'invalid'), "domain_id = {$id}");
			if($update === false) json(false, '数据库操作失败');
			json(true, array('status' => 'invalid', 'dnspod' => $is_dnspod));
		}
	}

	//查询A记录是否存在
	$node = model('node');
	$nodeList = $node->nodeList(0, 0, true);
	$iparray = array();
	foreach ($nodeList as $key => $value) $iparray[$value['NodeIP']] = false;
	$a = dns_a($domainEle['domain_name']);
	foreach ($a as $key => $value){
		if(isset($iparray[$value['ip']])) $iparray[$value['ip']] = true;
	}
	$valid = array();
	foreach ($iparray as $key => $value){
		if($value == true) $valid[] = $key;
	}
	if(count($valid) == 0){
		$update = $domain->update(array('status' => 'invalid'), "domain_id = {$id}");
		if($update === false) json(false, '数据库操作失败');
		json(true, array('status' => 'invalid', 'dnspod' => $is_dnspod));		
	}
	$validstr = implode(',', $valid);
	$update = $domain->update(array('a_ip' => $validstr, 'status' => 'a'), "domain_id = {$id}");
	if($update === false) json(false, '数据库操作失败');

	json(true, array('status' => 'a', 'ip' => $valid, 'dnspod' => $is_dnspod));	

});

router('api:mailChange',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$mail = filter('mail', '/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix', '邮箱格式不符');
	//$mail = '372894402@qq.com';

	$info = $user->get($user_id);
	if($info['mail'] == $mail) json(false, '与原邮箱相同');

	$user->mailChange($user_id, $mail);
	json(true, '修改链接已发送至新邮箱,请至邮箱确认生效');
});

router('api:dnspodBind',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$user = filter('user', '/^.{1,255}$/', '账号格式不符');
	$pass = filter('pass', '/^.{1,100}$/', '密码格式不符');

	if($pass == 'opencdnOPENCDN') json(false, '未进行绑定');

	$dnspod = model('dnspod');
	$dnspod->user = $user;
	$dnspod->pass = $pass;
	$result = $dnspod->bind($user_id);
	if($result === false) json(false, '绑定失败，请检查账号');
	json(true, '绑定成功');
});

router('api:passwdChange',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$oldPass = filter('oldPass', '/^.{1,100}$/', '当前密码格式不符');
	$newPass = filter('newPass', '/^.{1,100}$/', '新密码格式不符');

	$info = $user->get($user_id);
	//旧密码核对
	$enPass = $user->passEncode($oldPass, $info['salt']);
	if(strcasecmp($enPass, $info['passwd']) !== 0) json(false, '当前密码错误');
	$result = $user->passwdUpdate($user_id, $newPass);
	if($result === false) json(false, '修改失败');
	json(true, '修改成功');


});

router('domainSet:([0-9]{1,6})',function($matches){
	$user = model('user');
	$user_id = $user->sessionCheck();
	$domain_id = $matches[1];

	$domain = model('domain');
	$list = $domain->confList(0, 0, "domain_id = '{$domain_id}'");
	$ele = $domain->domainList(0, 0, "domain_id = '{$domain_id}'");
	if($ele[0]['user_id'] != $user_id) exit('不能操作他人的域名');

	$data = array('domain_id' => $domain_id, 'list'	=> $list, 'info'=> $ele[0]);
	view('domainSet.html', $data);
});

router('api:domainRuleAdd',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$name = filter('name', '/^[\x{a1}-\x{ff}a-zA-Z0-9\-]{0,100}$/', '备注只能为数字英文和中文');
	$rule_type = filter('rule_type', '/^dir|file$/', '规则类型格式不符');
	$rule = filter('rule', '/^[a-zA-Z0-9_\/\-\.\|]{1,255}$/', '规则格式不符(a-zA-Z_.-/|)');
	$is_cache = filter('is_cache', '/^yes|no$/', '是否缓存格式不符');
	$cache = filter('cache', '/^[0-9]{0,4}$/', '缓存天数格式不符');
	$weight = filter('weight', '/^[0-9]{1,4}$/', '权重格式不符0-9999');
	$domain_id = filter('domain_id', '/^[0-9]{1,10}$/', '域名ID格式不符');

	if($is_cache == 'no') $cache = 0;

	$domain = model('domain');
	$ele = $domain->domainList(0, 0, "domain_id = {$domain_id}");
	if(count($ele) == 0) json(false, '域名ID不存在');
	if($ele[0]['user_id'] != $user_id) json(false, '不能操作他人的域名');

	if($domain->confRepeat($domain_id, $rule, $rule_type)) json(false, '和原规则重复');
	$conf_id = $domain->confAdd($domain_id, $name, $rule, $rule_type, $cache, $weight);
	if($conf_id === false) json(false, '添加失败');

	$domain->confUpate($domain_id, $ele[0]['domain_name'], $ele[0]['source_ip']);
	json(true, '添加成功');

});

router('api:domainRuleRemove',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$rule_id = filter('rule_id', '/^[0-9]{1,10}$/', '规则ID格式不符');
	$domain = model('domain');

	$ele = $domain->confList(0, 0, "conf_id = {$rule_id}");
	if(empty($ele)) json(false, '该规则ID不存在');
	$domain_id = $ele[0]['domain_id'];
	$doele = $domain->domainList(0, 0, "domain_id = {$domain_id}");
	if(count($doele) == 0) json(false, '域名ID不存在');
	if($doele[0]['user_id'] != $user_id) json(false, '不能操作他人的域名');

	$result = $domain->confRemove($rule_id);
	if($result === false) json(false, '删除失败');

	$domain->confUpate($domain_id, $doele[0]['domain_name'], $doele[0]['source_ip']);
	json(true, '删除成功');
});

router('profile',function(){
	$user = model('user');
	$user_id = $user->sessionCheck();

	$info = $user->get($user_id);
	if(empty($info)) exit('该ID信息不存在');
	$model = new model();
	$reg = $model->db()->query("SELECT value FROM config WHERE name='reg'", 'row');
	$data = array('info' => $info, 'reg' => $reg['value']);
	view('profile.html', $data);
});

router('task',function(){
	$model = new model;
	$db = $model->db();
	$value = $db->query('SELECT value FROM config WHERE name = \'restart\'', 'row');
	echo $value['value'];
	if($value['value'] == 'true') $db->update('config', array('value' => 'false'), 'name = \'restart\'');

	$node = model('node');
	$doModel = model('domain');
	$nodeList = $node->nodeList(0, 0, true);
	if(empty($nodeList)) exit();
	$iparray = array();
	foreach ($nodeList as $key => $value) $iparray[] = $value['NodeIP'];
	$ips = implode(',', $iparray);

	$domains = $db->query("SELECT * FROM domain WHERE cname_ip != '{$ips}' ORDER BY last_update_time DESC LIMIT 0,10", 'array');

	foreach ($domains as $key => $value) {
		$doModel->cnameUpdate($ips, $value['token'], $value['cname_domain']);
		$db->update('domain', array('cname_ip' => $ips), "domain_id = {$value['domain_id']}");
	}

	//var_dump($domains);

});

router('mail=([a-f0-9]{32})',function($matches){
	$token = $matches[1];
	$user = model('user');
	$result = $user->mailChangeToken($token);
	header('Content-type: text/html; charset=utf-8'); 
	if($result == false) echo '更换邮箱失败';
	else echo '更换邮箱成功！';
});

router('purge:([0-9]{1,6})',function($matches){
	$user = model('user');
	$user_id = $user->sessionCheck();
	$domain_id = $matches[1];

	$domain = model('domain');
	$ele = $domain->domainList(0, 0, "domain_id = '{$domain_id}'");
	if($ele[0]['user_id'] != $user_id) exit('不能操作他人的域名');

	$data = array('domain_id' => $domain_id, 'info'=> $ele[0]);
	view('purge.html', $data);
});

router('api:purge',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$domain_id =  filter('id', '/^[0-9]{1,8}$/', '域名ID格式错误');
	$path = filter('path', '/^.{1,255}$/', '路径格式错误');

	$domain = model('domain');
	$ele = $domain->domainList(0, 0, "domain_id = '{$domain_id}'");
	if($ele[0]['user_id'] != $user_id) json(false, '不能purge操作他人的域名');
	$domain_name = $ele[0]['domain_name'];

	$node = model('node');
	$nodeList = $node->nodeList(0, 0, true);
	//$nodeList = array();
	//$nodeList[0]['NodeIP'] = '198.23.241.130';
	//$nodeList[1]['NodeIP'] = '119.147.0.239';
	$returnArray = array();
	foreach ($nodeList as $key => $value) {
		$fp = @fsockopen($value['NodeIP'], 80, $errno, $errstr, 5);
		if (!$fp) $returnArray[$value['NodeIP']] = false;	
		else{
		    $out = "GET /purge{$path} HTTP/1.1\r\n";
		    $out .= "Host: {$domain_name}\r\n";
		    $out .= "Connection: Close\r\n\r\n";
		    fwrite($fp, $out);
		    //while (!feof($fp)) echo fgets($fp, 128);
		    $returnArray[$value['NodeIP']] = true;
		    fclose($fp);
		}
	}
	json(true, $returnArray);
});

router('api:regConfig',function(){
	$user = model('user');
	$user_id = $user->sessionCheck(function(){
		json(false, '未登录');
	});

	$reg = filter('reg', '/^true|false$/', '全局注册格式错误');

	$info = $user->get($user_id);
	if(empty($info)) json(false, '该用户ID信息不存在');
	if($info['auth'] != 1) json(false, '不是系统账号');

	$model = new model();
	$result = $model->db()->update('config', array('value' => $reg), "name = 'reg'");

	if($result == false) json(false, '数据库操作失败');
	json(true, '修改成功');
});

router('test',function(){
	$nodes = array('198.23.241.130', '119.147.0.239');
	foreach ($nodes as $key => $value) {
		$fp = @fsockopen($value, 80, $errno, $errstr, 5);
		if (!$fp) {
		    echo "$errstr ($errno)<br />\n";
		} else {
		    $out = "GET /purge/?page_id=53 HTTP/1.1\r\n";
		    $out .= "Host: www.firefoxbug.net\r\n";
		    $out .= "Connection: Close\r\n\r\n";
		    fwrite($fp, $out);
		    while (!feof($fp)) {
		        echo fgets($fp, 128);
		    }
		    fclose($fp);
		}
	}
});

router('exit',function(){
	session_destroy();
	exit(header('Location: ./?default'));
});

/*================路由表<结束>========================*/


/*路由遍历*/
foreach ($router as $key => $value){
	if(preg_match('/^'.$key.'$/', $uri, $matches)) exit($value($matches));
}

/*not found*/
echo 'Page not fonud';

?>