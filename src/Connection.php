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
		if (!feof($this->conn)){
			fwrite($this->conn, $str);
		}
	}

	public function read($n){
		if (feof($this->conn)){
			return "";
		}

		return fread($this->conn, $n);
	}

	public function readLine(){
		if (feof($this->conn)){
			return null;
		}

		$line = fgets($this->conn);
		return substr($line, 0, strlen($line) - 1);
	}

	public function close(){
		if ($this->conn){
			fclose($this->conn);
			$this->conn = null;
		}
	}
}
