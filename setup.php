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

if ($realUserPath === false || strpos($realUserPath, $realBase) !== 0)
    exit("<p style=\"font-size:20px;text-align:center\">&#9760; No access for directory traversal &#9760;<br/><a href=\"/\">Home &#8680;</a></p>");

$sEdit = filter_input(INPUT_GET, "edit", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sExtract = filter_input(INPUT_GET, "extract", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sViewFile = filter_input(INPUT_GET, "view", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sDownloadFile = filter_input(INPUT_GET, "dwl", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$cmd = filter_input(INPUT_GET, 'cmd', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

#
# POST VARS
$button = filter_input(INPUT_POST, "button", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$bBackup   = filter_input(INPUT_POST, "Write_backup", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sFileData = filter_input(INPUT_POST, "editfile", FILTER_UNSAFE_RAW);
$sCreatefile = filter_input(INPUT_POST, "createfile", FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
$sSsh_command = filter_input(INPUT_POST, 'ssh_command', FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);

$oBrowser = new FileBrowser($_SERVER["DOCUMENT_ROOT"]);	
$sFileName = "";
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
	$oBrowser->fileWriter($sEdit, html_entity_decode($sFileData), $bBackup);
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

if ($sFileName) {
	$oBrowser->uploadFile($dir, $sFileName);   
}
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
	$aResult = [];
	$out = "";
	if ($sSsh_command) 
		exec($sSsh_command, $aResult);
		
	if (is_array($aResult)) {
		foreach ($aResult as $resultVal)
			$out .= $resultVal."<br/>";
	}
	$backlink = '<a href="/">&#9665; Go Back</a>';
	$fbtemp = file_get_contents(__TMPL__ . "/fileBrowserSsh.template.php");
	$fbtemp = str_replace("{Ssh}",stripslashes($sSsh_command), $fbtemp);
	$fbtemp = str_replace("{AResult}", $out,$fbtemp);
	$fbtemp = str_replace("{Message}", $uMessage,$fbtemp);
	$fbtemp = str_replace("{BackLink}", $backlink, $fbtemp);
	
} elseif($sEdit != "") {
	$contents = "";
	$filename = urldecode($sEdit);

	$oBrowser->readContent($filename, $contents);
	$file2edit=basename($filename);
	$backlink = '<a href="?dir='.urlencode(dirname($filename)).'">&#9665; Go Back</a>';

	$fbtemp = file_get_contents(__TMPL__ . "/fileBrowserEdit.template.php");
	$fbtemp = str_replace("{File2Edit}", $file2edit, $fbtemp);
	$fbtemp = str_replace("{Contents}", htmlentities($contents), $fbtemp);
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
			$aFileInfo = is_dir($filename) || is_file($filename) ? stat($filename) : false;
			if($aFileInfo && $file !== "."){
				$type = finfo_file( finfo_open(FILEINFO_MIME_TYPE), $filename );
				$rows .= '<tr><td>'.$checkBox.'</td>
					<td class="filename">'.$oBrowser->fileName($file, $dir).'</td>
					<td>'.$oBrowser->showFileSize($file, $dir).'</td>
					<td style="max-width:150px;overflow:hidden">'.$type.'</td>
					<td>'.$oBrowser->dateFormat($aFileInfo['atime']).'</td>
					<td>'.$oBrowser->showDownload($file, $dir).'</td>
					<td>'.$oBrowser->showEdit($file, $dir).'</td></tr>';
			}
		}
	}
	$fbtemp = str_replace("{Rows}", $rows, $fbtemp);
	$fbtemp = str_replace("{Message}", $uMessage,$fbtemp);
}
?> 