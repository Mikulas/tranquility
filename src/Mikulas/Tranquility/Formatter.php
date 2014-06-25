<?php

namespace Mikulas\Tranquility;

use PhpParser;


class Formatter
{

	public function __construct()
	{
		$this->parser = new PhpParser\Parser(new PhpParser\Lexer);
		$this->printer = new Printer;
	}

	/**
	 * @param string $code
	 * @return NULL|string
	 */
	public function format($code)
	{
		$code = preg_replace('~else\s+if~i', 'elseif', $code); // TODO remove this ugly hack

		try
		{
			$tokens = $this->parser->parse($code);
			$pretty = $this->printer->prettyPrintFile($tokens) . "\n";
			// remove indentation on empty newlines
			$pretty = preg_replace('~^[ \t]$~m', '', $pretty);
			return $pretty;
		}
		catch (PhpParser\Error $e)
		{
			echo 'Parse Error: ', $e->getMessage();
		}
		return NULL;
	}

}
