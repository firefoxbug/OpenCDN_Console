$(document).ready(function(){

	$(".node-edit,.domain-source-edit").click(function(){
		$(this).parent().hide().next().show();
	});

	$(".edit-cancel").click(function(){
		$(this).parent().hide().prev().show();
	});

	$(".nodeName-save").click(function(){
		var form = $(this).parents("form");
		$.post("./?api:nodeName", form.serialize(), function(data,status){
			back = $.parseJSON(data);
			if(!back.result){
				alert(back.msg);
			}else window.location.reload();
		});
	});

	$(".domain-source-save").click(function(){
		var form = $(this).parents("form");
		$.post("./?api:domainSourceEdit", form.serialize(), function(data,status){
			back = $.parseJSON(data);
			if(!back.result){
				alert(back.msg);
			}else window.location.reload();
		});
	});

	$("#domainAdd").click(function(){
		art.dialog({
			follow: $(this)[0],
    		content: '<img src="./?api:domainCaptcha" onclick=\'javascript:this.src="./?api:domainCaptcha"\'/><br><br><input type="text" placeholder="验证码" name="captcha" id="captcha">',
			ok: function () {
				var form = $("#domainForm");
				var postVal = form.serialize() + '&captcha=' + $("#captcha").val();
				$.post("./?api:domainAdd", postVal, function(data,status){
					back = $.parseJSON(data);
					if(!back.result){
						alert(back.msg);
					}else{
						alert(back.data, function(){
							window.location.reload();
						});
					}
				});
  			},
 			init: function () {
				$("#captcha").focus();
			}
		});
	});

	$(".domain-body .icon-info-sign").click(function(){
		$(this).siblings(".domain-info").slideToggle('fast');
	});

	$(".domain-body .icon-refresh").click(function(){
		$(this).hide().siblings(".load").show();
		var refresh = $(this);
		$.post("./?api:domainCheck", {id: $(this).attr("data-id")}, function(data,status){
			back = $.parseJSON(data);
			if(!back.result) return alert(back.msg);

			var tr = refresh.parents("tr");
			if(back.data.status == 'invalid'){	//未生效
				tr.addClass("error");
				tr.children("td").eq(2).addClass("invalid").removeClass("valid");
			}else{
				tr.removeClass("error");
				tr.children("td").eq(2).removeClass("invalid").addClass("valid");
				if(back.data.status == 'cname'){
					tr.children("td").eq(2).find(".well-cname").addClass("record-valid");
					tr.children("td").eq(2).find(".well-a").removeClass("record-valid");
				}else{
					tr.children("td").eq(2).find(".well-cname").removeClass("record-valid");
					tr.children("td").eq(2).find(".well-a").addClass("record-valid");
				}
			}
			refresh.show().siblings(".load").hide();
		});
	});

	$(".default-body").keydown(function(event){
		if(event.keyCode == 13) login();
	});

	$("#nodeForm").keydown(function(event){
		if(event.keyCode == 13) nodeAdd();
	});

	$("#domainForm").keydown(function(event){
		if(event.keyCode == 13) $("#domainAdd").click();
	});

	$(document).on("keydown", "#captcha", function(event){
		if(event.keyCode == 13 && $(this).val() != "") $(".aui_state_highlight").click();
	});

	$("#domainForm input[name='domain']").change( function() {
		var domain = $(this);
		$.post("./?api:domain2ip", {domain: $(this).val()}, function(data,status){
			back = $.parseJSON(data);
			if(!back.result) return;
			if(back.data != domain.val() && back.data != "0.0.0.0"){
				$("#domainForm input[name='ip']").val(back.data+":80");
			}
		});
	});

	$(".domain-body .dnspod-cname").click(function(){
		$(this).hide().siblings(".load").show();
		var dnspod = $(this);
		dnspodCname($(this).attr("data-id"), function(){
			dnspod.show().siblings(".load").hide();
		});
	});

	$(".domain-body .dnspod-a").click(function(){
		$(this).hide().siblings(".load").show();
		var dnspod = $(this);
		dnspodA($(this).attr("data-id"), function(){
			dnspod.show().siblings(".load").hide();
		});
	});

	$("#mailChange").click(function(){
		$("#changeMail").removeAttr("disabled");
		$(this).parents("form").addClass("mail-mode-edit");
	});

	$("#mailChangeCancel").click(function(){
		$("#changeMail").attr("disabled", "");
		$(this).parents("form").removeClass("mail-mode-edit");
	});

	$("#mailChangeSave").click(function(){
		$.post("./?api:mailChange", {mail: $("#changeMail").val()}, function(data,status){
			back = $.parseJSON(data);
			if(!back.result){
				alert(back.msg);
			}else alert(back.data);
		});
	});

	var purgeList = [];
	var lock = true;
	if($(".container-fluid").hasClass("purge-body")){
		setInterval(function(){
			if(purgeList.length > 0){
				if(!lock){
					read = purgeList[0];
					//url补全
					whole = /^http:\/\/.+$/;
					if(whole.exec(read) != null) read = read.replace($("input[name='url']").val(), '');
					if(read.substr(0, 1) != '/') read = '/'+read;
					purgeList.splice(0,1);
					lock = true;
					$("textarea[name='content']").val(purgeList.join('\n'));
					$.post("./?api:purge", {id: $("input[name='domain_id']").val(), path: read}, function(data,status){
						back = $.parseJSON(data);
						if(back.result) $(".msg").append(read+"已清除<br/>");
						else $(".msg").append(read+"已失败("+data.msg+")<br/>");
						lock = false;
					});
				}
			}else{
				$("textarea[name='content']").removeAttr("disabled");
				lock = true;
			}
		}, 500);
	}

	$("#startPurge").click(function(){
		var textarea =  $("textarea[name='content']");
		textarea.attr("disabled", "");
		purgeList = textarea.val().split('\n');
		lock = false;
	});

	$("#stopPurge").click(function(){
		var textarea =  $("textarea[name='content']");
		lock = true;
		textarea.removeAttr("disabled");
	});

	$("#globalReg").change(function(){
		$.post("./?api:regConfig", $("#globalRegForm").serialize(), function(data,status){
			back = $.parseJSON(data);
			if(!back.result){
				alert(back.msg);
			}else{ 
				alert(back.data, function(){
					window.location.reload();
				});
			}
		});
	})
});

