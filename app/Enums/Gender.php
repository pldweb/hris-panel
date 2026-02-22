<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'Laki-laki';
    case FEMALE = 'Perempuan';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Laki-laki',
            self::FEMALE => 'Perempuan',
        };
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label(),
        ];
    }
}
