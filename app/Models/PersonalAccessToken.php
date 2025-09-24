<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;
use App\Traits\HasUuid;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasUuid;
}