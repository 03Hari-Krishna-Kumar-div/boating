<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'server_time' => now()->format('Y-m-d\TH:i:s.u\Z'),
            'boats' => BoatResource::collection($this['boats'] ?? []),
            'stats' => $this['stats'] ?? [],
            'notifications' => $this['notifications'] ?? [],
        ];
    }
}
