<?php

namespace App\Http\Resources;

use App\Models\Challenge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The reference solution stays on the server. SQL answers are checked by
 * ChallengeCheckController; PHP answers run in the browser against `checks`.
 *
 * @mixin Challenge
 */
class ChallengeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'prompt' => $this->prompt,
            'starter_code' => $this->starter_code,
            'hint' => $this->hint,
            'order_matters' => $this->order_matters,
            'checks' => $this->checks,
        ];
    }
}
