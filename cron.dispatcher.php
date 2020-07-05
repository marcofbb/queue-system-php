<?php 

if (php_sapi_name() != 'cli') {
    throw new Exception('This application must be run on the command line.');
}

require(__DIR__ . '/class.queue.php');

$f = fopen(__DIR__.'/dispatcher.lock', 'w') or die ('The file could not be created dispatcher.lock');
if(!flock($f, LOCK_EX | LOCK_NB)) {
    die ('The file is already open.');
}

$cola = new queue_admin();

while(true){
	$cola->process_queue();
}
