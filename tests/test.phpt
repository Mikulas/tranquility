<?php

use Tester\Assert;


require __DIR__ . '/bootstrap.php';


$in = file_get_contents(__DIR__ . '/fixtures/in.php');
$expected = file_get_contents(__DIR__ . '/fixtures/expected.php');
$f = new \Mikulas\Tranquility\Formatter();

$out = $f->format($in);
Assert::same($expected, $out);
