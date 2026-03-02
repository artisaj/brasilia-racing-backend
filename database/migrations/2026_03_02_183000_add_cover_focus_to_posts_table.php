<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->unsignedSmallInteger('cover_focus_x')->default(50)->after('cover_media_id');
            $table->unsignedSmallInteger('cover_focus_y')->default(50)->after('cover_focus_x');
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table): void {
            $table->dropColumn(['cover_focus_x', 'cover_focus_y']);
        });
    }
};
