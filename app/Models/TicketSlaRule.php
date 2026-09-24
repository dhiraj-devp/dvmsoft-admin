<?php

namespace App\Models;

use App\Concerns\Auditable;
use App\Enums\TicketPriority;
use Database\Factories\TicketSlaRuleFactory;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketSlaRule extends Model
{
    /** @use HasFactory<TicketSlaRuleFactory> */
    use Auditable, HasFactory, HasUlids;

    protected string $auditModule = 'ticket_sla';

    protected $fillable = [
        'priority',
        'hours',
        'warning_hours',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TicketPriority::class,
            'hours' => 'integer',
            'warning_hours' => 'integer',
        ];
    }
}
