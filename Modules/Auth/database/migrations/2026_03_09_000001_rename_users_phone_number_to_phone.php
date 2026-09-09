<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'phone_number') && ! Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('phone_number', 'phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'phone') && ! Schema::hasColumn('users', 'phone_number')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('phone', 'phone_number');
            });
        }
    }
};
