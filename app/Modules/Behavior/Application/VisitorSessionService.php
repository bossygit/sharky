<?php

declare(strict_types=1);

namespace App\Modules\Behavior\Application;

use App\Modules\Behavior\Domain\Models\BehaviorEvent;
use App\Modules\Behavior\Domain\Models\VisitorSession;
use App\Modules\Observability\Application\DomainEventRecorder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class VisitorSessionService
{
    private const COOKIE = 'nasande_sid';

    public function __construct(private Request $request) {}

    public function resolve(): VisitorSession
    {
        $id = $this->request->cookie(self::COOKIE);
        if ($id && Str::isUuid($id)) {
            $session = VisitorSession::find($id);
            if ($session) {
                $session->last_seen_at = now();
                if ($userId = Auth::id()) {
                    $session->user_id = $userId;
                }
                $session->save();

                return $session;
            }
        }

        $session = VisitorSession::create([
            'id' => (string) Str::uuid(),
            'user_id' => Auth::id(),
            'ip_hash' => $this->hashIp($this->request->ip()),
            'user_agent_hash' => hash('sha256', (string) $this->request->userAgent()),
            'locale' => app()->getLocale(),
            'country' => 'CG',
            'device' => $this->detectDevice(),
            'flags' => ['bot' => $this->isBot()],
            'started_at' => now(),
            'last_seen_at' => now(),
        ]);

        cookie()->queue(cookie(self::COOKIE, $session->id, 60 * 24 * 30, '/', null, false, true, false, 'Lax'));

        return $session;
    }

    public function currentId(): ?string
    {
        $id = $this->request->cookie(self::COOKIE);

        return ($id && Str::isUuid($id)) ? $id : null;
    }

    private function hashIp(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }
        $salt = config('app.key');

        return hash('sha256', $ip.'|'.now()->toDateString().'|'.$salt);
    }

    private function detectDevice(): string
    {
        $ua = strtolower((string) $this->request->userAgent());
        if (str_contains($ua, 'mobile')) {
            return 'mobile';
        }
        if (str_contains($ua, 'tablet')) {
            return 'tablet';
        }

        return 'desktop';
    }

    public function isBot(): bool
    {
        $ua = strtolower((string) $this->request->userAgent());

        return (bool) preg_match('/bot|crawl|spider|slurp|facebookexternalhit|preview/i', $ua);
    }
}
