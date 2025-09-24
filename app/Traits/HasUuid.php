<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Ramsey\Uuid\Uuid;

trait HasUuid
{
    /**
     * Boot trait ini.
     */
    protected static function bootHasUuid(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = Uuid::uuid4()->toString();
            }
        });
    }

    /**
     * Dapatkan nilai yang menunjukkan apakah ID auto-increment.
     */
    public function getIncrementing(): bool
    {
        return false;
    }

    /**
     * Dapatkan tipe key auto-increment.
     */
    public function getKeyType(): string
    {
        return 'string';
    }
}