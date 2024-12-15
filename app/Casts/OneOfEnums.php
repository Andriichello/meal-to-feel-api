<?php

namespace App\Casts;

use BackedEnum;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Class OneOfEnums.
 */
class OneOfEnums implements CastsAttributes
{
    /**
     * Enums that it uses.
     *
     * @var array<class-string<BackedEnum>>
     */
    protected array $enums;

    /**
     * OneOfEnums' constructor.
     *
     * @param array|string $enums
     */
    public function __construct(string ...$enums)
    {
        $this->enums = $enums;
    }

    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        foreach ($this->enums as $enum) {
            if (is_subclass_of($enum, BackedEnum::class)) {
                $case = $enum::tryFrom($value);

                if ($case) {
                    return $case;
                }
            }
        }

        return null;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value;
    }
}
