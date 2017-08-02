<?php

require_once '../vendor/autoload.php';

use RemExec\Api\RemExec;

$rx = new RemExec('localhost:3500');
echo 'SendFile: '.$rx->sendFileStream('file.txt', 'The very secret file')."\n\n";
echo 'ExecTask: '.$rx->execTask('echo', [1, 2, 3], function($stream, $body){
	echo "> Stream(".$stream.", ".strlen($body).")\n";
	echo $body."\n\n";
})."\n\n";
echo 'FetchFile: '.$rx->fetchFileStream('file.txt')."\n\n";
$rx->close();
