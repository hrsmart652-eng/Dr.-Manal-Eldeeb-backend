<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title_ar');
            $table->string('title_en')->nullable();
            $table->text('description_ar');
            $table->text('description_en')->nullable();
            $table->string('slug');
            $table->unique(['slug', 'author_id']);
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->foreignId('author_id')->constrained('instructors')->onDelete('cascade');
            $table->string('isbn')->unique()->nullable();
            $table->string('cover_image')->nullable();
            $table->enum('format', ['pdf', 'epub', 'physical', 'both'])->default('pdf');
            $table->decimal('digital_price', 10, 2)->unsigned()->default(0);
            $table->decimal('physical_price', 10, 2)->unsigned()->nullable();
            $table->decimal('discount_price', 10, 2)->unsigned()->nullable();


            $table->string('publisher')->nullable();
            $table->date('publication_date')->nullable();
           $table->unsignedInteger('pages')->default(0);
            $table->string('language')->default('ar');
            $table->enum('status', ['draft', 'published', 'out_of_stock'])->default('draft');
            $table->string('file_path')->nullable(); // For digital version
            $table->integer('file_size_mb')->nullable();
            $table->unsignedInteger('total_sales')->default(0);

            $table->integer('stock_quantity')->nullable(); // For physical books
            $table->decimal('rating', 3, 2)->default(0);
            $table->integer('total_reviews')->default(0);
            $table->json('sample_pages')->nullable(); // Preview pages
            $table->boolean('is_featured')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['status', 'is_featured']);
            $table->index('author_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};