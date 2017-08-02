<?php

namespace RemExec\Api;

class RemExec {
	public function __construct($addr){
		$this->connection = new Connection($addr);
	}

	public function __destruct(){
		$this->connection->close();
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
		return urlencode($val);
	}

	private function decodeParameter($val){
		return urldecode($val);
	}
}
