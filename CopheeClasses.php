<?php

abstract class Node {
	public $line;
}

class Symbol extends Node{
	public $name;
	public function __construct($v = '') {$this->name = $v;}
}

class SymbolVariable extends Symbol {
	public $type;
	public $value;
	public $static = false;
}

class SymbolMethod extends Symbol {
	public $argc = 0;
	public $argv = array();
	public $static = false;
	public $returnType;
	public $overloads; //SymbolMethod
	public $abstract = false;
	public $final = false;
	public $access;
}

class SymbolClass extends Symbol {
	public $father; //SymbolClass
	public $methods; //Array SymbolMethods
	public $variables; //Array SymbolVariable
	public $abstract = false;
	public $final = false;
}

class Program {
	public $var;
	public $class;
	public $next;
}

class NodeType extends Node{
	public $token;

	public function __construct($t, $l) {
		parent::__construct($l);
		$this->token = $t;
	}
}

class NodeExpr extends Node {
	public $token;
	public $first;
	public $last;

	public function __construct($token, $first, $last, $line) {
		parent::__construct($line);
		$this->token = $token;
		$this->first = $first;
		$this->last  = $last;
	}
}

class NodeNew extends Node {
	public $symbol;
	public function __construct(Symbol &$symbol, $line) {
		$this->symbol = $symbol;
		$this->line = $line;
	}
}

abstract class NodeValue extends Node {
	public $value;

	public function __construct($v, $l) {
		parent::__construct($l);
		$this->value = $v;
	}
}

class NodeStmt extends Node {
	public $scope;
	public $next;

	public function __construct($scope, $line){
		$this->scope = $scope;
		$this->line = $line;
	}
}

class NodePrimitive extends NodeValue{}

class NodeNegative extends NodeValue{}

class NodeNegation extends NodeValue {}

class nodeIf {}
class nodeFor {}
class nodeWhile {}
class nodeRoot {}
class nodeName {}