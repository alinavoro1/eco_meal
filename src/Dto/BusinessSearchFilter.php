<?php

namespace App\Dto;

use App\Entity\BusinessType;

class BusinessSearchFilter
{
    public ?string $name = null;
    public ?string $city = null;
    public ?BusinessType $businessType = null;
}
