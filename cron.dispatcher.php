<?php 
/*
	Run this file every 5 minutes
*/

require(__DIR__ . '/class.queue.php');

set_time_limit(0);

$f = fopen(__DIR__.'/dispatcher.lock', 'w') or die ('The file could not be created agente_colas.lock');
if(!flock($f, LOCK_EX | LOCK_NB)) {
    die ('The file is already open.');
}

$cola = new queue_admin();

while(true){
	$cola->process_queue();
	sleep(2);
}