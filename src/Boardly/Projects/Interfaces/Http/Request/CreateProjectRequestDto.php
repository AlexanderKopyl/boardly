<?php

declare(strict_types=1);

namespace App\Boardly\Projects\Interfaces\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateProjectRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,

        #[Assert\Length(max: 64)]
        #[Assert\Regex(pattern: '/\A[a-z][a-z0-9_-]{0,63}\z/')]
        public ?string $iconKey = null,
    ) {
    }
}
