<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectStageMember extends Pivot
{
    use HasUlids;

    protected $table = 'project_stage_members';

    public $incrementing = false;

    protected $keyType = 'string';
}
