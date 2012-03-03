<?php
class Symbol {
	const A_PUBLIC = 1;
	const A_PROTECTED = 2;
	const A_PRIVATE = 4;

	private $name;
	private $access = self::A_PUBLIC;
}

class SymbolVariable extends Symbol {
	private $type;
}

class SymbolMethod extends Symbol {
	private $argc;
	private $argv = array();
	private $returnType;
	private $overloads; //SymbolMethod
	private $abstract = false;
	private $final = false;
}

class SymbolClass extends Symbol {
	private $father; //SymbolClass
	private $methods; //Array SymbolMethods
	private $variables; //Array SymbolVariable
	private $abstract = false;
	private $final = false;
}

$symbolMap = array();

class Lexical {
	const T_IF = 1;
	const T_ELSE = 2;
	const T_UNLESS = 3;
	const T_FOR = 4;
	const T_FOREACH = 5;
	const T_ELIF = 6;
	const T_SWITCH = 7;
	const T_CASE = 8;
	const T_DEFAULT = 9;
	const T_BREAK = 10;
	const T_CONTINUE = 11;
	const T_LT = 12;
	const T_GT = 13;
	const T_EQUALS = 14;
	const T_AND = 15;
	const T_OR = 16;
	const T_XOR = 17;
	const T_NEG = 18;
	const T_BIN_AND = 19;
	const T_BIN_OR = 20;
	const T_VARIABLE = 21;
	const T_METHOD = 22;
	const T_CLASS = 23;
	const T_PUBLIC = 24;
	const T_PROTECT = 25;
	const T_PRIVATE = 26;
	const T_WHILE = 27;
	const T_DO = 28;
	const T_RETURN = 29;
	const T_INT = 30;
	const T_STRING = 31;
	const T_BOOLEAN = 32;
	const T_EOF = 33;
	const T_NUMERIC = 34;
	const T_REAL = 35;
	const T_ADD = 36;
	const T_SUB = 37;
	const T_IS = 38;
	const T_ISNT = 39;
	const T_ID = 40;
	const T_ASSIGN = 41;

	private $parser;
	private $word = '';
	private $regex = '/[a-zA-Z_]/';

	public function __construct ($file) {
		$this->parser = new Parser($file);
	}

	private function findToken($word) {
		var_dump($word);
		switch($word) {
			case is_numeric($word):
				return self::T_NUMERIC;
			case '+':
				return self::T_ADD;
			case '-':
				return self::T_SUB;
			case 'is':
				return self::T_IS;
			case 'isnt':
				return self::T_ISNT;
			case 'public':
				return self::T_PUBLIC;
			case 'protected':
				return self::T_PROTECT;
			case 'private':
				return self::T_PRIVATE;
			case 'while':
				return self::T_WHILE;
			case 'if':
				return self::T_IF;
			case 'else':
				return self::T_ELSE;
			case 'for':
				return self::T_FOR;
			case 'foreach':
				return self::T_FOREACH;
			case 'elif':
				return self::T_ELIF;
			case 'switch':
				return self::T_SWITCH;
			case 'case':
				return self::T_CASE;
			case 'default':
				return self::T_DEFAULT;
			case 'break':
				return self::T_BREAK;
			case 'continue':
				return self::T_CONTINUE;
			case '<':
				return self::T_LT;
			case '>':
				return self::T_GT;
			case '&&':
				return self::T_AND;
			case '||':
				return self::T_OR;
			case 'xor':
				return self::T_XOR;
			case '!':
				return self::T_NEG;
			case '&':
				return self::T_BIN_AND;
			case '|':
				return self::T_BIN_OR;
			case 'public':
				return self::T_PUBLIC;
			case 'protected':
				return self::T_PROTECT;
			case 'private':
				return self::T_PRIVATE;
			case 'while':
				return self::T_WHILE;
			case 'do':
				return self::T_DO;
			case 'return':
				return self::T_RETURN;
			case 'int':
				return self::T_STRING;
			case 'bool':
				return self::T_BOOLEAN;
			//case '':return self::T_EOF;
			case 'is':
				return self::T_IS;
			case 'isnt':
				return self::T_ISNT;
			case '=':
				return self::T_ASSIGN;
			default:
				return self::T_ID;
		}
	}

	public function getToken () {
		list($current, $next) = $this->parser->read();
		$this->word .= $current;
		if((preg_match($this->regex, $next) || is_numeric($next)) && (preg_match($this->regex, $current) || is_numeric($current))) {
			return $this->getToken();
		}else {
			$token = $this->findToken($this->word);
			$this->word = '';
			return $token;
		}
	}
}

class Parser {
	private $file;
	private $fd;
	private static $size = 256;
	private $pointer = 0;
	private $marker = 0;
	private $source;

	public function __construct ($file) {
		$this->file = $file;
		$this->fd = fopen($file, 'r');
		//var_dump($file, $this->fd);
	}

	public function read () {
		$block = $this->reader();
		//var_dump($block, $block[$this->pointer]);
		return array($block[$this->pointer], $block[++$this->pointer]);
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
}

$lexical = new Lexical('./test.coffee');
var_dump($lexical->getToken(), $lexical->getToken());
