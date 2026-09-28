<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Anyone can now sign up, so the roles become admin and member, and a member
 * only sees the admin's collection when they joined by invite. Everyone
 * already here was invited or made on the server, so they keep that view and
 * count as having confirmed their email.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('sees_admin_collection')->default(false)->after('role');
            $table->string('role')->default('member')->change();
        });

        DB::table('users')->where('role', 'owner')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'tester')->update(['role' => 'member', 'sees_admin_collection' => true]);
        DB::table('users')->whereNull('email_verified_at')->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        DB::table('users')->where('role', 'admin')->update(['role' => 'owner']);
        DB::table('users')->where('role', 'member')->update(['role' => 'tester']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('tester')->change();
            $table->dropColumn('sees_admin_collection');
        });
    }
};
