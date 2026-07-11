<?php

namespace App\Dto;

use App\Entity\Business;
use App\Entity\Consumer;

class OrderSearchFilter
{
    public ?string $packageName = null;
    public ?Business $business = null;
    public ?Consumer $consumer = null;
}
