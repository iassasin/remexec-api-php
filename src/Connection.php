<?php

namespace RemExec\Api;

class Connection {
	private $conn;

	public function __construct($addr){
		$this->conn = stream_socket_client($addr, $errno, $errstr, 5);
		if (!$this->conn){
			throw Exception("Can't connect to RemExec: $errstr ($errno)");
		}

		stream_set_timeout($this->conn, 5);
	}

	public function __destruct(){
		$this->close();
	}

	public function write($str){
		fwrite($this->conn, $str);
	}

	public function read($n){
		if (feof($this->conn)){
			return "";
		}

		return fgets($this->conn, $n);
	}

	public function readLine(){
		if (feof($this->conn)){
			return "";
		}

		return fgets($this->conn);
	}

	public function close(){
		if ($this->conn){
			fclose($this->conn);
			$this->conn = null;
		}
	}
}
