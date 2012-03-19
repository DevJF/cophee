<?php
require_once('./CopheeLexer.php');
require_once('./CopheeError.php');
require_once('./CopheeFirsts.php');
require_once('./CopheeClasses.php');

class Parser {
	public static $symbolMap = array();
	public static $literalMap = array();
	private $lex;
	private $lexer;
	private $lookAhead;
	private $program;
	private $classes = array();
	private function &getSymbol($next = true) {
		$s = self::$symbolMap[$this->lex];

		if($next) $this->assert(206);
		return $s;
	}

	private function curLine() {
		return $this->lexer->curLine();
	}

	public function eat($token) {
		$this->assert($token, true);
	}

	public function validate($token) {
		if($this->lookAhead != $token)
			Error::log($token, $this->lookAhead, $this->curLine());
	}

	public function assert($token, $silent = false) {
		if(!$silent)
			$this->validate($token);
		
		if($token == $this->lookAhead)
			$this->get();
			
	}

	public function get() {
		$this->lookAhead = $this->lexer->getToken();
		$this->lex = $this->lexer->getCurrentLex();
	}

	public function __construct ($file) {
		$this->lexer = new Lexer($file);
	}

	public function parse() {
		$this->program();
		return $this->program;
	}

	private function program(){
		$this->get();
		$this->program = $this->root();
	}
	private function root () {
		$p = new Program();
		switch ($this->lookAhead) {
			case 10: //T_CLASS
				$p->class = $this->class_decl();
				break;
			case 201: //T_END
				return null;
			case 202: //T_TERMINATOR
				$this->assert(202);
				return $this->root();
			default:
				$p->var = $this->stmt_list();
				//Error::log('206 | 10', $this->lookAhead, $this->curLine()); 
		}

		$p->next = $this->root_();
		return $p;
	}

	private function root_ () {
		if($this->lookAhead == 206 || $this->lookAhead == 10 || $this->lookAhead == 202) {
			$aux = $this->root();
			if($aux != null){
				return $aux;
			}
		}
	}

	private function stmt_list() {
		$stmt = $this->stmt();
		if($stmt)
			$stmt->next = $this->stmt_list();
	}

	private function stmt() {
		switch ($this->lookAhead) {
			case 7:
				$if = new NodeIf();
				$this->assert(7);
				$this->eat(46);
				$if->condition = $this->expr();
				$this->eat(47);
				$this->eat(42);
				$if->scope = $this->stmt();
				$this->eat(43);
				if($this->lookAhead == 8) {
					$if->else = $this->else();
				}
				return $if;
			case 20:
				$this->eat(20);
				$while = new NodeWhile();
				$this->eat(46);
				$while->condition = $this->expr();
				$this->eat(47);
				$this->eat(42);
				$while->scope = $this->stmt();
				$this->eat(43);
				return $while;
			case 5:
					$for = new NodeFor();
				$this->eat(5);
				$this->eat(46);
				$for->condition = $this->expr();
				$this->eat(47);
				$this->eat(42);
				$for->scope = $this->stmt();
				$this->eat(43);
				return $for;
			case 4:
				$switch =  new NodeSwitch();
				$this->eat(4);
				$this->eat(46);
				$switch->condition = $this->expr();
				$this->eat(47);
				$this->eat(42);
				$swtich->scope = $this->case_list();
				$this->eat(43);
				return $switch;
			case 17:
				$_this = new NodeThis();
				$_this->value = $this->expr();
				return $_this;
			case 18:
				$return = new NodeReturn();
				$return->value = $this->expr();
				return $return;
			case 206:
				return $this->expr_or_decl();
			default:
				return null;
		}
	}

	private function expr_or_decl() {
		switch($this->lookAhead) {
			case 23:
			case 24:
			case 25:
			case 26:
			case 27:
				return $this->local_decl();
			case 206:
				if(array_key_exists($this->lex, $this->classes))
					return $this->local_decl();
				else
					return $this->expr();
		}
	}
	private function class_decl() {
		$c = new SymbolClass();
		$this->eat(10);
		$c->name = &$this->getSymbol();
		$c->father = &$this->_extends();
		$this->classes[$c->name->name] = &$c;
		return $c;
	}

	private function &_extends() {
		if($this->lookAhead == 28){
			$this->assert(28);
			return $this->getSymbol();
		}

		return null;
	}

	private function local_decl() {
		$static = $this->_static();
		$type = $this->type_or_id();
		$a = array();
		$this->id_list($type, $static, $a);
		return $a;
	}

	private function id_list($type, $static, &$a) {
		$v = new SymbolVariable();
		$v->type = $type;
		$v->static = $static;
		$v->name = &$this->getSymbol();
		
		switch($this->lookAhead){
			case 40://T_ASSIGN
				$v->value = $this->assign();
				break;
			case 52://T_COMMA
				$this->id_list($type, $a);
				break;
		}

		$a[] = $v;
	}

	private function assign() {
		$this->eat(40);
		return $this->expr();
	}

	private function _static(){
		$r = $this->lookAhead == 29;
		if($r) $this->eat(29);
		return $r;
	}

	private function type_or_id() {
		$t = $this->lookAhead;
		switch ($t) {
			case 23:
				$this->eat(23);
			case 24:
				$this->eat(24);
			case 25:
				$this->eat(25);
			case 26:
				$this->eat(26);
			case 27:
				$this->eat(27);
				return new NodeType($t, $this->curLine());
			case 206:
				return new NodeType($this->getSymbol(), $this->curLine());
		}
	}

