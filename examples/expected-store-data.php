<?php

namespace App\Data;

use Illuminate\Foundation\Http\FormRequest;

final readonly class StoreCustomerData
{
    /**
     * @param array<string, mixed> $attributes
     */
    private function __construct(
        private array $attributes,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(array_intersect_key($data, array_flip([
            'company_id',
            'name',
            'email',
            'credit_limit',
            'is_active',
        ])));
    }

    public static function fromRequest(FormRequest $request): self
    {
        return self::fromArray($request->validated());
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->attributes;
    }
}
