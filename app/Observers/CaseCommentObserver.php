<?php

namespace App\Observers;

use App\Enums\UserRole;
use App\Models\CaseComment;
use App\Models\User;
use App\Notifications\CaseConversationNotification;

class CaseCommentObserver
{
    public function created(CaseComment $comment): void
    {
        $comment->loadMissing(['case.assignedAuditor', 'user']);
        $case = $comment->case;
        $author = $comment->user;

        if (! $case || ! $author) {
            return;
        }

        $notification = new CaseConversationNotification(
            $case,
            "Nueva conversación · {$case->case_number}",
            "{$author->name} agregó un comentario o pregunta en el caso {$case->case_number}.",
        );

        if ($author->canManageAllCases()) {
            if ($case->assignedAuditor && $case->assignedAuditor->id !== $author->id) {
                $case->assignedAuditor->notify($notification);
            }

            return;
        }

        User::query()
            ->where('is_active', true)
            ->whereIn('role', [UserRole::Administrator, UserRole::Leader])
            ->whereKeyNot($author->id)
            ->each(fn (User $user) => $user->notify($notification));
    }

    public function updated(CaseComment $comment): void
    {
        if (! $comment->wasChanged('is_resolved') || ! $comment->is_resolved) {
            return;
        }

        $comment->loadMissing(['case', 'user', 'resolvedBy']);

        if (! $comment->case || ! $comment->user || $comment->user_id === $comment->resolved_by) {
            return;
        }

        $resolver = $comment->resolvedBy?->name ?? 'El equipo';

        $comment->user->notify(new CaseConversationNotification(
            $comment->case,
            "Conversación resuelta · {$comment->case->case_number}",
            "{$resolver} marcó como resuelta una conversación del caso {$comment->case->case_number}.",
        ));
    }
}