	private function id () {
		$this->id_();
	}
	private function id_ () {
		
	}
	private function expr() {
		switch($this->lookAhead) {
			case 22:
				return $this->expr_new();
			default:
				return $this->priority2();
		}
	}
	private function expr_new(){
		$this->assert(22);
		$n = &$this->getSymbol();
		return new NodeNew($n, $this->curLine());
	}
	private function priority2(){
		$first = $this->priority3();
		if($this->lookAhead == 83) return new NodeExpr($this->lookAhead, $first, $this->priority2_(), $this->curLine());
		return $first;
	}

	private function priority2_(){
		$this->eat(83);
		$first = $this->priority3();
		if($this->lookAhead == 83) return new NodeExpr($this->lookAhead, $first, $this->priority2_(), $this->curLine());
		return $first;
	}

	private function priority3(){
		$first = $this->priority4();
		if(($this->lookAhead == 84) return new NodeExpr($this->lookAhead, $first, $this->priority3_(), $this->curLine());
		return $first;
	}

	private function priority3_(){
		$this->eat(84);
		$first = $this->priority4();
		if($this->lookAhead == 84) return new NodeExpr($this->lookAhead, $first, $this->priority3_(), $this->curLine());
		return $first;
	}

	private function priority4(){
		$first = $this->priority5();
		if($this->lookAhead == 75 || $this->lookAhead == 76 || $this->lookAhead == 100 || $this->lookAhead == 101) return new NodeExpr($this->lookAhead, $first, $this->priority4_(), $this->curLine());
		return $first;
	}

	private function priority4_() {
		$this->eat($this->lookAhead);
		$first = $this->priority5();
		if($this->lookAhead == 75 || $this->lookAhead == 76 || $this->lookAhead == 100 || $this->lookAhead == 101) return new NodeExpr($this->lookAhead, $first, $this->priority4_(), $this->curLine());
		return $first;
	}
	private function priority5(){
		$first = $this->priority6();
		if($this->lookAhead == 54 || $this->lookAhead == 55 || $this->lookAhead == 70 || $this->lookAhead == 71) return new NodeExpr($this->lookAhead, $first, $this->priority5_(), $this->curLine());
		return $first;
	}

	private function priority5_(){
		$this->eat($this->lookAhead);
		$first = $this->priority6();
		if($this->lookAhead == 54 || $this->lookAhead == 55 || $this->lookAhead == 70 || $this->lookAhead == 71) return new NodeExpr($this->lookAhead, $first, $this->priority5_(), $this->curLine());
		return $first;
	}

	private function priority6(){
		$first = $this->priority7();
		if($this->lookAhead == 56 || $this->lookAhead == 50 || $this->lookAhead == 49) return new NodeExpr($this->lookAhead, $first, $this->priority6_(), $this->curLine());
		return $first;
	}

	private function priority6_(){
		$this->eat($this->lookAhead);
		$first = $this->priority7();
		if($this->lookAhead == 56 || $this->lookAhead == 50 || $this->lookAhead == 49) return new NodeExpr($this->lookAhead, $first, $this->priority6_(), $this->curLine());
		return $first;
	}

	private function priority7(){
		$first = $this->priority8();
		if($this->lookAhead == 57 || $this->lookAhead == 30 || $this->lookAhead == 58 || $this->lookAhead == 59) return new NodeExpr($this->lookAhead, $first, $priority7_(), $this->curLine());
		return $first;
	}
	
	private function priority7_(){
		$this->eat($this->lookAhead);
		$first = $this->priority8();
		if($this->lookAhead == 57 || $this->lookAhead == 30 || $this->lookAhead == 58 || $this->lookAhead == 59) return new NodeExpr($this->lookAhead, $first, $priority7_(), $this->curLine());
		return $first;
	}

	private function priority8(){
		$first = $this->priority9();
		if($this->lookAhead == 31 || $this->lookAhead == 85) return new NodeExpr($this->lookAhead, $first, $this->priority8_(), $this->curLine());
		return $first;
	}

	private function priority8_(){
		$this->eat($this->lookAhead);
		$first = $this->priority9();
		if($this->lookAhead == 31 || $this->lookAhead == 85) return new NodeExpr($this->lookAhead, $first, $this->priority8_(), $this->curLine());
		return $first;
	}

	private function priority9(){
		switch ($this->lookAhead) {
			case 60: // T_NEG (!)
				$this->eat(60);
				return new NodeNegation($this->primary(), $this->curLine());
			case 49: // T_MINUS (-)
				$this->eat(49);
				return new NodeNegative($this->primary(), $this->curLine());
			case  50: // T_PLUS(+)
				$this->eat(50);
				return $this->primary();
		}
	}

	private function primary() {
		switch ($this->lookAhead) {
			case 208: //T_LITERAL
			case 205: //T_NUMBER
				$primary = new NodePrimitive($this->token, $this->lex, $this->curLine());
				$this->eat($this->token);
				break;
			case 13: //T_TRUE
			case 14: //T_FALSE
				$primary = new NodePrimitive($this->token, $this->curLine());
				$this->eat($this->token);
				break;
			case 206: //T_ID
				$name = new NodeName($this->getSymbol(), $this->curLine());
				$this->eat(206);
				return $name;
			case 46: //T_PARENT_L
				$this->assert(46);
				$primary = $this->expr();
				$this->assert(47); //T_PARENT_R
			case 17: //T_THIS
				$this->assert(17);
				$primary = new NodeThis($this->curLine);
				$primary->attr_expr = $this->expr();
		}

		return $primary;
	}

	private function struct($node) {
		switch($this->lookAhead) {
			case 44: //[ ???
				$this->eat(44);
				$expr = $this->expr();
				$this->eat(45);
				return new NodeVector($node, $expr, $this->curLine());
			case 46: //T_PARENT_L
			case 206: //T_ID

			case 51: //T_DOT
		}
	}
}
