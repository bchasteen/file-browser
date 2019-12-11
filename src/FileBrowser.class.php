<?php
date_default_timezone_set('America/New_York');
class FileBrowser{
    public $slash, $sMessage = 0, $sError = 0;
    protected $aPlainText = array('as','asp','aspx','atom','bat','cfm','cmd','hta','htm','html','js','jsp','java','mht','php','pl','py','rb','rss','sh','txt','xhtml','xml','log','out','ini','shtml','xsl','xslt','backup', 'gitignore', 'git', 'htaccess');
    protected $aImageType = array('bm','bmp','ras','rast','fif','flo','turbot','g3','gif','ief','iefs','jfif','jfif-tbnl','jpe','jpeg','jpg','jut','nap','naplps','pic','pict','jfif','jpe','jpeg','jpg','png','x-png','tif','tiff','mcf','dwg','dxf','svf','fpx','fpx','rf','rp','wbmp','xif','xbm','ras','dwg','dxf','svf','ico','art','jps','nif','niff','pcx','pct','xpm','pnm','pbm','pgm','pgm','ppm','qif','qti','qtif','rgb','tif','tiff','bmp','xbm','xbm','pm','xpm','xwd','xwd');
	protected $path;
	protected $admin;
	
    public function __construct($root=""){
	    error_reporting(E_ALL);
        $this->admin = "";
        $this->path = "";
        $this->root = $root;
    }
    public function downloadFile($file){
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header('Content-Length: ' . filesize($file));
        header('Content-Disposition: attachment; filename=' . basename($file));
        header('Content-Type: application/octet-stream');
        readfile($file);
    }
    public function fileName($file, $dir){
    	$filename = $dir . DIRECTORY_SEPARATOR . $file;
    	$sLink = "";
        if (filetype($filename) !== "dir") {
            #$sLink = '<a href="'.$this->path.'?view='.urlencode($filename).'">'.$file.'</a>';
            $sExt = strtolower(substr(strrchr($filename,'.'), 1));
            $sLink = ($sExt === 'zip') 
            	? '<a href="'.$this->path.'?extract='.urlencode($filename).'">'.$file.'</a>'
            	: '<a href="'.$this->path.'?edit='.urlencode($filename).'">'.$file.' &#9998;</a>';
        } else {
            if ($file == '.') {
				$sLink = '<a href="'.$this->path.'?dir='.$this->root."\">[ ".DIRECTORY_SEPARATOR." ]</a>";
            } elseif ($file == '..') {
            	$prev = dirname(dirname($filename));
            	if( strlen(str_replace("..", "", $filename)) < strlen($this->root) )
            		$prev = $this->root;
                $sLink = '<a style="font-size:130%;line-height:110%" href="'.$this->path.'?dir='.urlencode($prev)."\">&#8629;</a>";
            } else {
                $sLink = '<a href="'.$this->path.'?dir='.urlencode($filename).'">'.$file.'</a>';
            }
        }
        return $sLink;
    } 
    private function formatSize($bytes, $precision = 2) {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        return round($bytes, $precision) . ' ' . $units[$pow];
    } 
    public function dateFormat($iTimestamp) {
        return date("F j, Y, g:i a", $iTimestamp);
    } 
    public function delete_directory($dirname) {
    	
        if (is_dir($dirname))
            $dir_handle = opendir($dirname);
        if (!$dir_handle)
            return false;
        while($file = readdir($dir_handle)) {
            if ($file != "." && $file != "..") {
                if (!is_dir($dirname."/".$file))
                    if (@unlink($dirname."/".$file)) {
                        $this->sMessage = "Directory Deleted Successfully: \"".$dirname."\" .";
                    }
                    else{
                        $this->sError = "Can't Deleted Directory \"".$dirname."\" .";
                    }
                    else
                        $this->delete_directory($dirname.'/'.$file);          
            }
        }
        closedir($dir_handle);
        rmdir($dirname);
        return true;
    }
    public function deleteFiles($dir, $aFiles){
        if (is_array($aFiles)) {
            foreach ($aFiles as $aFilesNames){
            	$filename = $dir . DIRECTORY_SEPARATOR . $aFilesNames;
                if (is_dir($filename)) {
                    $this->delete_directory($filename);
                    
                } else {
                    if (@unlink($filename))
                        $this->sMessage = "<div class=\"padding pale-green text-green\">File Deleted Successfully: \"".$filename."\"</div>";
                    else
                        $this->sError = "Can't Delete file \"".$filename."\" .";
                }
            }
        }
    }
    public function createDirectory($dir, $sCreatefile){
    	$directory = $dir . DIRECTORY_SEPARATOR . $sCreatefile;
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
            $this->sMessage = "<div class=\"padding pale-green text-green border\">Directory Created Successfully: \"$directory\"</div>";
        }else{
            $this->sError = "<div class=\"padding pale-yellow text-grey border\">\"$directory\" Directory already exist</div>";
        }
    }
    public function createFile($dir, $sCreatefile, $creationNum=1){
    	$sCreatefile = str_replace("../", "", $sCreatefile);
    	$filename = $dir . DIRECTORY_SEPARATOR . $sCreatefile;
        if (!file_exists($filename)) {
            if (is_writable($dir)) {
            	try {
					if($handle = fopen($filename, "w")){
						fclose($handle);
						$this->sMessage = "File Created Successfully: \"$sCreatefile\" in directory: $dir.";
					} else {
						throw new Exception('File naming error.');
					}
                } catch(Exception $e) {
                	$this->sError = "Directory Not Writable, Can't Create file!";
                }
               
            }else{
                $this->sError = "Directory Not Writable, Can't Create file.";
            }
        } else{
            $this->sError = " \"$sCreatefile\" File already exists.";
            $this->createFile($dir, $sCreatefile . "($creationNum)", $creationNum+1);
        }
    }
    public function extract($sExtract){
        $path_parts = pathinfo($sExtract);
        if (is_writable($path_parts['dirname'])) {
            $zip = new ZipArchive;
            if ($zip->open($sExtract) === TRUE) {
                $zip->extractTo($path_parts['dirname']);
                $zip->close();
                return 'ok';
            } else {
                return 'failed';
            }
        }
        else{
            $this->sError = "\"".$path_parts['dirname']."\" Directory is not writable..";
        }
    }
    public function getCurrentDir($dir){
        $slugs = array_values(array_filter(explode(DIRECTORY_SEPARATOR, $dir)));
        $ret = [];
        for($i = count($slugs); $i > 0; $i--){
        	$ret[] = '<a href="?dir=' . DIRECTORY_SEPARATOR . urlencode(implode(DIRECTORY_SEPARATOR, $slugs)) . '">' .  $slugs[$i - 1] . '</a>';
        	array_pop($slugs);
        }
        return implode(DIRECTORY_SEPARATOR, array_reverse($ret));
    }
    private function writeBackup($sFileName){
        if (!copy($sFileName, $sFileName.".backup")) {
            return false;
        }
        return true;
    }   
    public function fileWriter($sFile, $string, $backup = false) {
	    $this->sMessage = '';
        if(function_exists("file_put_contents")){
	        $written=file_put_contents($sFile, $string);
	        $this->sMessage .= "File Saved Successfully: \"$sFile\".";
        } else {
	        $fp = fopen($sFile,"w");
	        //Writing to a network stream may end before the whole string is written. Return value of fwrite() is checked
	        for ($written = 0; $written < strlen($string); $written += $fwrite) {
	            $fwrite = fwrite($fp, substr($string, $written));
	            if (!$fwrite) {
	                return $fwrite;
	            }
	        }
	        fclose($fp);
	        $this->sMessage .= "File Saved Successfully: \"$sFile\".";
    	}
    	if ($backup) {
            $this->writeBackup($sFile);
            $this->sMessage .= "Backup Written Successfully: \"$sFile\".<br/>";
        }
		return $written;
    }
    public function uploadFile($dir, $sFileName){
        if (move_uploaded_file($_FILES['myfile']['tmp_name'], $dir.DIRECTORY_SEPARATOR.$sFileName)) {
            $this->sMessage = "<div class=\"padding pale-green text-green\">File: <u>$sFileName</u> Successfully Uploaded to: $dir.\"</div>";
        }
        else{
            $this->sError = "\"$sFileName\" Uploading Error.";
        }
    }
    public function readContent($sEdit, &$contents = null){
        if (file_exists($sEdit))
        	$contents = file_get_contents($sEdit);
    }
    public function showDownload($file, $dir = ""){
    	$filename = $dir . DIRECTORY_SEPARATOR . $file;
        if (filetype($filename) != "dir") {
            return '<a href="'.$this->path.'?dwl='.urlencode($filename).'">&#8681; Download</a>';
        }else{
            return '';
        }
    }
    public function showEdit($file, $dir){
    	$filename = $dir . DIRECTORY_SEPARATOR . $file;
		$sLink = "";
        if (filetype($filename) != "dir") {
            $sExt = strtolower(substr(strrchr($filename,'.'),1));
            $sLink = ($sExt === 'zip') 
            	? '<a href="'.$this->path.'?extract='.urlencode($filename).'">Unpack</a>'
            	: '<a href="'.$this->path.'?edit='.urlencode($filename).'">Edit &#9998;</a>';
        }
        return $sLink;
    }
    public function showFileSize($file, $dir, $precision = 2) {
    	$filename = $dir . DIRECTORY_SEPARATOR . $file;
    	return filetype($filename) != "dir" ? $this->formatSize(filesize($filename)) : "";
    }
    public function viewFile($file){
        $sBaseName = basename($file);
        $sExt = strtolower(substr(strrchr($sBaseName,'.'),1));
        $ret="";
        if ($sExt == "zip") {
            $oZip = new ZipArchive;
            if ($oZip->open($file) === TRUE) {
                $ret.= "<table cellspacing=\"1px\" cellpadding=\"0px\">";
                $ret.= "<tr><th>Name</th><th>Uncompressed size</th><th>Compressed size</th><th>Compr. ratio</th><th>Date</th></tr>";
                for ($i=0; $i<$oZip->numFiles;$i++) {
                    $aZipDtls = $oZip->statIndex($i);
                    $iPercent = round($aZipDtls['comp_size'] * 100 / $aZipDtls['size']);
                    $iUncompressedSize = $aZipDtls['size'];
                    $iCompressedSize = $aZipDtls['comp_size'];
                    $iTotalPercent += $iPercent;
                    $ret.= "<tr><td>".$aZipDtls['name']."</td><td>".$this->formatSize($iUncompressedSize)."</td><td>".$this->formatSize($iCompressedSize)."</td><td>".$iPercent."%</td><td>".$this->dateFormat($aZipDtls['mtime'])."</td></tr>";
                }
                $ret.= "</table>";
                $ret.= "<p align=\"center\"><b>".$this->showFileSize($file, $dir)." in ".$oZip->numFiles." files in ".basename($oZip->filename).". Compression ratio: ".round($iTotalPercent / $oZip->numFiles)."%</b></p>";
                $oZip->close();
            } else {
                $ret.= 'failed';
            }
            return $ret;
        }elseif (in_array($sExt, $this->aPlainText)) {
            header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Description: File View');
            header('Content-Length: ' . filesize($file));
            header('Content-Disposition: inline; filename=' . basename($file));
            header('Content-Type: text/plain');
            readfile($file);
        }elseif(in_array($sExt, $this->aImageType)){
            header ("Cache-Control: must-revalidate, post-check=0, pre-check=0");
            header('Content-Description: File View');
            header('Content-Length: ' . filesize($file));
            header('Content-Disposition: inline; filename=' . basename($file));
            header('Content-Type: image/jpg');
            readfile($file);
        } else{
            $this->downloadFile($file);
        }
    }
}