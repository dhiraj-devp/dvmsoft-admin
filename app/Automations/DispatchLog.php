<?php

namespace App\Automations;

use App\Models\AutomationDispatch;
use Illuminate\Database\Eloquent\Model;

class DispatchLog
{
    public function claimed(string $key, Model|string|null $subject, string $occurrence): bool
    {
        $type = $subject instanceof Model ? $subject::class : (string) $subject;
        $id = $subject instanceof Model ? (string) $subject->getKey() : (string) $subject;

        try {
            AutomationDispatch::query()->create([
                'automation_key' => $key,
                'subject_type' => $type,
                'subject_id' => $id,
                'occurrence_key' => $occurrence,
                'created_at' => now(),
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function exists(string $key, Model|string|null $subject, string $occurrence): bool
    {
        $type = $subject instanceof Model ? $subject::class : (string) $subject;
        $id = $subject instanceof Model ? (string) $subject->getKey() : (string) $subject;

        return AutomationDispatch::query()
            ->where('automation_key', $key)
            ->where('subject_type', $type)
            ->where('subject_id', $id)
            ->where('occurrence_key', $occurrence)
            ->exists();
    }
}
