# Light Task Queues System PHP
Lightweight queue system that performs work in the background, with a central agent that is responsible for executing and managing processes.

You can configure the number of processes in parallel to process, and the time from which the system can process the task.

## Configure

### Database
The database must be configured in the file class.queue.php

### CLI php
You must configure the PHP binary file path in class.queue.php

```
/*
		PHP_CLI
		Example
			cPanel: '/usr/bin/php'
			xampp Windows: 'C:\xampp\php\php.exe'
			vestacp: 'php'
*/
private $php_cli = 'php';
```

### Maximum processes in parallel
You must configure the maximum number of processes in parallel in class.queue.php
```
/*
	Maximum processes in parallel
*/
	
public $max_process = 10;
```

### Cronjob
Add cron with execute PHP cron.dispatcher.php every five minutes.
```
*/5 * * * * /usr/bin/php /home/username/public_html/cron.dispatcher.php >/dev/null 2>&1
```

### Interesting
If you want a more advanced system, you may be interested in this other system called beanstalkd https://beanstalkd.github.io/

# Sistema liviano de colas PHP
Sistema de colas liviano que realiza el trabajo en segundo plano, con un agente central que se encarga de ejecutar y administrar los procesos. 

Se puede configurar la cantidad de procesos en paralelo a procesar, y el horario desde el cual el sistema puede procesar la tarea.

## Configuracion

### Base de datos
Se debe configurar la base de datos en el archivo class.queue.php

### CLI php
Se debe configurar la ruta del archivo binario de PHP en class.queue.php

```
/*
		PHP_CLI
		Example
			cPanel: '/usr/bin/php'
			xampp Windows: 'C:\xampp\php\php.exe'
			vestacp: 'php'
*/
private $php_cli = 'php';
```

### Cantidad máxima de procesos en paralelo
Se debe configurar la cantidad máxima de procesos en paralelos en class.queue.php
```
/*
	Maximum processes in parallel
*/
	
public $max_process = 10;
```

### Cronjob
Agregar un cronjob para ejecutar el archivo cron.dispatcher.php cada cinco minutos
```
*/5 * * * * /usr/bin/php /home/username/public_html/cron.dispatcher.php >/dev/null 2>&1
```

### Interesante
Si desea un sistema mas avanzado, puede ser de tu interes beanstalkd https://beanstalkd.github.io/
