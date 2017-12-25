<?php

namespace RemExec\Api;

class Connection {
	private $conn;

	public function __construct($addr, $timeout){
		$this->conn = stream_socket_client($addr, $errno, $errstr, $timeout);
		if (!$this->conn){
			throw new \Exception("Can't connect to RemExec: $errstr ($errno)");
		}

		stream_set_timeout($this->conn, $timeout);
	}

	public function __destruct(){
		$this->close();
	}

	public function write($str){
		$written = 0;
		$cnt = strlen($str);
		while (!feof($this->conn) && $written < $cnt){
			$res = fwrite($this->conn, substr($str, $written));
			if ($res === false){
				throw new \Exception("Connection error with RemExec: can't write data");
			}
			$written += $res;
		}
	}

	public function read($n){
		$res = '';

		while (!feof($this->conn) && $n > 0){
			$rd = fread($this->conn, $n);
			if ($rd === false){
				throw new \Exception("Connection error with RemExec: can't read data");
			}
			$n -= strlen($rd);
			$res .= $rd;
		}

		return $res;
	}

	public function readLine(){
		if (feof($this->conn)){
			return null;
		}

		$line = fgets($this->conn);

		if ($line === false){
			throw new \Exception("Connection error with RemExec: can't read data (line)");
		}

		return substr($line, 0, strlen($line) - 1);
	}

	public function close(){
		if ($this->conn){
			fclose($this->conn);
			$this->conn = null;
		}
	}
}
