<?php

declare(strict_types=1);

namespace App\Modules\Behavior\Domain\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisitorSession extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $table = 'visitor_sessions';

    protected $fillable = [
        'id',
        'user_id',
        'ip_hash',
        'user_agent_hash',
        'locale',
        'country',
        'device',
        'flags',
        'intent_score',
        'started_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'flags' => 'array',
            'intent_score' => 'decimal:3',
            'started_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function behaviorEvents(): HasMany
    {
        return $this->hasMany(BehaviorEvent::class, 'session_id');
    }
}
