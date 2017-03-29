<?php 
class Protomessage {
	private $raw_data;
	private $position = 0;
	private $length;
	private $children = [];
	private $decoded = 0;
	function __construct($data = '') {
		$this->raw_data = $data;
		$this->position = 0;
		$this->length = strlen($data);
		$this->decoded = 0;
	}
	function load($data) {
		$this->raw_data = $data;
		$this->position = 0;
		$this->length = strlen($data);
		$this->decoded = 0;
	}
	private function cur() {
		return ord($this->raw_data[$this->position]);
	}
	private function next() {
		if ($this->position > $this->length) throw new Exception('Out of range');
		return $this->raw_data[$this->position++];
	}
	private function readULEB128() {
		$tmp = 0; $base = 1;
		for(; $this->cur() & 128; $base <<= 7, $this->position++)
			$tmp += ($this->cur() & 127) * $base;
		$tmp += ($this->cur() & 127) * $base;
		$this->position++;
		return $tmp;
	}
	private function readType() {
		$cur = $this->readULEB128();
		$index = $cur >> 3;
		$type = $cur & 7;
		return [$index, $type];
	}
	private function decode() {
		if ($this->decoded) return $this->children;
		$this->position = 0;
		while($this->position < $this->length) {
			list($index, $type) = $this->readType();
			switch($type) {
				case 0: {
					$node = $this->readULEB128();
					break;
				}
				case 1: {
					$node_ = '';
					for($i=0;$i<8;$i++) $node_ .= $this->next();
					$node = unpack("Q", $node_)[1];
					break;
				}
				case 2: {
					$num = $this->readULEB128();
					$node_ = '';
					for($i=0;$i<$num;$i++) $node_ .= $this->next();
					$node = new ProtoMessage($node_);
					break;
				}
				case 5: {
					$node_ = '';
					for($i=0;$i<4;$i++) $node_ .= $this->next();
					$node = unpack("L", $node_)[1];
					break;
				}
			}
			$this->children[$index][] = $node;
		}
		$this->decoded = 1;
		return $this->children;
	}

	public function str() { return $this->raw_data; }
	public function get($index, $n = 0) {
		if (!$this->decoded) $this->decode();
		return $this->children[$index][$n];
	}
	public function get_all($index) {
		if (!$this->decoded) $this->decode();
		return $this->children[$index];
	}
}
