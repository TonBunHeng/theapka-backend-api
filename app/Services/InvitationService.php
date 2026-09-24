<?php

namespace App\Services;

use App\Models\Guest;
use App\Models\Invitation;
use App\Models\InvitationSend;
use App\Models\User;
use App\Models\Wedding;

class InvitationService
{
    /**
     * Publish an invitation.
     */
    public function publish(Wedding $wedding): Invitation
    {
        $invitation = Invitation::firstOrCreate(
            ['wedding_id' => $wedding->id],
            [
                'slug' => $wedding->slug,
                'title' => $wedding->title,
                'status' => 'draft',
            ]
        );

        $invitation->update([
            'status' => 'published',
            'published_at' => now(),
        ]);

        return $invitation;
    }

    /**
     * Unpublish an invitation.
     */
    public function unpublish(Wedding $wedding): ?Invitation
    {
        $invitation = $wedding->invitation;
        if ($invitation instanceof Invitation) {
            $invitation->update([
                'status' => 'draft',
            ]);
            return $invitation;
        }

        return null;
    }

    /**
     * Track view count and mark opened_at on personalized invitation.
     */
    public function recordView(Invitation $invitation, ?Guest $guest = null): void
    {
        $invitation->increment('view_count');

        if ($guest) {
            // Mark opened_at on invitation send if not opened yet
            InvitationSend::where('invitation_id', $invitation->id)
                ->where('guest_id', $guest->id)
                ->whereNull('opened_at')
                ->update(['opened_at' => now()]);
        }
    }

    /**
     * Record an invitation send event.
     */
    public function recordSend(Invitation $invitation, Guest $guest, string $channel = 'manual', ?User $sender = null): InvitationSend
    {
        return InvitationSend::updateOrCreate(
            [
                'invitation_id' => $invitation->id,
                'guest_id' => $guest->id,
                'channel' => $channel,
            ],
            [
                'sent_by' => $sender?->id,
                'sent_at' => now(),
            ]
        );
    }
}
