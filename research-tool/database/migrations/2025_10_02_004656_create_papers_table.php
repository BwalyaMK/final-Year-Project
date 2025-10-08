<?php

namespace App\Database\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('papers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('file_path');
            $table->string('file_name');
            $table->longText('content'); // Extracted PDF text
            $table->integer('pages')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('papers');
    }
};