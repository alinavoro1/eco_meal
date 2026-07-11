<?php

namespace App\Dto;

use App\Entity\Category;
use App\Entity\Business;

class PackageSearchFilter
{
    public ?string $name = null;
    public ?float $minPrice = null;
    public ?float $maxPrice = null;
    public ?Category $category = null;
    public ?string $city = null;
    public ?Business $business = null;
}
