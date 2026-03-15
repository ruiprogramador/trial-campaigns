<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contact_contacts_list', function (Blueprint $table) {
            $table->unique(['contact_id', 'contact_list_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_contacts_list', function (Blueprint $table) {
            $table->dropUnique(['contact_id', 'contact_list_id']);
        });
    }
};
