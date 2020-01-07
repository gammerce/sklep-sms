<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class ServerResponse extends Response
{
    public function __construct(array $data)
    {
        $items = [];
        foreach ($data as $key => $value) {
            $escapedValue = str_replace("\n", " ", $value);
            $items[] = "$key:$escapedValue";
        }

        $content = implode("\n", $items);

        parent::__construct($content, 200, [
            "Content-type" => 'text/plain; charset="UTF-8"',
        ]);
    }
}
