<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_daily_updates', function (Blueprint $table) {
            $table->string('manager_stamp')->nullable()->after('reviewed_at');
        });
    }

    public function down(): void
    {
        Schema::table('work_daily_updates', function (Blueprint $table) {
            $table->dropColumn('manager_stamp');
        });
    }
};
