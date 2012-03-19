<?php

class Lexer {
	const T_END = 201;
	const T_TERMINATOR = 202;
	const T_RANGE_IN = 203;
	const T_RANGE_OUT = 204;
	const T_NUMBER = 205;
	const T_ID = 206;
	const T_ERROR = 207;
	const T_LITERAL = 208;
	const T_INDENT = 209;
	const T_OUTDENT = 210;

	private static $TYPES = array(
		'int',
		'string',
		'bool',
		'float',
		'void',
	);


	private static $COPHEE_SC_MAP = array(
		'is' => '==',
	   	'isnt' =>'!=',
	    'not' => '!',
	    'yes' => 'true',
	    'no' => 'false',
	    'on' => 'true',
	    'off' => 'false',
	    '@' => 'this',
	    'undefined' => 'null',
	);

	private static $RESERVED = array(
		'case' => 1,
		'default' => 2,
		'var' => 3,
		'switch' => 4,
		'for' => 5,
		'in' => 6,
		'if' => 7,
		'else' => 8,
		'elif' => 9,
		'class' => 10,
		'constructor' => 11,
		'mixed' => 12,
		'true' => 13,
		'false' => 14,
		'null' => 15,
		'function' => 16,
		'this' => 17,
		'return' => 18,
		'break' => 19,
		'while' => 20,
		'do' => 21,
		'new' => 22,
		'int' => 23,
		'bool' => 24,
		'void' => 25,
		'array' => 26,
		'string' => 27,
		'extends' => 28,
		'static' => 29,
		'DIV' => 30,
		'SQRT' => 31,
	);

	private static $SINGLE = array(
		"=" => 40,
		":" => 41,
		"{" => 42,
		"}" => 43,
		"[" => 44,
		"]" => 45,
		"(" => 46,
		")" => 47,
		"?" => 48,
		"-" => 49,
		"+" => 50,
		'.' => 51,
		',' => 52,
		'^' => 53,
	 	'>' => 54,
	 	'<' => 55,
	 	'&' => 56,
	 	'|' => 57,
	 	'*' => 58,
	 	'/' => 59,
	 	'!' => 60,
	);

	private static $DOUBLE = array(
		'<=' => 70,
		'>=' => 71,
		'->' => 72,
		'<<' => 73,
		'>>' => 74,
		'!=' => 75,
		'==' => 76,
		'..' => 77,
		'++' => 78,
		'--' => 79,
		'+=' => 80,
		'-=' => 81,
		'|=' => 82,
		'&&' => 83,
		'||' => 84,
		'**' => 85,
	);

	private static $TRIPLE = array(
		'!==' => 100,
		'===' => 101,
		'>>>' => 102,
		'<<<' => 103,
		'...' => 104,
	);

	private static $size = 256;
	private $file;
	private $fd;
	private $pointer = 0;
	private $marker = 0;
	private $source;
	private $lex = '';
	private $line = 1;
	private $regex = '/[a-zA-Z_]/';
	private $indentCount = array();

	public function __construct ($file) {
		$this->file = $file;
		$this->fd = fopen($file, 'r');
	}

	private function findToken() {
		if(is_numeric($this->lex)){
			return self::T_NUMBER;
		}else if(isset(self::$SINGLE[$this->lex])) {
			return self::$SINGLE[$this->lex];
		}else if(isset(self::$DOUBLE[$this->lex])){
			return self::$DOUBLE[$this->lex];
		}else if(isset(self::$TRIPLE[$this->lex])){
			return self::$TRIPLE[$this->lex];
		}else if(preg_match('/\d\.\.(\.)?\d/', $this->lex)){
			return count(explode('...', $this->lex)) === 2 ? self::T_RANGE_OUT : self::T_RANGE_IN;
		}else if($this->lex === "\n"){
			$this->line ++;
			return self::T_TERMINATOR;
		}else if($this->lex === "\t"){
			$this->indentCount[$this->line] =  array_keys_exists($this->line, $this->indentCount)  ? $this->indentCount[$this->line] + 1 : 1;
			return self::T_INDENT;
		}else{
				if(isset($COPHEE_SC_MAP[$this->lex])) {
					$this->lex = $COPHEE_SC_MAP[$this->lex];
				}

				if(isset(self::$RESERVED[$this->lex])){
					return self::$RESERVED[$this->lex];
				}
				if(!array_key_exists($this->lex, Parser::$symbolMap))
					Parser::$symbolMap[$this->lex] = new Symbol($this->lex);
				return self::T_ID;
		}
	}
		
	public function getCurrentLex() {
		return $this->lex;
	}

	public function getToken () {
		$this->lex = $this->read();
		
		switch ($this->lex) {
			case ' ':
				return $this->getToken();
			case '':
				return self::T_END;
			case is_numeric($this->lex):
				while(preg_match('/[0-9.,]/', ($_char = $this->read())))
					$this->lex .= $_char;
				$this->back();
				break;
			case (bool)preg_match('/[a-zA-Z_]/', $this->lex):
				while(preg_match('/[a-zA-Z_0-9]/', ($_char = $this->read())))
					$this->lex .= $_char;
				$this->back();
				break;
			case '"':
				$this->lex = '';
				while(($_char = $this->read()) !== '"' && $_char !== ''){
					$this->lex .= $_char;
				}
				if($_char === '') return self::T_ERROR;
				$literalMap[] = $this->lex;
				return self::T_LITERAL;
			case "'":
				$this->lex = '';
				while(($_char = $this->read()) !== "'" && $_char !== ''){
					$this->lex .= $_char;
				}
				if($_char === '') return self::T_ERROR;
				$literalMap[] = $this->lex;
				return self::T_LITERAL;
			default:
				$_char = $this->read();

				if(isset(self::$DOUBLE[$this->lex.$_char])){
					$this->lex .= $_char;
					$_char = $this->read();
					if(isset(self::$TRIPLE[$this->lex.$_char])){
						$this->lex .= $_char;
					}else{
						$this->back();
					}
				}else
					$this->back();
		}
		return $this->findToken();
	}

	public function curLine() {
		return $this->line;
	}
	
	private function read () {
		$block = $this->reader();
		return $block[$this->pointer++];
	}

	private function reader () {
		//Returns pieces of code
		if($this->pointer == self::$size || $this->pointer === 0) {
			fseek($this->fd, $this->marker);
			$this->source = fread($this->fd, self::$size);
			$this->marker += self::$size;
			$this->pointer = 0;
		}

		return $this->source;
	}

	private function back() {
		$this->pointer --;
	}
}
