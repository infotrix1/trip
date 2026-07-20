<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table): void {
            $table->unsignedInteger('position')->default(0)->after('user_id');
            $table->index(['user_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table): void {
            $table->dropIndex(['user_id', 'position']);
            $table->dropColumn('position');
        });
    }
};
