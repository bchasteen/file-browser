<?php 
ini_set('magic_quotes_gpc', 'Off');

define("__ROOT__", dirname(__FILE__));
define("__CODE__", __ROOT__ . "/src");
define("__TMPL__", __ROOT__ . "/templates");
include(__CODE__ . "/FileBrowser.class.php");
#
# GET VARS
$dir = filter_input(INPUT_GET, "dir", FILTER_SANITIZE_STRING, ["options" => [
    "default" => $_SERVER["DOCUMENT_ROOT"],
    "flags" => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH
]]);
define("__BASE__", $_SERVER["DOCUMENT_ROOT"]);

#
# log file for breakin attempts.
$realBase = realpath(__BASE__);
$realUserPath = realpath($dir);

if ($realUserPath === false || strpos($realUserPath, $realBase) !== 0) {
    exit("<p>no access</p>");
}

$sEdit = filter_input(INPUT_GET, "edit", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sExtract = filter_input(INPUT_GET, "extract", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sViewFile = filter_input(INPUT_GET, "view", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sDownloadFile = filter_input(INPUT_GET, "dwl", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$cmd = filter_input(INPUT_GET, 'cmd', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

#
# POST VARS
$button = filter_input(INPUT_POST, "button", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$bBackup   = filter_input(INPUT_POST, "Write_backup", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sFileData = filter_input(INPUT_POST, "editfile", FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW);
$sCreatefile = filter_input(INPUT_POST, "createfile", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sSsh_command = filter_input(INPUT_POST, 'ssh_command', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$oBrowser = new FileBrowser($_SERVER["DOCUMENT_ROOT"]);	
if($_FILES)
	$sFileName = $_FILES['myfile']['name'];

if(isset($_POST['chkfiles']))
	$aFiles = $_POST['chkfiles'];

switch($button){
case "Delete Selected Files":
	$oBrowser->deleteFiles($dir, $aFiles);
	break;
case "Create File":	
	$oBrowser->createFile($dir, $sCreatefile);
	break;
case "Create Directory":
	$oBrowser->createDirectory($dir, trim($sCreatefile));
	break;
case "SAVEFILE":
	$oBrowser->fileWriter($sEdit, $sFileData, $bBackup);
	break;
default:
	break;
}


if ($sDownloadFile) {
	$oBrowser->downloadFile($sDownloadFile);
	exit(0);
}
if ($sExtract != "") {
	$oBrowser->extract($sExtract);
}

#if ($sFileName) {
#	$oBrowser->uploadFile($dir, $sFileName);   
#}
if ($sViewFile) {
	$oBrowser->viewFile($sViewFile);
	exit(0);
}
$sFiles = scandir(urldecode($dir));
$uMessage = "";
if ($oBrowser->sError) {
	$uMessage .= "<p class=\"error\">".$oBrowser->sError."</p>";
}
if ($oBrowser->sMessage) {
	$uMessage .= "<p class=\"message\">".$oBrowser->sMessage."</p>";
}

// Template
$fbtemp="";
if ($cmd == 'ssh') {
	
	if ($sSsh_command) {
		$aResult = array();
		exec($sSsh_command, $aResult);
	}
	if (is_array($aResult)) {
		foreach ($aResult as $resultVal){
			$aresult .= $resultVal."<br/>";
		}
	}
	$backlink = '<a href="'.$_SERVER["HTTP_REFERER"].'"><< Go Back</a>';
	$fbtemp = file_get_contents(__TMPL__ . "/fileBrowserSsh.template.php");
	$fbtemp = str_replace("{Ssh}",stripslashes($_POST['ssh']), $fbtemp);
	$fbtemp = str_replace("{AResult}", $aresult,$fbtemp);
	$fbtemp = str_replace("{Message}", $uMessage,$fbtemp);
	$fbtemp = str_replace("{BackLink}", $backlink, $fbtemp);
	
} elseif($sEdit != "") {
	$contents = "";
	$filename = urldecode($sEdit);
	$oBrowser->readContent($filename, $contents);
	$file2edit=basename($filename);
	$backlink = '<a href="'.$_SERVER["HTTP_REFERER"].'"><< Go Back</a>';

	$fbtemp = file_get_contents(__TMPL__ . "/fileBrowserEdit.template.php");
	$fbtemp = str_replace("{File2Edit}", $file2edit, $fbtemp);
	$fbtemp = str_replace("{Contents}", $contents, $fbtemp);
	$fbtemp = str_replace("{Message}", $uMessage,$fbtemp);
	$fbtemp = str_replace("{BackLink}", $backlink, $fbtemp);
	
} else {
	$fbtemp = file_get_contents(__TMPL__ . "/fileBrowser.template.php");
	$fbtemp = str_replace("{Dir}", $dir, $fbtemp);	
	$fbtemp = str_replace("{CurrentLocation}", $oBrowser->getCurrentDir($dir), $fbtemp);
	$checkBox = "";
	$rows = "";
	if (is_array($sFiles)) {
		foreach ($sFiles as $file){
			if ($file != "." && $file != "..") {
				$checkBox = '<input type="checkbox" id="chkfiles[]" name="chkfiles[]" value="' . $file . '"/>';
			}
			$filename = $dir . DIRECTORY_SEPARATOR . $file;
			$aFileInfo = stat($filename);
			$type = finfo_file( finfo_open(FILEINFO_MIME_TYPE), $filename );
			$rows .= '<tr><td>'.$checkBox.'</td>
				<td>'.$oBrowser->fileName($file, $dir).'</td>
				<td>'.$oBrowser->showFileSize($file, $dir).'</td>
				<td style="max-width:150px;overflow:hidden">'.$type.'</td>
				<td>'.$oBrowser->dateFormat($aFileInfo['atime']).'</td>
				<td>'.$oBrowser->showDownload($file, $dir).'</td>
				<td>'.$oBrowser->showEdit($file, $dir).'</td></tr>';
		}
	}
	$fbtemp = str_replace("{Rows}", $rows, $fbtemp);
	$fbtemp = str_replace("{Message}", $uMessage,$fbtemp);
}
?> 
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8"/>
	<meta http-equiv="X-UA-Compatible" content="IE=edge"/>
	<meta name="viewport" content="width=device-width, initial-scale=1"/>
	<title>Web Root</title>
	<style>
	/* W3.CSS 4.12 November 2018 by Jan Egil and Borge Refsnes */
	html{box-sizing:border-box}*,:after,:before{box-sizing:inherit}html{-ms-text-size-adjust:100%;-webkit-text-size-adjust:100%}body{margin:0}article,aside,details,figcaption,figure,footer,header,main,menu,nav,section,summary{display:block}audio,canvas,progress,video{display:inline-block}progress{vertical-align:baseline}audio:not([controls]){display:none;height:0}[hidden],template{display:none}a{background-color:transparent;-webkit-text-decoration-skip:objects}a:active,a:hover{outline-width:0}abbr[title]{border-bottom:none;text-decoration:underline;text-decoration:underline dotted}dfn{font-style:italic}mark{background:#ff0;color:#000}small{font-size:80%}sub,sup{font-size:75%;line-height:0;position:relative;vertical-align:baseline}sub{bottom:-.25em}sup{top:-.5em}figure{margin:1em 40px}img{border-style:none}svg:not(:root){overflow:hidden}code,kbd,pre,samp{font-family:monospace,monospace;font-size:1em}hr{box-sizing:content-box;height:0;overflow:visible}button,input,select,textarea{font:inherit;margin:0}optgroup{font-weight:700}button,input{overflow:visible}button,select{text-transform:none}[type=reset],[type=submit],button,html [type=button]{-webkit-appearance:button}[type=button]::-moz-focus-inner,[type=reset]::-moz-focus-inner,[type=submit]::-moz-focus-inner,button::-moz-focus-inner{border-style:none;padding:0}[type=button]:-moz-focusring,[type=reset]:-moz-focusring,[type=submit]:-moz-focusring,button:-moz-focusring{outline:1px dotted ButtonText}fieldset{border:1px solid silver;margin:0 2px;padding:.35em .625em .75em}legend{color:inherit;display:table;max-width:100%;padding:0;white-space:normal}textarea{overflow:auto}[type=checkbox],[type=radio]{padding:0}[type=number]::-webkit-inner-spin-button,[type=number]::-webkit-outer-spin-button{height:auto}[type=search]{-webkit-appearance:textfield;outline-offset:-2px}[type=search]::-webkit-search-cancel-button,[type=search]::-webkit-search-decoration{-webkit-appearance:none}::-webkit-input-placeholder{color:inherit;opacity:.54}::-webkit-file-upload-button{-webkit-appearance:button;font:inherit}body,html{font-family:Verdana,sans-serif;font-size:15px;line-height:1.5}html{overflow-x:hidden}h1{font-size:36px}h2{font-size:30px}h3{font-size:24px}h4{font-size:20px}h5{font-size:18px}h6{font-size:16px}.serif{font-family:serif}h1,h2,h3,h4,h5,h6{font-family:"Segoe UI",Arial,sans-serif;font-weight:400;margin:10px 0}.wide{letter-spacing:4px}hr{border:0;border-top:1px solid #eee;margin:20px 0}.image{max-width:100%;height:auto}img{vertical-align:middle}a{color:inherit}.table,.table-all{border-collapse:collapse;border-spacing:0;width:100%;display:table}.table-all{border:1px solid #ccc}.bordered tr,.table-all tr{border-bottom:1px solid #ddd}.striped tbody tr:nth-child(even){background-color:#f1f1f1}.table-all tr:nth-child(odd){background-color:#fff}.table-all tr:nth-child(even){background-color:#f1f1f1}.hoverable tbody tr:hover,.ul.hoverable li:hover{background-color:#ccc}.centered tr td,.centered tr th{text-align:center}.table td,.table th,.table-all td,.table-all th{padding:8px 8px;display:table-cell;text-align:left;vertical-align:top}.table td:first-child,.table th:first-child,.table-all td:first-child,.table-all th:first-child{padding-left:16px}.btn,.button{border:none;display:inline-block;padding:8px 16px;vertical-align:middle;overflow:hidden;text-decoration:none;color:inherit;background-color:inherit;text-align:center;cursor:pointer;white-space:nowrap}.btn:hover{box-shadow:0 8px 16px 0 rgba(0,0,0,.2),0 6px 20px 0 rgba(0,0,0,.19)}.btn,.button{-webkit-touch-callout:none;-webkit-user-select:none;-khtml-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none}.btn:disabled,.button:disabled,.disabled{cursor:not-allowed;opacity:.3}.disabled *,:disabled *{pointer-events:none}.btn.disabled:hover,.btn:disabled:hover{box-shadow:none}.badge,.tag{background-color:#000;color:#fff;display:inline-block;padding-left:8px;padding-right:8px;text-align:center}.badge{border-radius:50%}.ul{list-style-type:none;padding:0;margin:0}.ul li{padding:8px 16px;border-bottom:1px solid #ddd}.ul li:last-child{border-bottom:none}.display-container,.tooltip{position:relative}.tooltip .text{display:none}.tooltip:hover .text{display:inline-block}.ripple:active{opacity:.5}.ripple{transition:opacity 0s}.input{padding:8px;display:block;border:none;border-bottom:1px solid #ccc;width:100%}.select{padding:9px 0;width:100%;border:none;border-bottom:1px solid #ccc}.dropdown-click,.dropdown-hover{position:relative;display:inline-block;cursor:pointer}.dropdown-hover:hover .dropdown-content{display:block}.dropdown-click:hover,.dropdown-hover:first-child{background-color:#ccc;color:#000}.dropdown-click:hover>.button:first-child,.dropdown-hover:hover>.button:first-child{background-color:#ccc;color:#000}.dropdown-content{cursor:auto;color:#000;background-color:#fff;display:none;position:absolute;min-width:160px;margin:0;padding:0;z-index:1}.check,.radio{width:24px;height:24px;position:relative;top:6px}.sidebar{height:100%;width:200px;background-color:#fff;position:fixed!important;z-index:1;overflow:auto}.bar-block .dropdown-click,.bar-block .dropdown-hover{width:100%}.bar-block .dropdown-click .dropdown-content,.bar-block .dropdown-hover .dropdown-content{min-width:100%}.bar-block .dropdown-click .button,.bar-block .dropdown-hover .button{width:100%;text-align:left;padding:8px 16px}#main,.main{transition:margin-left .4s}.modal{z-index:3;display:none;padding-top:100px;position:fixed;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:#000;background-color:rgba(0,0,0,.4)}.modal-content{margin:auto;background-color:#fff;position:relative;padding:0;outline:0;width:600px}.bar{width:100%;overflow:hidden}.center .bar{display:inline-block;width:auto}.bar .bar-item{padding:8px 16px;float:left;width:auto;border:none;display:block;outline:0}.bar .dropdown-click,.bar .dropdown-hover{position:static;float:left}.bar .button{white-space:normal}.bar-block .bar-item{width:100%;display:block;padding:8px 16px;text-align:left;border:none;white-space:normal;float:none;outline:0}.bar-block.center .bar-item{text-align:center}.block{display:block;width:100%}.responsive{display:block;overflow-x:auto}.bar:after,.bar:before,.cell-row:after,.cell-row:before,.clear:after,.clear:before,.container:after,.container:before,.panel:after,.panel:before,.row-padding:after,.row-padding:before,.row:after,.row:before{content:"";display:table;clear:both}.col,.half,.quarter,.third,.threequarter,.twothird{float:left;width:100%}.col.s1{width:8.33333%}.col.s2{width:16.66666%}.col.s3{width:24.99999%}.col.s4{width:33.33333%}.col.s5{width:41.66666%}.col.s6{width:49.99999%}.col.s7{width:58.33333%}.col.s8{width:66.66666%}.col.s9{width:74.99999%}.col.s10{width:83.33333%}.col.s11{width:91.66666%}.col.s12{width:99.99999%}@media (min-width:601px){.col.m1{width:8.33333%}.col.m2{width:16.66666%}.col.m3,.quarter{width:24.99999%}.col.m4,.third{width:33.33333%}.col.m5{width:41.66666%}.col.m6,.half{width:49.99999%}.col.m7{width:58.33333%}.col.m8,.twothird{width:66.66666%}.col.m9,.threequarter{width:74.99999%}.col.m10{width:83.33333%}.col.m11{width:91.66666%}.col.m12{width:99.99999%}}@media (min-width:993px){.col.l1{width:8.33333%}.col.l2{width:16.66666%}.col.l3{width:24.99999%}.col.l4{width:33.33333%}.col.l5{width:41.66666%}.col.l6{width:49.99999%}.col.l7{width:58.33333%}.col.l8{width:66.66666%}.col.l9{width:74.99999%}.col.l10{width:83.33333%}.col.l11{width:91.66666%}.col.l12{width:99.99999%}}.rest{overflow:hidden}.stretch{margin-left:-16px;margin-right:-16px}.auto,.content{margin-left:auto;margin-right:auto}.content{max-width:980px}.auto{max-width:1140px}.cell-row{display:table;width:100%}.cell{display:table-cell}.cell-top{vertical-align:top}.cell-middle{vertical-align:middle}.cell-bottom{vertical-align:bottom}.hide{display:none!important}.show,.show-block{display:block!important}.show-inline-block{display:inline-block!important}@media (max-width:1205px){.auto{max-width:95%}}@media (max-width:600px){.modal-content{margin:0 10px;width:auto!important}.modal{padding-top:30px}.dropdown-click.mobile .dropdown-content,.dropdown-hover.mobile .dropdown-content{position:relative}.hide-small{display:none!important}.mobile{display:block;width:100%!important}.bar-item.mobile,.dropdown-click.mobile,.dropdown-hover.mobile{text-align:center}.dropdown-click.mobile,.dropdown-click.mobile .btn,.dropdown-click.mobile .button,.dropdown-hover.mobile,.dropdown-hover.mobile .btn,.dropdown-hover.mobile .button{width:100%}}@media (max-width:768px){.modal-content{width:500px}.modal{padding-top:50px}}@media (min-width:993px){.modal-content{width:900px}.hide-large{display:none!important}.sidebar.collapse{display:block!important}}@media (max-width:992px) and (min-width:601px){.hide-medium{display:none!important}}@media (max-width:992px){.sidebar.collapse{display:none}.main{margin-left:0!important;margin-right:0!important}.auto{max-width:100%}}.bottom,.top{position:fixed;width:100%;z-index:1}.top{top:0}.bottom{bottom:0}.overlay{position:fixed;display:none;width:100%;height:100%;top:0;left:0;right:0;bottom:0;background-color:rgba(0,0,0,.5);z-index:2}.display-topleft{position:absolute;left:0;top:0}.display-topright{position:absolute;right:0;top:0}.display-bottomleft{position:absolute;left:0;bottom:0}.display-bottomright{position:absolute;right:0;bottom:0}.display-middle{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);-ms-transform:translate(-50%,-50%)}.display-left{position:absolute;top:50%;left:0;transform:translate(0,-50%);-ms-transform:translate(0,-50%)}.display-right{position:absolute;top:50%;right:0;transform:translate(0,-50%);-ms-transform:translate(0,-50%)}.display-topmiddle{position:absolute;left:50%;top:0;transform:translate(-50%,0);-ms-transform:translate(-50%,0)}.display-bottommiddle{position:absolute;left:50%;bottom:0;transform:translate(-50%,0);-ms-transform:translate(-50%,0)}.display-container:hover .display-hover{display:block}.display-container:hover span.display-hover{display:inline-block}.display-hover{display:none}.display-position{position:absolute}.circle{border-radius:50%}.round-small{border-radius:2px}.round,.round-medium{border-radius:4px}.round-large{border-radius:8px}.round-xlarge{border-radius:16px}.round-xxlarge{border-radius:32px}.row-padding,.row-padding>.col,.row-padding>.half,.row-padding>.quarter,.row-padding>.third,.row-padding>.threequarter,.row-padding>.twothird{padding:0 8px}.container,.panel{padding:.01em 16px}.panel{margin-top:16px;margin-bottom:16px}.code,.codespan{font-family:Consolas,"courier new";font-size:16px}.code{width:auto;background-color:#fff;padding:8px 12px;border-left:4px solid #4caf50;word-wrap:break-word}.codespan{color:#dc143c;background-color:#f1f1f1;padding-left:4px;padding-right:4px;font-size:110%}.card,.card-2{box-shadow:0 2px 5px 0 rgba(0,0,0,.16),0 2px 10px 0 rgba(0,0,0,.12)}.card-4,.hover-shadow:hover{box-shadow:0 4px 10px 0 rgba(0,0,0,.2),0 4px 20px 0 rgba(0,0,0,.19)}.spin{animation:w3-spin 2s infinite linear}@keyframes w3-spin{0%{transform:rotate(0)}100%{transform:rotate(359deg)}}.animate-fading{animation:fading 10s infinite}@keyframes fading{0%{opacity:0}50%{opacity:1}100%{opacity:0}}.animate-opacity{animation:opac .8s}@keyframes opac{from{opacity:0}to{opacity:1}}.animate-top{position:relative;animation:animatetop .4s}@keyframes animatetop{from{top:-300px;opacity:0}to{top:0;opacity:1}}.animate-left{position:relative;animation:animateleft .4s}@keyframes animateleft{from{left:-300px;opacity:0}to{left:0;opacity:1}}.animate-right{position:relative;animation:animateright .4s}@keyframes animateright{from{right:-300px;opacity:0}to{right:0;opacity:1}}.animate-bottom{position:relative;animation:animatebottom .4s}@keyframes animatebottom{from{bottom:-300px;opacity:0}to{bottom:0;opacity:1}}.animate-zoom{animation:animatezoom .6s}@keyframes animatezoom{from{transform:scale(0)}to{transform:scale(1)}}.animate-input{transition:width .4s ease-in-out}.animate-input:focus{width:100%!important}.hover-opacity:hover,.opacity{opacity:.6}.hover-opacity-off:hover,.opacity-off{opacity:1}.opacity-max{opacity:.25}.opacity-min{opacity:.75}.grayscale-max,.greyscale-max,.hover-grayscale:hover,.hover-greyscale:hover{filter:grayscale(100%)}.grayscale,.greyscale{filter:grayscale(75%)}.grayscale-min,.greyscale-min{filter:grayscale(50%)}.sepia{filter:sepia(75%)}.hover-sepia:hover,.sepia-max{filter:sepia(100%)}.sepia-min{filter:sepia(50%)}.tiny{font-size:10px!important}.small{font-size:12px!important}.medium{font-size:15px!important}.large{font-size:18px!important}.xlarge{font-size:24px!important}.xxlarge{font-size:36px!important}.xxxlarge{font-size:48px!important}.jumbo{font-size:64px!important}.left-align{text-align:left!important}.right-align{text-align:right!important}.justify{text-align:justify!important}.center{text-align:center!important}.border-0{border:0!important}.border{border:1px solid #ccc!important}.border-top{border-top:1px solid #ccc!important}.border-bottom{border-bottom:1px solid #ccc!important}.border-left{border-left:1px solid #ccc!important}.border-right{border-right:1px solid #ccc!important}.topbar{border-top:6px solid #ccc!important}.bottombar{border-bottom:6px solid #ccc!important}.leftbar{border-left:6px solid #ccc!important}.rightbar{border-right:6px solid #ccc!important}.code,.section{margin-top:16px!important;margin-bottom:16px!important}.margin{margin:16px!important}.margin-top{margin-top:16px!important}.margin-bottom{margin-bottom:16px!important}.margin-left{margin-left:16px!important}.margin-right{margin-right:16px!important}.padding-small{padding:4px 8px!important}.padding{padding:8px 16px!important}.padding-large{padding:12px 24px!important}.padding-16{padding-top:16px!important;padding-bottom:16px!important}.padding-24{padding-top:24px!important;padding-bottom:24px!important}.padding-32{padding-top:32px!important;padding-bottom:32px!important}.padding-48{padding-top:48px!important;padding-bottom:48px!important}.padding-64{padding-top:64px!important;padding-bottom:64px!important}.left{float:left!important}.right{float:right!important}.button:hover{color:#000!important;background-color:#ccc!important}.hover-none:hover,.transparent{background-color:transparent!important}.hover-none:hover{box-shadow:none!important}.amber,.hover-amber:hover{color:#000!important;background-color:#ffc107!important}.aqua,.hover-aqua:hover{color:#000!important;background-color:#0ff!important}.blue,.hover-blue:hover{color:#fff!important;background-color:#2196f3!important}.hover-light-blue:hover,.light-blue{color:#000!important;background-color:#87ceeb!important}.brown,.hover-brown:hover{color:#fff!important;background-color:#795548!important}.cyan,.hover-cyan:hover{color:#000!important;background-color:#00bcd4!important}.blue-gray,.blue-grey,.hover-blue-gray:hover,.hover-blue-grey:hover{color:#fff!important;background-color:#607d8b!important}.green,.hover-green:hover{color:#fff!important;background-color:#4caf50!important}.hover-light-green:hover,.light-green{color:#000!important;background-color:#8bc34a!important}.hover-indigo:hover,.indigo{color:#fff!important;background-color:#3f51b5!important}.hover-khaki:hover,.khaki{color:#000!important;background-color:khaki!important}.hover-lime:hover,.lime{color:#000!important;background-color:#cddc39!important}.hover-orange:hover,.orange{color:#000!important;background-color:#ff9800!important}.deep-orange,.hover-deep-orange:hover{color:#fff!important;background-color:#ff5722!important}.hover-pink:hover,.pink{color:#fff!important;background-color:#e91e63!important}.hover-purple:hover,.purple{color:#fff!important;background-color:#9c27b0!important}.deep-purple,.hover-deep-purple:hover{color:#fff!important;background-color:#673ab7!important}.hover-red:hover,.red{color:#fff!important;background-color:#f44336!important}.hover-sand:hover,.sand{color:#000!important;background-color:#fdf5e6!important}.hover-teal:hover,.teal{color:#fff!important;background-color:#009688!important}.hover-yellow:hover,.yellow{color:#000!important;background-color:#ffeb3b!important}.hover-white:hover,.white{color:#000!important;background-color:#fff!important}.black,.hover-black:hover{color:#fff!important;background-color:#000!important}.gray,.grey,.hover-gray:hover,.hover-grey:hover{color:#000!important;background-color:#9e9e9e!important}.hover-light-gray:hover,.hover-light-grey:hover,.light-gray,.light-grey{color:#000!important;background-color:#f1f1f1!important}.dark-gray,.dark-grey,.hover-dark-gray:hover,.hover-dark-grey:hover{color:#fff!important;background-color:#616161!important}.hover-pale-red:hover,.pale-red{color:#000!important;background-color:#fdd!important}.hover-pale-green:hover,.pale-green{color:#000!important;background-color:#dfd!important}.hover-pale-yellow:hover,.pale-yellow{color:#000!important;background-color:#ffc!important}.hover-pale-blue:hover,.pale-blue{color:#000!important;background-color:#dff!important}.hover-text-amber:hover,.text-amber{color:#ffc107!important}.hover-text-aqua:hover,.text-aqua{color:#0ff!important}.hover-text-blue:hover,.text-blue{color:#2196f3!important}.hover-text-light-blue:hover,.text-light-blue{color:#87ceeb!important}.hover-text-brown:hover,.text-brown{color:#795548!important}.hover-text-cyan:hover,.text-cyan{color:#00bcd4!important}.hover-text-blue-gray:hover,.hover-text-blue-grey:hover,.text-blue-gray,.text-blue-grey{color:#607d8b!important}.hover-text-green:hover,.text-green{color:#4caf50!important}.hover-text-light-green:hover,.text-light-green{color:#8bc34a!important}.hover-text-indigo:hover,.text-indigo{color:#3f51b5!important}.hover-text-khaki:hover,.text-khaki{color:#b4aa50!important}.hover-text-lime:hover,.text-lime{color:#cddc39!important}.hover-text-orange:hover,.text-orange{color:#ff9800!important}.hover-text-deep-orange:hover,.text-deep-orange{color:#ff5722!important}.hover-text-pink:hover,.text-pink{color:#e91e63!important}.hover-text-purple:hover,.text-purple{color:#9c27b0!important}.hover-text-deep-purple:hover,.text-deep-purple{color:#673ab7!important}.hover-text-red:hover,.text-red{color:#f44336!important}.hover-text-sand:hover,.text-sand{color:#fdf5e6!important}.hover-text-teal:hover,.text-teal{color:#009688!important}.hover-text-yellow:hover,.text-yellow{color:#d2be0e!important}.hover-text-white:hover,.text-white{color:#fff!important}.hover-text-black:hover,.text-black{color:#000!important}.hover-text-gray:hover,.hover-text-grey:hover,.text-gray,.text-grey{color:#757575!important}.hover-text-light-gray:hover,.hover-text-light-grey:hover,.text-light-gray,.text-light-grey{color:#f1f1f1!important}.hover-text-dark-gray:hover,.hover-text-dark-grey:hover,.text-dark-gray,.text-dark-grey{color:#3a3a3a!important}.border-amber,.hover-border-amber:hover{border-color:#ffc107!important}.border-aqua,.hover-border-aqua:hover{border-color:#0ff!important}.border-blue,.hover-border-blue:hover{border-color:#2196f3!important}.border-light-blue,.hover-border-light-blue:hover{border-color:#87ceeb!important}.border-brown,.hover-border-brown:hover{border-color:#795548!important}.border-cyan,.hover-border-cyan:hover{border-color:#00bcd4!important}.border-blue-gray,.border-blue-grey,.hover-border-blue-gray:hover,.hover-border-blue-grey:hover{border-color:#607d8b!important}.border-green,.hover-border-green:hover{border-color:#4caf50!important}.border-light-green,.hover-border-light-green:hover{border-color:#8bc34a!important}.border-indigo,.hover-border-indigo:hover{border-color:#3f51b5!important}.border-khaki,.hover-border-khaki:hover{border-color:khaki!important}.border-lime,.hover-border-lime:hover{border-color:#cddc39!important}.border-orange,.hover-border-orange:hover{border-color:#ff9800!important}.border-deep-orange,.hover-border-deep-orange:hover{border-color:#ff5722!important}.border-pink,.hover-border-pink:hover{border-color:#e91e63!important}.border-purple,.hover-border-purple:hover{border-color:#9c27b0!important}.border-deep-purple,.hover-border-deep-purple:hover{border-color:#673ab7!important}.border-red,.hover-border-red:hover{border-color:#f44336!important}.border-sand,.hover-border-sand:hover{border-color:#fdf5e6!important}.border-teal,.hover-border-teal:hover{border-color:#009688!important}.border-yellow,.hover-border-yellow:hover{border-color:#ffeb3b!important}.border-white,.hover-border-white:hover{border-color:#fff!important}.border-black,.hover-border-black:hover{border-color:#000!important}.border-gray,.border-grey,.hover-border-gray:hover,.hover-border-grey:hover{border-color:#9e9e9e!important}.border-light-gray,.border-light-grey,.hover-border-light-gray:hover,.hover-border-light-grey:hover{border-color:#f1f1f1!important}.border-dark-gray,.border-dark-grey,.hover-border-dark-gray:hover,.hover-border-dark-grey:hover{border-color:#616161!important}.border-pale-red,.hover-border-pale-red:hover{border-color:#ffe7e7!important}.border-pale-green,.hover-border-pale-green:hover{border-color:#e7ffe7!important}.border-pale-yellow,.hover-border-pale-yellow:hover{border-color:#ffc!important}.border-pale-blue,.hover-border-pale-blue:hover{border-color:#e7ffff!important}</style>
	<style>
        body{
            font:normal normal 14px helvetica, arial, sans-serif
        }
        main{
			display:-webkit-flex;
			display:flex;
			width:100%;
			height:100%;
		}
		main > nav{width:40%;order:1;}
		main > section{width:100%;order:0;}
        h1{
            margin:0;
            padding:0;
        }
        .title, .content{
            border:1px dashed gray;
            padding:15px;
            margin:5px;
        }
        .content{
            margin-top:20px;
            overflow:auto;
            min-height:500px;
        }
        a {
        	color:#090
        }
        
    </style>
</head>
<body>
<main>
	<section>
		<?php echo $fbtemp; ?>
	</section>
</main>
<script type="text/javascript">
    function selectElementContents(el) {
        var range = document.createRange();
        range.selectNodeContents(el);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    }
</script>
</body>
</html>