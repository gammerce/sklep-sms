<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    public function __construct(array $data)
    {
        $output = collect($data)
            ->map(function ($value, $key) {
                return "<$key>$value</$key>";
            })
            ->join();

        parent::__construct($output, 200, [
            "Content-type" => 'text/plain; charset="UTF-8"',
        ]);
    }
}
