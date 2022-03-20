<?php

namespace App\Theme;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\SyntaxError;

class ContentEvaluator
{
    private ExpressionLanguage $expressionLanguage;

    public function __construct()
    {
        $this->expressionLanguage = new ExpressionLanguage();
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp("__"));
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp("url"));
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp("versioned"));
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp("e"));
        $this->expressionLanguage->addFunction(ExpressionFunction::fromPhp("addSlashes"));
    }

    public function evaluate(string $text, array $data = []): string
    {
        return preg_replace_callback(
            "/({{(?P<safe>.+?)}}|{!!(?P<simple>.+?)!!})/s",
            function (array $matches) use ($data) {
                if (array_get($matches, "simple")) {
                    return $this->evalMatch($matches["simple"], $data);
                }

                if (array_get($matches, "safe")) {
                    return $this->evalMatchSafely($matches["safe"], $data);
                }

                return "[#ERROR_MATCH]";
            },
            $text
        );
    }

    private function evalMatchSafely(string $match, array $data): string
    {
        return htmlspecialchars($this->evalMatch($match, $data));
    }

    private function evalMatch(string $match, array $data): string
    {
        $result = trim($match);
        $result = ltrim($result, "$");
        $result = str_replace(["->"], ["."], $result);

        try {
            return $this->expressionLanguage->evaluate($result, $data) ?? "";
        } catch (SyntaxError $e) {
            return "[#ERROR_SYNTAX]";
        }
    }
}
