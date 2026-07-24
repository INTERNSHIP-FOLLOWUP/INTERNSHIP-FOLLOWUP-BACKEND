<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request): array
    {
        $sender = $this->sender;
        $senderData = null;

        if ($sender) {
            $roleName = $sender->role?->name ?? 'User';

            // If sender is a company, return company info
            if ($roleName === 'Company' && $sender->company) {
                $senderData = [
                    'id' => $sender->id,
                    'name' => $sender->company->company_name,
                    'role' => 'Company',
                    'avatar' => $sender->company->company_profile_image_url ?? $sender->company->company_image_url,
                ];
            } else {
                $senderData = [
                    'id' => $sender->id,
                    'name' => $sender->name,
                    'role' => $roleName,
                    'avatar' => $sender->avatar_url,
                ];
            }
        }

        $receiver = $this->receiver;
        $receiverData = null;

        if ($receiver) {
            $receiverRoleName = $receiver->role?->name ?? 'User';

            if ($receiverRoleName === 'Company' && $receiver->company) {
                $receiverData = [
                    'id' => $receiver->id,
                    'name' => $receiver->company->company_name,
                    'role' => 'Company',
                    'avatar' => $receiver->company->company_profile_image_url ?? $receiver->company->company_image_url,
                ];
            } else {
                $receiverData = [
                    'id' => $receiver->id,
                    'name' => $receiver->name,
                    'role' => $receiverRoleName,
                    'avatar' => $receiver->avatar_url,
                ];
            }
        }

        return [
            'id' => $this->id,
            'title' => $this->title,
            'message' => $this->message,
            'type' => $this->type,
            'priority' => $this->priority,
            'is_read' => $this->is_read,
            'action_url' => $this->action_url,
            'entity_type' => $this->entity_type,
            'entity_id' => $this->entity_id,
            'sender' => $senderData,
            'receiver' => $receiverData,
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
        ];
    }
}