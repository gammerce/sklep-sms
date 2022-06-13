<?php

namespace App\Support;

use PDO;
use PDOStatement as BasePDOStatement;

class PDOStatement extends BasePDOStatement
{
    public function bindAndExecute(array $params): bool
    {
        $i = 1;
        foreach ($params as $param) {
            $parameterType = $this->getParamType($param);
            $this->bindValue($i++, $param, $parameterType);
        }

        return parent::execute();
    }

    private function getParamType($value): int
    {
        if (is_bool($value)) {
            return PDO::PARAM_BOOL;
        }

        if (is_null($value)) {
            return PDO::PARAM_NULL;
        }

        if (is_int($value)) {
            return PDO::PARAM_INT;
        }

        return PDO::PARAM_STR;
    }
}
