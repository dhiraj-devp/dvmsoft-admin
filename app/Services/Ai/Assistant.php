<?php

namespace App\Services\Ai;

use App\Ai\AiManager;
use App\Ai\AiResponse;
use App\Ai\PromptSanitizer;
use App\Ai\SystemPrompt;
use App\Contracts\AiServiceInterface;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class Assistant
{
    public function __construct(
        protected AiManager $manager,
        protected PromptSanitizer $sanitizer,
        protected AuditLogger $audit,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function generate(string $task, array $context, ?Model $record = null): array
    {
        $this->manager->ensureEnabled();

        $response = $this->driver()->complete(
            SystemPrompt::for($task),
            $this->sanitizer->encode($context),
            ['task' => $task, 'model' => $this->manager->model(), 'max_tokens' => $this->manager->maxTokens()],
        );

        return $this->normalize($task, $response);
    }

    protected function driver(): AiServiceInterface
    {
        return $this->manager->driver();
    }

    /**
     * @return array<string, mixed>
     */
    protected function normalize(string $task, AiResponse $response): array
    {
        $data = $response->data;

        foreach ($this->listKeys($task) as $key) {
            if (isset($data[$key]) && ! is_array($data[$key])) {
                $data[$key] = array_filter([(string) $data[$key]]);
            } elseif (! isset($data[$key])) {
                $data[$key] = in_array($key, $this->listKeys($task), true) ? [] : '';
            }
        }

        return $data;
    }

    /**
     * @return list<string>
     */
    protected function listKeys(string $task): array
    {
        return match ($task) {
            'lead' => ['risks'],
            'requirement' => ['acceptance_criteria', 'missing_information', 'risks'],
            'quotation' => ['line_item_descriptions', 'missing_pricing_information'],
            'project' => ['overdue_tasks', 'blocked_tasks', 'milestone_risks', 'change_request_risks', 'next_actions'],
            'ticket' => ['troubleshooting_steps'],
            'finance' => ['overdue_patterns', 'unusual_expenses', 'attention', 'collection_suggestions'],
            'overview' => ['sales_risks', 'project_risks', 'finance_risks', 'support_risks', 'operational_items', 'suggested_actions'],
            default => [],
        };
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function auditApplied(?Model $record, array $meta): void
    {
        $this->audit->record(
            action: 'applied',
            module: 'ai',
            auditable: $record,
            newValues: $meta,
            user: Auth::guard('web')->user(),
        );
    }
}
