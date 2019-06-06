<?php 

require(__DIR__ . '/class.queue.php');

set_time_limit(0);
$id = null;
if(isset($_GET['id'])) $id = $_GET['id'];
if(empty($id) and isset($argv) and is_array($argv)) $id = $argv[1];
if(empty($id)) exit();

$cola = new queue_admin();
$cola->run_job($id);