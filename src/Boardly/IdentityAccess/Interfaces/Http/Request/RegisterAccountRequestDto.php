<?php

declare(strict_types=1);

namespace App\Boardly\IdentityAccess\Interfaces\Http\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class RegisterAccountRequestDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8, max: 4096)]
        public string $plainPassword,

        #[Assert\NotBlank]
        #[Assert\Length(max: 100)]
        public string $name,
    ) {
    }
}
