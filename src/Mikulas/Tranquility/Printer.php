<?php

namespace Mikulas\Tranquility;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Stmt;
use PhpParser\PrettyPrinter\Standard;


class Printer extends Standard
{

	/**
	 * Pretty prints an array of nodes (statements) and indents them optionally.
	 *
	 * @param Node[] $nodes  Array of nodes
	 * @param bool   $indent Whether to indent the printed nodes
	 *
	 * @return string Pretty printed statements
	 */
	protected function pStmts(array $nodes, $indent = TRUE)
	{
		$result = '';
		foreach ($nodes as $node) {
			$result .= "\n"
				. $this->pComments($node->getAttribute('comments', array()))
				. $this->p($node)
				. ($node instanceof Expr ? ';' : '');
		}

		if ($indent) {
			return preg_replace('~\n(?!$|' . $this->noIndentToken . ')~', "\n\t", $result); // TODO replace \t with setter
		} else {
			return $result;
		}
	}

	public function pStmt_If(Stmt\If_ $node) {
		return 'if (' . $this->p($node->cond) . ")\n{"
			. $this->pStmts($node->stmts) . "\n" . '}'
			. $this->pImplode($node->elseifs)
			. (null !== $node->else ? $this->p($node->else) : '');
	}

	public function pStmt_ElseIf(Stmt\ElseIf_ $node) {
		return "\nelseif (" . $this->p($node->cond) . ")\n{"
			. $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_Else(Stmt\Else_ $node) {
		return "\nelse\n{" . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_For(Stmt\For_ $node) {
		return 'for ('
			. $this->pCommaSeparated($node->init) . ';' . (!empty($node->cond) ? ' ' : '')
			. $this->pCommaSeparated($node->cond) . ';' . (!empty($node->loop) ? ' ' : '')
			. $this->pCommaSeparated($node->loop)
			. ') {' . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_Foreach(Stmt\Foreach_ $node) {
		return 'foreach (' . $this->p($node->expr) . ' as '
			. (null !== $node->keyVar ? $this->p($node->keyVar) . ' => ' : '')
			. ($node->byRef ? '&' : '') . $this->p($node->valueVar) . ")\n{"
			. $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_While(Stmt\While_ $node) {
		return 'while (' . $this->p($node->cond) . ")\n{"
			. $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_Do(Stmt\Do_ $node) {
		return "do\n{" . $this->pStmts($node->stmts) . "\n"
			. "}\nwhile (" . $this->p($node->cond) . ');';
	}

	public function pStmt_Switch(Stmt\Switch_ $node) {
		return 'switch (' . $this->p($node->cond) . ")\n{"
			. $this->pStmts($node->cases) . "\n" . '}';
	}

	public function pStmt_TryCatch(Stmt\TryCatch $node) {
		return "try\n{" . $this->pStmts($node->stmts) . "\n" . '}'
			. $this->pImplode($node->catches)
			. ($node->finallyStmts !== null
				? " finally\n{" . $this->pStmts($node->finallyStmts) . "\n" . '}'
				: '');
	}

	public function pStmt_Catch(Stmt\Catch_ $node) {
		return ' catch (' . $this->p($node->type) . ' $' . $node->var . ")\n{"
			. $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pExpr_Array(Expr\Array_ $node) {
		return '[' . $this->pCommaSeparated($node->items) . ']';
	}

	public function pStmt_Class(Stmt\Class_ $node) {
		return "\n\n" . $this->pModifiers($node->type)
			. 'class ' . $node->name
			. (null !== $node->extends ? ' extends ' . $this->p($node->extends) : '')
			. (!empty($node->implements) ? ' implements ' . $this->pCommaSeparated($node->implements) : '')
			. "\n" . '{' . "\n" . $this->pStmts($node->stmts) . "\n" . '}';
	}

	public function pStmt_ClassMethod(Stmt\ClassMethod $node) {
		return parent::pStmt_ClassMethod($node) . "\n";
	}

}
