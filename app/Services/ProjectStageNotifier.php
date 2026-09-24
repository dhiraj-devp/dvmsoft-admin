<?php

namespace App\Services;

use App\Automations\AutomationEngine;
use App\Automations\ClientPortalNotifier;
use App\Automations\StaffNotifier;
use App\Models\ClientUser;
use App\Models\ProjectStage;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class ProjectStageNotifier
{
    public function __construct(
        protected AutomationEngine $engine,
        protected StaffNotifier $staff,
        protected ClientPortalNotifier $portal,
    ) {}

    public function staff(
        ProjectStage $stage,
        string $event,
        string $title,
        string $message,
        Model|string|null $subject = null,
        ?string $occurrence = null,
        ?User $except = null,
    ): int {
        if (! $this->engine->isEnabled('projects.stage_activity')) {
            return 0;
        }

        $stage->loadMissing(['project.manager', 'owner', 'members']);

        $users = $this->staffRecipients($stage);
        if ($except) {
            $users = $users->reject(fn (User $user) => $user->id === $except->id);
        }

        return $this->staff->send(
            'projects.stage_activity',
            $users,
            $title,
            $message,
            $stage->project ? url(route('projects.show', ['project' => $stage->project, 'tab' => 'delivery'], false)) : null,
            ['stage_id' => $stage->id, 'event' => $event],
            $subject ?? $stage,
            $occurrence ?? $event.'-'.$stage->id,
        );
    }

    public function portal(
        ProjectStage $stage,
        string $event,
        string $title,
        string $message,
        Model|string|null $subject = null,
        ?string $occurrence = null,
        ?ClientUser $except = null,
    ): int {
        $stage->loadMissing('project.client');

        return $this->portal->notify(
            $stage->project?->client,
            'portal.activity',
            $event,
            $title,
            $message,
            $stage->project ? route('client.projects.show', $stage->project) : null,
            ['stage_id' => $stage->id],
            $subject ?? $stage,
            $occurrence ?? $event.'-'.$stage->id,
            $except,
        );
    }

    /**
     * @return Collection<int, User>
     */
    protected function staffRecipients(ProjectStage $stage): Collection
    {
        $users = collect([$stage->owner, $stage->project?->manager])
            ->merge($stage->members)
            ->filter()
            ->unique(fn (User $user) => $user->id)
            ->values();

        if ($users->isEmpty()) {
            return $this->staff->withPermission('projects.stages.view');
        }

        return $users;
    }
}
