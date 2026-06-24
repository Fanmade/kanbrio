<?php

namespace App\Http\Resources;

use App\Models\TaskType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin TaskType
 */
class TaskTypeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'color' => $this->color,
            'icon' => $this->icon,
            'branch_prefix' => $this->branch_prefix,
            'position' => $this->position,
        ];
    }
}
