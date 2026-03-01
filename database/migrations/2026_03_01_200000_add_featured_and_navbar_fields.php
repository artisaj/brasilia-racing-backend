<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->index('is_featured');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->boolean('show_in_navbar')->default(false)->after('description');
            $table->unsignedInteger('navbar_order')->default(0)->after('show_in_navbar');
            $table->index(['show_in_navbar', 'navbar_order']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropIndex(['is_featured']);
            $table->dropColumn('is_featured');
        });

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropIndex(['show_in_navbar', 'navbar_order']);
            $table->dropColumn(['show_in_navbar', 'navbar_order']);
        });
    }
};
