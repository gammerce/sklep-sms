<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class ServerResponse extends Response
{
    public function __construct(array $data)
    {
        $items = $this->formatArray("", $data);
        $content = implode("\n", $items);

        parent::__construct($content, 200, [
            "Content-type" => 'text/plain; charset="UTF-8"',
        ]);
    }

    private function formatArray($prefix, array $data)
    {
        $output = [];

        foreach ($data as $key => $value) {
            $valueKey = "$prefix.$key";

            if (is_array($value)) {
                foreach ($this->formatArray($valueKey, $value) as $item) {
                    $output[] = $item;
                }
            } else {
                $trimmedValueKey = ltrim($valueKey, ".");
                $escapedValueKey = str_replace("\n", " ", $trimmedValueKey);
                $escapedValue = str_replace("\n", " ", $value);
                $output[] = "$escapedValueKey:$escapedValue";
            }
        }

        return $output;
    }
}
