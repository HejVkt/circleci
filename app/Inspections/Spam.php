<?php

namespace App\Inspections;

use App\Inspections\InvalidKeywords;
use Mockery\Exception;

class Spam
{

    protected $inspections = [
        InvalidKeywords::class,
        KeyHoldDown::class,
    ];

    public function detect($body)
    {
        foreach ( $this->inspections as $inspection) {
            app($inspection)->detect($body);
        }

        return false;
    }

}