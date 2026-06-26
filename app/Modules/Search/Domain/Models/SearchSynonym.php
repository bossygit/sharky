<?php

declare(strict_types=1);

namespace App\Modules\Search\Domain\Models;

use Illuminate\Database\Eloquent\Model;

class SearchSynonym extends Model
{
    protected $fillable = ['term', 'synonyms'];

    protected function casts(): array
    {
        return ['synonyms' => 'array'];
    }
}
