<?php
declare(strict_types=1);

namespace Nexus\Database;

use Nexus\Core\Container;
use InvalidArgumentException;

/**
 * Mass-Assignment Safe Active Entity Model
 */
abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $guarded = ['id'];
    protected array $casts = [];
    protected array $hidden = [];
    protected bool $timestamps = true;
    protected bool $softDeletes = false;

    protected array $attributes = [];
    protected array $original = [];
    protected bool $exists = false;

    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }

    public static function query(): QueryBuilder
    {
        $instance = new static();
        /** @var Database $db */
        $db = Container::getInstance()->make(Database::class);
        $qb = new QueryBuilder($db, $instance->table);

        if ($instance->softDeletes) {
            $qb->whereNull('deleted_at');
        }

        return $qb;
    }

    public static function find(mixed $id): ?static
    {
        $instance = new static();
        $row = static::query()->where($instance->primaryKey, '=', $id)->first();
        if ($row === null) {
            return null;
        }

        return static::newFromBuilder($row);
    }

    public static function newFromBuilder(array $attributes): static
    {
        $instance = new static();
        $instance->setRawAttributes($attributes, true);
        return $instance;
    }

    public function fill(array $attributes): static
    {
        foreach ($attributes as $key => $value) {
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            }
        }
        return $this;
    }

    public function isFillable(string $key): bool
    {
        if (in_array($key, $this->fillable, true)) {
            return true;
        }
        if (empty($this->fillable) && !in_array($key, $this->guarded, true)) {
            return true;
        }
        return false;
    }

    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    public function getAttribute(string $key): mixed
    {
        $value = $this->attributes[$key] ?? null;
        if ($value === null) {
            return null;
        }

        $castType = $this->casts[$key] ?? null;
        return match ($castType) {
            'int', 'integer' => (int)$value,
            'float', 'real' => (float)$value,
            'bool', 'boolean' => (bool)$value,
            'json', 'array' => is_string($value) ? json_decode($value, true) : $value,
            default => $value,
        };
    }

    public function __get(string $key): mixed
    {
        return $this->getAttribute($key);
    }

    public function __set(string $key, mixed $value): void
    {
        if ($this->isFillable($key)) {
            $this->setAttribute($key, $value);
        }
    }

    public function setRawAttributes(array $attributes, bool $sync = false): void
    {
        $this->attributes = $attributes;
        if ($sync) {
            $this->original = $attributes;
            $this->exists = true;
        }
    }

    public function save(): bool
    {
        /** @var Database $db */
        $db = Container::getInstance()->make(Database::class);

        if ($this->timestamps) {
            $now = date('Y-m-d H:i:s');
            $this->attributes['updated_at'] = $now;
            if (!$this->exists && !isset($this->attributes['created_at'])) {
                $this->attributes['created_at'] = $now;
            }
        }

        $serialized = $this->getAttributesForSave();

        if ($this->exists) {
            $id = $this->original[$this->primaryKey] ?? $this->attributes[$this->primaryKey];
            $qb = new QueryBuilder($db, $this->table);
            $qb->where($this->primaryKey, '=', $id)->update($serialized);
        } else {
            $qb = new QueryBuilder($db, $this->table);
            $id = $qb->insert($serialized);
            if ($id !== '0') {
                $this->attributes[$this->primaryKey] = $id;
            }
            $this->exists = true;
        }

        $this->original = $this->attributes;
        return true;
    }

    public function delete(): bool
    {
        if (!$this->exists) {
            return false;
        }

        /** @var Database $db */
        $db = Container::getInstance()->make(Database::class);
        $id = $this->original[$this->primaryKey] ?? $this->attributes[$this->primaryKey];
        $qb = new QueryBuilder($db, $this->table);

        if ($this->softDeletes) {
            $qb->where($this->primaryKey, '=', $id)->update(['deleted_at' => date('Y-m-d H:i:s')]);
        } else {
            $qb->where($this->primaryKey, '=', $id)->delete();
        }

        $this->exists = false;
        return true;
    }

    /**
     * GDPR Anonymization Hook
     */
    public function anonymize(array $anonymizeMap): bool
    {
        foreach ($anonymizeMap as $field => $placeholder) {
            if (array_key_exists($field, $this->attributes)) {
                $this->setAttribute($field, $placeholder);
            }
        }
        return $this->save();
    }

    private function getAttributesForSave(): array
    {
        $saveData = [];
        foreach ($this->attributes as $key => $val) {
            $castType = $this->casts[$key] ?? null;
            if (in_array($castType, ['json', 'array'], true) && is_array($val)) {
                $saveData[$key] = json_encode($val, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            } else {
                $saveData[$key] = $val;
            }
        }
        return $saveData;
    }

    public function toArray(): array
    {
        $array = [];
        foreach ($this->attributes as $key => $val) {
            if (in_array($key, $this->hidden, true)) {
                continue;
            }
            $array[$key] = $this->getAttribute($key);
        }
        return $array;
    }
}

