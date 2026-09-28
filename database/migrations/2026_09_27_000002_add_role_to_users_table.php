<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * New people are testers. The account that existed before roles did is the
     * owner's, since until now there was only ever one.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tester')->after('email');
            $table->timestamp('disabled_at')->nullable()->after('role');
        });

        $first = DB::table('users')->min('id');

        if ($first !== null) {
            DB::table('users')->where('id', $first)->update(['role' => 'owner']);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'disabled_at']);
        });
    }
};
