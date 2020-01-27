<?php
namespace App\Http\Responses;

use Symfony\Component\HttpFoundation\Response;

class XmlResponse extends Response
{
    public function __construct(array $data, $status = 200)
    {
        $output = collect($data)
            ->map(function ($value, $key) {
                return "<$key>$value</$key>";
            })
            ->join();

        parent::__construct($output, $status, [
            "Content-type" => 'application/xml',
        ]);
    }
}
