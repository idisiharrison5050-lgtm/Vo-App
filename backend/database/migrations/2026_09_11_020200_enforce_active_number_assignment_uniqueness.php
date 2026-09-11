<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                "CREATE UNIQUE INDEX number_assignments_one_active_per_number ON number_assignments (phone_number_id) WHERE status = 'active'"
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS number_assignments_one_active_per_number');
        }
    }
};
