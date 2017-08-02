<?php

namespace RemExec\Api;

class RemExec {
	public function __construct($addr){
		$this->connection = new Connection($addr);
	}

	public function __destruct(){
		$this->connection->close();
	}

	public function close(){
		$this->sendCommand('EXIT');
		$this->connection->close();
	}

	public function sendFileStream($name, $body){
		$this->sendCommand('FILE', [], [
			'Name' => $name,
			'Size' => strlen($body),
		]);
		$this->sendBody($body);

		$resp = $this->readCommand();
		if ($resp['command'] == 'OK'){
			return 0;
		}
		else if ($resp['command'] == 'ERROR'){
			return +$resp['args'][0];
		}
	}

	public function fetchFileStream($name){
		$this->sendCommand('FETCH', [$name]);

		$resp = $this->readCommand();
		if ($resp['command'] != 'FILE'){
			return null;
		}

		return $this->readBody(+$resp['params']['Size']);
	}

	public function execTask($task, $args, $callback){
		if (count($args) > 0){
			$args = ['Arguments' => $args];
		}

		$this->sendCommand('EXEC', [$task], $args);

		$resp = $this->readCommand();
		if ($resp['command'] == 'ERROR'){
			return +$resp['args'][0];
		}
		else if ($resp['command'] != 'OK'){
			return false;
		}

		while (true){
			$resp = $this->readCommand();
			if ($resp['command'] == '' || $resp['command'] == 'END'){
				break;
			}
			else if ($resp['command'] == 'STREAM'){
				$body = $this->readBody(+$resp['params']['Size']);
				if (is_callable($callback)){
					$callback(+$resp['args'][0], $body);
				}
			}
			else {
				//unexcepted server command
				break;
			}
		}

		return 0;
	}

	private function readCommand(){
		$line = $this->connection->readLine();
		if ($line === null){
			return ['command' => ''];
		}

		$parts = preg_split('/\s+/', $line, 2);
		$cmd = $parts[0];
		$args = count($parts) > 1 ? [$parts[1]] : [];

		$params = [];
		while (true){
			$line = $this->connection->readLine();
			if ($line === null || $line == ''){
				break;
			}

			$param = preg_split('/:\s*/', $line, 2);
			if (count($param) == 2){
				$params[$param[0]] = $this->decodeParameter($param[1]);
			}
		}

		return [
			'command' => $parts[0],
			'args' => $args,
			'params' => $params,
		];
	}

	private function readBody($size){
		$body = $this->connection->read($size);
		$this->connection->read(2); //read \n\n
		return $body;
	}

	private function sendBody($data){
		$this->connection->write($data."\n\n");
	}

	private function sendCommand($command, $args = [], $params = []){
		$this->connection->write($command);
		if (count($args) > 0){
			$this->connection->write(' '.join(' ', array_map([$this, 'encodeParameter'], $args)));
		}
		$this->connection->write("\n");

		foreach ($params as $name => $param){
			if (is_array($param)){
				$val = join(';', array_map([$this, 'encodeParameter'], $param));
			} else {
				$val = $this->encodeParameter($param);
			}
			$this->connection->write($name.': '.$val."\n");
		}

		$this->connection->write("\n");
	}

	private function encodeParameter($val){
		return preg_replace_callback('/[%; \t\n\r]/', function($v){ return '%'.bin2hex($v[0]); }, $val);
	}

	private function decodeParameter($val){
		return preg_replace_callback('/%(\d\d)/', function($v){ return hex2bin($v[1]); }, $val);
	}
}
