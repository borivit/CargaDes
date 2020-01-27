[client_u]
<link rel="stylesheet" type="text/css" href="{style}?v=2" />
<script>
	function RemovE(id){
		document.getElementById(id).remove();
		delete document.getElementById("file-container").file[id];
	}
	function in_array(what, where) {
		for(var i=0; i<where.length; i++) {
			if(what == where[i]) {return true;}
		}
		return false;
	}
	function fadeOut(el,func) {

		var opacity = 1, f = "";
		if(func !== "undefined"){f = func;}
		
		var timer = setInterval(function() {
		
			if(opacity <= 0.3) {
			
				clearInterval(timer);
				document.getElementById(el).style.display = "none";
		
			}
		
			document.getElementById(el).style.opacity = opacity;
		 
			opacity -= opacity * 0.1;
	   
		}, 100);
		f
	}
	
	var arr = JSON.stringify({param});
	//console.log(arr);
	
	document.addEventListener("DOMContentLoaded", function(){
		var input = document.getElementById("file-input");
		var button = document.getElementById("file-submit");
		var container = document.getElementById("file-container");
		var err_file_size = "{err_file_size}";
		var err_file_ext = "{err_file_ext}";
		var err_file_zero = "{err_file_zero}";
		var debug = {debug};
			container.file = {};
		
		input.onchange = function(e){
			var FR = new FileReader();
			var files = e.target.files;
			
			for(var file of files){
				if(document.getElementsByClassName("file-info").length>={max_file_count} && {max_file_count}>0) break;
				if(file.size!=0 && (file.size<{max_file_size} || {max_file_size}==0)){
					var id_name = [];
					id_name = file.name.split(".");
					var ext = "{allowed_ext}";
					ext = ext.split(",");
					if(in_array(id_name[1], ext) == true){
						
						if(container.file.hasOwnProperty(id_name[0]) == false){
							container.innerHTML = "<div id=\""+id_name[0]+"\" class=\"file-info\"><span class=\"btn-del\" onclick=\"RemovE(\'"+id_name[0]+"\');\">{btn_del}</span><span class=\"file-name\">"+file.name+"</span><span class=\"client-progress-bar\"><span class=\"client-progress\">0%</span></span></div>"+container.innerHTML;
							
							container.file[id_name[0]] = file;
						}
						
					}else{
						var err_ext = err_file_ext.replace("{file}", file.name);
						alert(err_ext);
					}
				}else{
					var err_file = err_file_size.replace("{file}", file.name);
					if(file.size==0) err_file = err_file_zero.replace("{file}", file.name);
					alert(err_file);
				}
			}
		};
		
		button.onclick = async function(evt){
			evt.preventDefault();
			var arr_files = [];
			arr_files = container.file;
			
			for (var key in arr_files){upload(key,arr_files);}
			console.log("Готово");
			
		};
		async function upload(key, file){//item, i, arr
			var data = new FormData();
			data.append("file", file[key]);
			
			{ajaxParam}
			if({param}!=0) data.append("param",arr);
			
			var progress = document.getElementById(key).querySelector(".client-progress");//elem.querySelector(".client-progress");
			let computedStyle = getComputedStyle(progress);
			var width_css=computedStyle.width;
			
			width_css=width_css.replace("px","");
			var pb = width_css>100?Math.ceil(width_css/100):Math.ceil(100/width_css);
			
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "{url_server}");
			
			xhr.upload.onprogress = function(evt){
				var percent = Math.ceil(evt.loaded / evt.total * 100);
				var prc = percent*pb;
				progress.style.boxShadow = "inset "+prc+"px 0px 0px #{color}";
				progress.innerHTML = percent + "%";
			}
			
			xhr.onload = function () {
				if(debug == true){document.getElementById("debug").innerHTML += '<br>responseText:<br>'+xhr.responseText;}
				if(xhr.status == "200"){{returns}}
			};
			
			xhr.send(data);
			
		}
	});
</script>
<form id="file-form" method="post">
	<input class="btn-input" type="button" onclick="document.getElementById('file-input').click();return false;" value="{btn_input}">
	<input type="file" id="file-input" {multiples} style="display:none;">
	<input class="btn-enviar" type="button" id="file-submit" value="{btn_enviar}"/><br/>
	<div class="file-container" id="file-container"></div>
</form>
<div id="debug"></div>
[/client_u]
[server]
<link rel="stylesheet" type="text/css" href="{style}?v=2" />
<span class="server-progress-bar"><span class="server-progress{idp}">0%</span></span>
<script>
	var progress = document.querySelector(".server-progress{idp}");
	function updateProgress(percentage){
		let computedStyle = getComputedStyle(progress);
		var width_css=computedStyle.width;
		width_css=width_css.replace("px","");
		var pb = width_css>100?Math.ceil(width_css/100):Math.ceil(100/width_css);
		var prc = percentage*pb;
		progress.style.boxShadow = "inset "+prc+"px 0px 0px #{color}";
		progress.innerHTML = percentage + "%";
		document.getElementById("sct").remove();
	}
</script>
[/server]