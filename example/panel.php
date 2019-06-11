<?php 
require(__DIR__ . '/../class.queue.php');

$cola = new queue_admin();

if(isset($_GET['do']) and !empty($_GET['do'])){
	$do = $_GET['do'];
	$id_job = time().rand(0,1000);
	if($do == 'add-demo-process1'){
		$cola->enqueue('C:\xampp\php\php.exe '.__DIR__.'/exec/write_time_in_txt.php', $id_job);
	} elseif($do == 'add-demo-process2'){
		$cola->enqueue('C:\xampp\php\php.exe '.__DIR__.'/exec/wait_30s.php', $id_job);
	} elseif($do == 'exec-one-queue') {
		$cola->process_queue();
	} elseif($do == 'delete-all-process-finish'){
		$cola->delete_process_finish();
	}
	header('location: panel.php');
}
?>
<!DOCTYPE html>
<html>
<head>
<style>
table {
  font-family: arial, sans-serif;
  border-collapse: collapse;
  width: 100%;
}

td, th {
  border: 1px solid #dddddd;
  text-align: left;
  padding: 8px;
}

tr:nth-child(even) {
  background-color: #dddddd;
}
</style>
</head>
<body>

<h2>Demo</h2>
<ul>
	<li><a href="?do=add-demo-process1">Add Demo Process [Write txt]</a></li>
	<li><a href="?do=add-demo-process2">Add Demo Process [Wait 30s]</a></li>
	<li><a href="?do=exec-one-queue">Execute a single process from the queue</a></li>
	<li><a href="?do=delete-all-process-finish">Delete all process finish</a></li>
</ul>

<h2>Queue Info</h2>
<ul>
	<li>Dispatcher Run: <?php if($cola->is_dispatcher_run()) echo 'yes'; else echo 'no'; ?></li>
	<li>Pending: <?php echo $cola->count_queue_pending(); ?></li>
	<li>Processing: <?php echo $cola->count_queue_processing(); ?></li>
	<li>Finish: <?php echo $cola->count_queue_finish(); ?></li>
	<li>Total: <?php echo $cola->count_queue_total(); ?></li>
</ul>

<h2>Queue Data</h2>

<table>
	<tr>
		<th>ID</th>
		<th>command</th>
		<th>message</th>
		<th>time_add_queue</th>
		<th>time_start_process</th>
		<th>time_finish_process</th>
		<th>wake_up</th>
		<th>status</th>
	</tr>
<?php 
	$data = $cola->get_queue_db(); 
	if(!empty($data)): foreach($data as $d): 
?>
	<tr>
		<td><?=$d['id']?></td>
		<td><small><?=$d['command']?></small></td>
		<td><small><?=$d['message']?></small></td>
		<td><?=$d['time_add_queue']?></td>
		<td><?=$d['time_start_process']?></td>
		<td><?=$d['time_finish_process']?></td>
		<td><?=$d['time_wake_up']?></td>
		<td><?=$d['status']?></td>
	</tr>
<?php endforeach; endif; ?>
</table>
</body>
</html>