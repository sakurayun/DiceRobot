<?php

declare(strict_types=1);

namespace DiceRobot\Data;

use DiceRobot\Exception\DiceException\{DiceNumberOverstepException, ExpressionInvalidException,
    SurfaceNumberOverstepException};
use DiceRobot\Util\Random;
use Selective\Config\Configuration;

/**
 * Class Subexpression
 *
 * The minimum subexpression of a dicing expression.
 *
 * @package DiceRobot\Data
 */
class Subexpression
{
    /** @var int Max dice number */
    protected static int $maxDiceNumber;

    /** @var int Max dice surface number */
    protected static int $maxSurfaceNumber;

    /** @var string Subexpression */
    public string $expression;

    /** @var int Dice number */
    public int $diceNumber;

    /** @var int Dice surface number */
    public int $surfaceNumber;

    /** @var int|null K number */
    public ?int $kNumber = NULL;

    /** @var int[] Dicing results */
    public array $results;

    /** @var int Dicing result */
    public int $result;

    /**
     * Set maxDiceNumber and maxSurfaceNumber.
     *
     * @GlobalInitialize
     *
     * @param Configuration $config
     */
    public static function globalInitialize(Configuration $config): void
    {
        static::$maxDiceNumber = $config->getInt("order.maxDiceNumber");
        static::$maxSurfaceNumber = $config->getInt("order.maxSurfaceNumber");
    }

    /**
     * The constructor.
     *
     * @param string $expression Dicing subexpression
     *
     * @throws DiceNumberOverstepException|ExpressionInvalidException|SurfaceNumberOverstepException
     */
    public function __construct(string $expression)
    {
        $this->expression = $expression;

        $this->parseExpression();
        $this->checkRange();
        $this->roll();
    }

    /**
     * Regenerate result.
     */
    public function __clone()
    {
        $this->roll();
    }

    /**
     * Parse out dice type, dice number, dice surface number, K number of the subexpression.
     *
     * The subexpression must be a full subexpression (xDy or xDyKz) or fulfilled before. Anything like D, xD, Dy, DK,
     * xDK, DyK, DKz, xDyK, xDKz or DyKz is invalid.
     *
     * @throws ExpressionInvalidException
     */
    private function parseExpression(): void
    {
        if (preg_match("/^([1-9][0-9]*)D([1-9][0-9]*)(?:K([1-9][0-9]*))?$/", $this->expression, $matches))
        {
            $this->diceNumber = (int) $matches[1];
            $this->surfaceNumber = (int) $matches[2];

            if (!empty($matches[3]))
                $this->kNumber = (int) $matches[3];
        }
        else
            throw new ExpressionInvalidException();
    }

    /**
     * Check the range of dice number and dice surface number.
     *
     * @throws DiceNumberOverstepException|ExpressionInvalidException|SurfaceNumberOverstepException
     */
    private function checkRange(): void
    {
        if ($this->diceNumber < 1 || $this->diceNumber > static::$maxDiceNumber)
            throw new DiceNumberOverstepException();

        if ($this->surfaceNumber < 1 || $this->surfaceNumber > static::$maxSurfaceNumber)
            throw new SurfaceNumberOverstepException();

        if ($this->kNumber > $this->diceNumber)
            throw new ExpressionInvalidException();
    }

    /**
     * Roll a dice determined by this subexpression and calculate summary.
     */
    private function roll(): void
    {
        // xDy
        if (is_null($this->kNumber))
        {
            $this->results = Random::generate($this->diceNumber, $this->surfaceNumber);
            $this->result = array_sum($this->results);
        }
        // xDyKz
        else
        {
            $this->results = Random::generate($this->diceNumber, $this->surfaceNumber);

            for ($i = count($this->results); $i > $this->kNumber; $i--)
                array_splice( $this->results, array_search(min($this->results), $this->results), 1);

            $this->result = array_sum($this->results);
        }
    }

    /**
     * Generate result string.
     *
     * @param string $glue Bound symbol
     *
     * @return string Result string
     */
    public function getResultString(string $glue = "+"): string
    {
        if (count($this->results) == 1)
            return (string) $this->results[0];

        return "(" . join($glue, $this->results) . ")";
    }
}