<?php

namespace App\DTO;

use App\Enum\BookCondition;
use Symfony\Component\Validator\Constraints as Assert;

class ListingFormEdit
{
    #[Assert\NotBlank()]
    public ?string $title = null;

    #[Assert\NotBlank]
    public ?string $isbn = null;

    #[Assert\NotBlank]
    #[Assert\Positive]
    public ?float $price = null;

    #[Assert\NotBlank]
    public ?string $language = null;

    public ?string $book_condition = null;
    
    public ?int $category = null;
}
