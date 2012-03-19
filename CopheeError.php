<?php
class Error {
	 public static function log($expected, $found, $line) {
	 	echo "Parser error at line $line, expected $expected, found $found\n";
	 	die();
	 }
}