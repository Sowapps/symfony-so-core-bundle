<?php
/**
 * @author Florent HAZARD <f.hazard@sowapps.com>
 */

namespace App\Doctrine;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Exception;

/**
 * https://github.com/creof/doctrine2-spatial/blob/4e3d8154a23cbb7216f32b38244dce615db6c28b/lib/CrEOF/Spatial/ORM/Query/AST/Functions/AbstractSpatialDQLFunction.php
 */
class AbstractSpatialDQLFunction extends FunctionNode {
	
	/**
	 * @var string
	 */
	protected string $functionName;
	
	/**
	 * @var array
	 */
	protected array $platforms = [];
	
	/**
	 * @var Node[]
	 */
	protected array $argExpressions = [];
	
	/**
	 * @var int
	 */
	protected int $minArgs = 0;
	
	/**
	 * @var int|null
	 */
	protected ?int $maxArgs = null;
	
	/**
	 * @param Parser $parser
	 */
	public function parse(Parser $parser) {
		$lexer = $parser->getLexer();
		
		$parser->match(Lexer::T_IDENTIFIER);
		$parser->match(Lexer::T_OPEN_PARENTHESIS);
		
		$this->argExpressions[] = $parser->ArithmeticExpression();
		
		while( count($this->argExpressions) < $this->minArgs || (($this->maxArgs === null || count($this->argExpressions) < $this->maxArgs) && $lexer->lookahead['type'] != Lexer::T_CLOSE_PARENTHESIS) ) {
			$parser->match(Lexer::T_COMMA);
			
			$this->argExpressions[] = $parser->ArithmeticExpression();
		}
		
		$parser->match(Lexer::T_CLOSE_PARENTHESIS);
	}
	
	/**
	 * @param SqlWalker $sqlWalker
	 * @return string
	 */
	public function getSql(SqlWalker $sqlWalker): string {
		//		$this->validatePlatform($sqlWalker->getConnection()->getDatabasePlatform());
		
		$arguments = [];
		foreach( $this->argExpressions as $expression ) {
			$arguments[] = $expression->dispatch($sqlWalker);
		}
		
		return sprintf('%s(%s)', $this->functionName, implode(', ', $arguments));
	}
	
	/**
	 * @param AbstractPlatform $platform
	 *
	 * @throws Exception
	 */
	//	protected function validatePlatform(AbstractPlatform $platform)
	//	{
	//		$platformName = $platform->getName();
	//
	//		if (!empty($this->platforms) && !in_array($platformName, $this->platforms)) {
	//			throw new Exception(sprintf('DBAL platform "%s" is not currently supported.', $platformName));
	//		}
	//	}
}
