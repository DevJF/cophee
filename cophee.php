<?php
require_once("./CopheeParser.php");

$parser = new Parser('./test.cophee');
echo str_replace(',{', ",\n{", json_encode($parser->parse())).PHP_EOL;
