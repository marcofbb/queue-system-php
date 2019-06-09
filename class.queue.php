<?php

function conectarDB(){
	$dbhost = 'localhost';
	$dbuser = 'root';
	$dbpass = '';
	$dbbase = 'p2pdrive';
	$link = mysqli_connect($dbhost,$dbuser,$dbpass);
			mysqli_select_db($link,$dbbase);
			
	return $link;
}

class queue_admin {
		
	/*
		Maximum processes in parallel
	*/
	
	public $max_process = 10;
	
	/*
		File DB DIR and Name
	*/
	
	private $file_db = __DIR__.'/queue.sqlite3';
	
	/*
		PHP_CLI
		Example
			cPanel: '/usr/bin/php'
			xampp Windows: 'C:\xampp\php\php.exe'
			vestacp: 'php'
	*/
	private $php_cli = 'C:\xampp\php\php.exe';
	
	/*
		File responsible for processing the command
	*/
	private $process_job_file = __DIR__ . '/queue_agent.php';
	
	/*
		Link DB
	*/
	private $linkdb;
	
	function __construct (){
		//
		$this->linkdb = conectarDB();
		mysqli_query($this->linkdb, 'CREATE TABLE IF NOT EXISTS `queue` (
  `id` int(11) NOT NULL,
  `command` text,
  `message` text,
  `time_add_queue` int(11),
  `time_start_process` int(11),
  `time_finish_process` int(11),
  `status` int(11),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;');
	}
	
	public function enqueue($command, $id = null){
		if(empty($id)) $id = md5($command);
		$id = mysqli_real_escape_string($this->linkdb,$id);
		$command = mysqli_real_escape_string($this->linkdb,$command);
		$sql = "SELECT * FROM queue WHERE id = '{$id}' LIMIT 1";
		$sql = mysqli_query($this->linkdb, $sql);
		$row = mysqli_fetch_assoc($sql);
		if(!empty($row)) return false;
		$time = time();
		$sql = "INSERT INTO queue (id, command, time_add_queue, time_start_process, time_finish_process, status) VALUES ('{$id}', '{$command}', '{$time}', 0, 0, 1)";
		mysqli_query($this->linkdb, $sql);
		return $id;
	}
	
	public function run_job($id){
		$id = mysqli_real_escape_string($this->linkdb,$id);
		$sql = "SELECT * FROM queue WHERE id = '{$id}' and status = '1' LIMIT 1";
		$sql = mysqli_query($this->linkdb, $sql);
		$result = mysqli_fetch_assoc($sql);
		if(empty($result)) return false;
		$this->mark_as_process($id);
		$output = $this->exec_command($result['command']);
		$this->set_message($id,$output);
		$this->mark_as_finish($id);
		return true;
	}
	
	public function count_queue_processing(){
		$sql = "SELECT count(*) as total FROM queue WHERE status = '2'";
		$sql = mysqli_query($this->linkdb, $sql);
		$result = mysqli_fetch_assoc($sql);
		return $result['total'];
	}
	
	public function count_queue_pending(){
		$sql = "SELECT count(*) as total FROM queue WHERE status = '1'";
		$sql = mysqli_query($this->linkdb, $sql);
		$result = mysqli_fetch_assoc($sql);
		return $result['total'];
	}
	
	public function count_queue_finish(){
		$sql = "SELECT count(*) as total FROM queue WHERE status = '3'";
		$sql = mysqli_query($this->linkdb, $sql);
		$result = mysqli_fetch_assoc($sql);
		return $result['total'];
	}
	
	public function count_queue_total(){
		$sql = "SELECT count(*) as total FROM queue";
		$sql = mysqli_query($this->linkdb, $sql);
		$result = mysqli_fetch_assoc($sql);
		return $result['total'];
	}
	
	public function process_queue(){
		if($this->can_process()){
			$this->process_next();
		}
	}
	
	public function get_queue_db(){
		$sql = "SELECT * FROM queue";
		$sql = mysqli_query($this->linkdb, $sql);
		$data = array();
		while($row = mysqli_fetch_assoc($sql)){
			$data[] = $row;
		}
		return $data;
	}
	
	public function delete_process_finish(){
		$sql = "DELETE FROM queue WHERE status = '3'";
		$sql = mysqli_query($this->linkdb, $sql);
	}
	
	public function is_dispatcher_run(){
		$f = fopen(__DIR__.'/dispatcher.lock', 'w');
		if(!flock($f, LOCK_EX | LOCK_NB)) {
			return true;
		}
		fclose($f);
		return false;
	}
	
	private function process_job($id){
		$this->execInBackground($this->php_cli.' '.$this->process_job_file.' '.$id);  
	}
	
	private function process_next(){
		$sql = "SELECT * FROM queue WHERE status = '1' LIMIT 1";
		$sql = mysqli_query($this->linkdb, $sql);
		$result = mysqli_fetch_assoc($sql);
		if(empty($result)) return;
		$this->process_job($result['id']);
	}
	
	private function can_process(){
		if($this->max_process - $this->count_queue_processing() > 0) return true;
		return false;
	}
	
	private function mark_as_process($id){
		$this->set_status($id,2);
		$this->set_start_time($id);
	}
	
	private function mark_as_finish($id){
		$this->set_status($id,3);
		$this->set_finish_time($id);
	}
	
	private function set_start_time($id){
		$id = mysqli_real_escape_string($this->linkdb,$id);
		$time = time();
		$sql = "UPDATE queue SET time_start_process = '{$time}' WHERE id = '{$id}' LIMIT 1";
		mysqli_query($this->linkdb, $sql);
	}
	
	private function set_finish_time($id){
		$id = mysqli_real_escape_string($this->linkdb,$id);
		$time = time();
		$sql = "UPDATE queue SET time_finish_process = '{$time}' WHERE id = '{$id}' LIMIT 1";
		mysqli_query($this->linkdb, $sql);
	}
	
	private function set_status($id, $status){
		$id = mysqli_real_escape_string($this->linkdb,$id);
		$status = mysqli_real_escape_string($this->linkdb, $status);
		$sql = "UPDATE queue SET status = '{$status}' WHERE id = '{$id}' LIMIT 1";
		mysqli_query($this->linkdb, $sql);
	}
	
	private function set_message($id, $output){
		$id = mysqli_real_escape_string($this->linkdb,$id);
		$output = mysqli_real_escape_string($this->linkdb, $output);
		$sql = "UPDATE queue SET message = '{$output}' WHERE id = '{$id}' LIMIT 1";
		mysqli_query($this->linkdb, $sql);
	}
	
	private function execInBackground($cmd) {
		if (substr(php_uname(), 0, 7) == "Windows"){
			pclose(popen("start /B ". $cmd, "r")); 
		} else {
			exec($cmd . " > /dev/null &");  
		}
	}
	
	private function exec_command($cmd){
		if (substr(php_uname(), 0, 7) == "Windows"){
			$output = shell_exec($cmd); 
		} else {
			$output = shell_exec($cmd);  
		}
		return $output;
	}
}