//注册
function reg(){
	var form = $("#regForm");
	$.post("./?api:reg", form.serialize(), function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
		}else alert(back.data, function(){
			window.location.href="./?domain";
		});
	});
}


function post(value, url, trueFunc, falseFunc){
	var trueFunc = arguments[2] ? arguments[2] : function(back){ alert(back.data)};
	var falseFunc = arguments[3] ? arguments[3] : function(back){ alert(back.msg)};

	var form = $("#"+value);
	$.post(url, form.serialize(), function(data,status){
		back = $.parseJSON(data);
		if(!back.result) return falseFunc(back);
		return trueFunc(back);
	});		
}

/*
function loginWindow(){
	art.dialog.open('?window:login', {title: '登陆'});
}*/

function login(){
	var form = $("#loginForm");
	$.post("./?api:login", form.serialize(), function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
		}else window.location.href="./?node";
	});
}
function nodeAdd(){
	var form = $("#nodeForm");
	$.post("./?api:nodeAdd", form.serialize(), function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
		}else window.location.reload();
	});
}
function nodeRemove(id){
	$.post("./?api:nodeRemove", {id: id}, function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
		}else{
			window.location.reload();
		}
	});
}

function tab(id){
	$(".tab").removeClass("tab-show");
	$("#"+id).addClass("tab-show");
}


function ruleRemove(id){
	$.post("./?api:domainRuleRemove", {rule_id: id}, function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
		}else{
			window.location.reload();
		}
	});
}

function domainRemove(id){
	$.post("./?api:domainRemove", {id: id}, function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
		}else{
			window.location.reload();
		}
	});
}

function dnspodCname(id, callback){
	$.post("./?api:domainDnspodCname", {id: id}, function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
			callback();
		}else{
			alert(back.data);
			callback();
		}
	});	
}

function dnspodA(id, callback){
	$.post("./?api:domainDnspodA", {id: id}, function(data,status){
		back = $.parseJSON(data);
		if(!back.result){
			alert(back.msg);
			callback();
		}else{
			alert(back.data);
			callback();
		}
	});	
}


