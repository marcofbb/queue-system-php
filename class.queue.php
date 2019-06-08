<?php 

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
	private $db;
	
	function __construct (){
		// Creo la base de datos para gestionar los procesos 
		$this->db = new PDO('sqlite:'.$this->file_db);
		$this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$this->db->exec("CREATE TABLE IF NOT EXISTS queue (
						id TEXT NOT NULL UNIQUE,
						command TEXT, 
						message TEXT, 
						time_add_queue INTEGER,
						time_start_process INTEGER,
						time_finish_process INTEGER,
						status INTEGER)");
	}
	
	public function enqueue($command, $id = null){
		if(empty($id)) $id = md5($command);
		$stmt = $this->db->prepare("SELECT * FROM queue WHERE id = :id LIMIT 1");
		$stmt->bindValue(':id', $id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		if(!empty($stmt->fetch())) return false;
		
		$stmt = $this->db->prepare("INSERT INTO queue (id, command, message, time_add_queue, time_start_process, time_finish_process, status) VALUES (:id, :command, :message, :time_add_queue, :time_start_process, :time_finish_process, :status)");
		
		$stmt->bindValue(':id', $id);
		$stmt->bindValue(':command', $command);
		$stmt->bindValue(':message', "");
		$stmt->bindValue(':time_add_queue', time());
		$stmt->bindValue(':time_start_process', 0);
		$stmt->bindValue(':time_finish_process', 0);
		$stmt->bindValue(':status', 1);
		$stmt->execute();
		
		return $id;
	}
	
	public function run_job($id){
		$stmt = $this->db->prepare("SELECT * FROM queue WHERE id = :id and status = '1' LIMIT 1");
		$stmt->bindValue(':id', $id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$result = $stmt->fetch();
		if(empty($result)) return false;
		$this->mark_as_process($id);
		$output = $this->exec_command($result['command']);
		$this->set_message($id,$output);
		$this->mark_as_finish($id);
		return true;
	}
	
	public function count_queue_processing(){
		$stmt = $this->db->prepare("SELECT count(*) as total FROM queue WHERE status = '2'");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$result = $stmt->fetch();
		return $result['total'];
	}
	
	public function count_queue_pending(){
		$stmt = $this->db->prepare("SELECT count(*) as total FROM queue WHERE status = '1'");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$result = $stmt->fetch();
		return $result['total'];
	}
	
	public function count_queue_finish(){
		$stmt = $this->db->prepare("SELECT count(*) as total FROM queue WHERE status = '3'");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$result = $stmt->fetch();
		return $result['total'];
	}
	
	public function count_queue_total(){
		$stmt = $this->db->prepare("SELECT count(*) as total FROM queue");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$result = $stmt->fetch();
		return $result['total'];
	}
	
	public function process_queue(){
		if($this->can_process()){
			$this->process_next();
		}
	}
	
	public function get_queue_db(){
		$stmt = $this->db->prepare("SELECT * FROM queue");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$data = array();
		while($row = $stmt->fetch()){
			$data[] = $row;
		}
		return $data;
	}
	
	public function delete_process_finish(){
		$stmt = $this->db->prepare("DELETE FROM queue WHERE status = '3'");
		$stmt->execute();
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
		$stmt = $this->db->prepare("SELECT * FROM queue WHERE status = '1' LIMIT 1");
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
		$result = $stmt->fetch();
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
		$stmt = $this->db->prepare("UPDATE queue SET time_start_process = :time_start_process WHERE id = :id");
		$stmt->bindValue(':time_start_process', time());
		$stmt->bindValue(':id', $id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
	}
	
	private function set_finish_time($id){
		$stmt = $this->db->prepare("UPDATE queue SET time_finish_process = :time_finish_process WHERE id = :id");
		$stmt->bindValue(':time_finish_process', time());
		$stmt->bindValue(':id', $id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
	}
	
	private function set_status($id, $status){
		$stmt = $this->db->prepare("UPDATE queue SET status = :status WHERE id = :id");
		$stmt->bindValue(':status', $status);
		$stmt->bindValue(':id', $id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
	}
	
	private function set_message($id, $output){
		$stmt = $this->db->prepare("UPDATE queue SET message = :message WHERE id = :id");
		$stmt->bindValue(':message', $output);
		$stmt->bindValue(':id', $id);
		$stmt->setFetchMode(PDO::FETCH_ASSOC);
		$stmt->execute();
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