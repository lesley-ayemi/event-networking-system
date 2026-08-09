<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'starts_at' => $this->starts_at,
            'ends_at' => $this->ends_at,
            'location' => $this->location,
            'is_virtual' => $this->is_virtual,
            'industry' => $this->industry,
            'one_to_one_available' => $this->one_to_one_available,
            'small_group_available' => $this->small_group_available,
            'is_free' => $this->is_free,
            'price' => $this->price,
            'accessibility_options' => $this->accessibility_options ?? [],
            'capacity' => $this->capacity,
            'cover_image' => $this->cover_image,
            'created_by' => $this->created_by,
            'attendees_count' => $this->registrations_count ?? 0,
            'is_registered' => (bool) ($this->is_registered ?? false),
            'my_registration' => $this->when($this->my_registration, fn () => [
                'interaction_mode' => $this->my_registration->interaction_mode,
                'open_to_matching' => $this->my_registration->open_to_matching,
                'message_before_event' => $this->my_registration->message_before_event,
                'preferred_group_size' => $this->my_registration->preferred_group_size,
                'attendance_format' => $this->my_registration->attendance_format,
            ], null),
            'is_bookmarked' => (bool) ($this->is_bookmarked ?? false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
