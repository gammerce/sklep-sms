<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class AssocResponse extends Response
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

        if (is_list($data)) {
            $output[] = $this->createEntry("$prefix.c", count($data));
        }

        foreach ($data as $key => $value) {
            $valueKey = "$prefix.$key";

            if (is_array($value)) {
                foreach ($this->formatArray($valueKey, $value) as $item) {
                    $output[] = $item;
                }
            } else {
                $output[] = $this->createEntry($valueKey, $value);
            }
        }

        return $output;
    }

    private function createEntry($key, $value)
    {
        $trimmedKey = ltrim($key, ".");
        $escapedKey = str_replace("\n", " ", $trimmedKey);
        $escapedValue = str_replace("\n", " ", $value);
        return "$escapedKey:$escapedValue";
    }
}
