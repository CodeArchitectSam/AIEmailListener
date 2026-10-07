<?php

namespace App\AI;

final readonly class Location
{
    /**
     * @param  array{building?: mixed, floor?: mixed, room?: mixed}|null  $data
     */
    public static function fromArray(?array $data): self
    {
        return new self(
            building: self::cleanString($data['building'] ?? null),
            floor: self::cleanString($data['floor'] ?? null),
            room: self::cleanString($data['room'] ?? null),
        );
    }

    private static function cleanString(mixed $value): ?string
    {
        if (! is_string($value) && ! is_numeric($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    public function __construct(
        public readonly ?string $building,
        public readonly ?string $floor,
        public readonly ?string $room,
    ) {
    }

    /**
     * @return array{building: string|null, floor: string|null, room: string|null}
     */
    public function toArray(): array
    {
        return [
            'building' => $this->building,
            'floor' => $this->floor,
            'room' => $this->room,
        ];
    }

    /**
     * @return array{building: string|null, floor: string|null, room: string|null}
     */
    public function normalized(): array
    {
        return [
            'building' => self::normalize($this->building),
            'floor' => self::normalize($this->floor),
            'room' => self::normalize($this->room),
        ];
    }

    public function equals(Location $other): bool
    {
        return $this->normalized() === $other->normalized();
    }

    public function isEmpty(): bool
    {
        return $this->building === null && $this->floor === null && $this->room === null;
    }

    private static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($value));

        return $normalized === '' ? null : $normalized;
    }
}