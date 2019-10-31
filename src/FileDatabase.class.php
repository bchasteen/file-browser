<?php
class FileDatabase{
	var $dataArray=array();
	var $array;
	var $log;
	var $database;
	
	function __construct($file){
		$this->database = $file;
		$this->tempfile = str_replace(".","_temp.",$this->database);
		$this->logfile  = str_replace(".txt","_log.txt",$this->database);
	}
	function getDatabase(){
		return $this->database;
	}
	function getTempfile(){
		return $this->tempfile;
	}
	function getLogfile(){
		return $this->logfile;
	}
	function setDatabase($val){
		$this->database=$val;
	}
	function setTempfile($val){
		$this->tempfile=$val;
	}
	function setLogfile($val){
		$this->logfile=$val;
	}
	function getAutoId(){
		return $this->autoid;
	}
	function insert($array){
    	if(is_numeric($this->autoid)){
	    	array_unshift($array,($this->autoid+1));
    		$this->autoid = $this->autoid + 1;
		}
		$this->dataArray[] = $array;
  	}
  	function read_cDB(){
	  	$cDB = $this->database;
	  	return file_get_contents($cDB);
  	}
	function read_iDB(){
		$iDB = $this->database;
		if (is_file($this->database)){
		  $fd = fopen($this->database,"r");
		  $this->autoid = trim(fgets($fd,4096));
		  while (!feof ($fd)) {
		    if(fgets($fd,2) == "*")
		      $this->dataArray[] = fgetcsv($fd,4096,"*");
		  }
		  fclose($fd);
		}else {
		  $fd = fopen($iDB,"w");
		  fclose($fd);
		}
		return $this->dataArray;
	}
	//
	// UPDATE CALENDAR DATABASE FROM TEMP FILES
	//
	function update_DB(){
		//OVERWRITE ORIGINAL WITH TEMP FILE IF CHANGED
		if (IS_FILE($this->database) && IS_FILE($this->tempfile) && filemtime($this->database) < filemtime($this->tempfile)){
		  if (!copy($this->tempfile, $this->database)) {
		  	$this->log("ERROR: Failed to copy temporary image database file");
		    echo "FILE ERROR: Failed to copy temp file (function 'readDB')";
		    exit();
		  }
		
		}
	}
	//
	// WRITE a row to CALENDAR DATABASE
	//
	function write_cDB($buffer){
	  $fd = fopen($this->tempfile,"w+");
	  if (flock($fd, LOCK_EX)) { // do an exclusive lock
	    ftruncate($fd, 0);
	    fwrite($fd, $buffer);
	    flock($fd, LOCK_UN); // release the lock
	    fclose($fd);
	  } else {
	    $this->log("ERROR: Could not lock calendar database for writing");
	    echo "FILE ERROR: Could not lock temp file (function 'write_iDB')";
	  }
	  $this->log("Writing image database");
	}
	//
	// WRITE EMAIL DATABASE
	//
	function write_iDB(){
		if (count($this->addresses) > 0) {
		  unset($buffer);
		  for ($i=0; $i < count($this->addresses); $i++){
		    for ($j=0; $j < count($this->addresses[$i]); $j++){
		      $buffer .= "*" . $this->addresses[$i][$j];
		    }
		    $buffer .= "\n";
		  $fd = fopen($this->tempfile,"w+");
		  if (flock($fd, LOCK_EX)) { // do an exclusive lock
		    ftruncate($fd, 0);
		    fwrite($fd, $this->autoid . "\n");
		    fwrite($fd, $buffer);
		    flock($fd, LOCK_UN); // release the lock
		    fclose($fd);
		  } else {
		    $this->log("ERROR: Could not lock image database for writing");
		    echo "FILE ERROR: Could not lock temp file (function 'write_iDB')";
		    }
		  }
		  $this->log("Writing image database");
		} else $this->log("ERROR: Writing image database - Image array empty!");
	}
	function log($entry) {
		// DELETE LARGE LOG FILE
		if (is_file($this->logfile) && filesize($this->logfile) > 300000) unlink($this->logfile);
		// CREATE NEW LOGFILE
		if (!is_file($this->logfile)) {
		  $fd = fopen($this->logfile,"a+");
		  fwrite($fd,"FILE LFS (Log File System)\n\n");
		  fwrite($fd,"---LOG BEGIN------------------\n\n");
		  fwrite($fd,date("Ymd, H.i.s : ") . $entry . "\n");
		} else {
		  $fd = fopen($this->logfile,"a+");
		  fwrite($fd,date("Ymd, H.i.s : ") . $entry . "\n");
		}
	}	
}
?>