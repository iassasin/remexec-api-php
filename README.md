# RemExec API for PHP
Provide basic commands for RemExec. Required composer to generate autoload.php.

## Connection
```php
use RemExec\Api\RemExec;
$rx = new RemExec('localhost:3500', 5); // timeout = 5 sec.
//...work with $rx...
$rx->close();
```

## Send file streams to server's sandbox
```php
$rx->sendFileStream('file.txt', 'The very secret file');
```
Arguments:
* (string) `$name` - file name to store on server;
* (string) `$content` - file contents.

Returns:
* (int) `0` - file successfully transferred;
* (int) `5` - can't create file.

## Execute remote task
```php
$rx->execTask('taskName', ['arg1', 'arg2', 'arg3'], function($stream, $body){
	echo "> Stream(".$stream.", ".strlen($body).")\n";
	echo $body."\n\n";
});
```
Arguments:
* (string) `$task` - name of task on remote server;
* (array) `$args` - arguments to pass to task;
* (callable) `$callback` - function called when received data from executing task. Takes 2 arguments:
	* (int) `$stream` - number of stream (`1` - stdout, `2` - stderr);
	* (string) `$body` - stream contents.

Returns:
* (bool) `false` - unexpected unknown server error;
* (int) `0` - task executed successfully;
* (int) `2` - task not found.

## Fetch files from server's sandbox
```php
$rx->fetchFileStream('file.txt');
```
Arguments:
* (string) `$name` - file name on remote server;

Returns:
* (null) - file not found;
* (string) - file contents.
