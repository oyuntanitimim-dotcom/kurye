<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_sites', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('primary_domain')->nullable()->index();
            $table->boolean('is_published')->default(true);
            $table->json('theme')->nullable();
            $table->timestamps();
        });

        Schema::create('marketing_pages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_site_id')->constrained('marketing_sites')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->string('meta_description')->nullable();
            $table->string('status', 32)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedBigInteger('published_version_id')->nullable();
            $table->timestamps();

            $table->unique(['marketing_site_id', 'slug']);
        });

        Schema::create('marketing_page_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_page_id')->constrained('marketing_pages')->cascadeOnDelete();
            $table->unsignedInteger('version')->default(1);
            $table->json('blocks_json');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('marketing_pages', function (Blueprint $table): void {
            $table->foreign('published_version_id')
                ->references('id')
                ->on('marketing_page_versions')
                ->nullOnDelete();
        });

        Schema::create('marketing_menus', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_site_id')->constrained('marketing_sites')->cascadeOnDelete();
            $table->string('key', 64); // e.g. header
            $table->timestamps();

            $table->unique(['marketing_site_id', 'key']);
        });

        Schema::create('marketing_menu_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_menu_id')->constrained('marketing_menus')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('marketing_menu_items')->cascadeOnDelete();
            $table->string('label');
            $table->string('url', 2048);
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketing_slides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_site_id')->constrained('marketing_sites')->cascadeOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('image_path', 2048)->nullable();
            $table->string('cta_label')->nullable();
            $table->string('cta_url', 2048)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('marketing_footer_columns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_site_id')->constrained('marketing_sites')->cascadeOnDelete();
            $table->string('heading')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('marketing_footer_links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('marketing_footer_column_id')->constrained('marketing_footer_columns')->cascadeOnDelete();
            $table->string('label');
            $table->string('url', 2048);
            $table->boolean('open_in_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_footer_links');
        Schema::dropIfExists('marketing_footer_columns');
        Schema::dropIfExists('marketing_slides');
        Schema::dropIfExists('marketing_menu_items');
        Schema::dropIfExists('marketing_menus');
        Schema::table('marketing_pages', function (Blueprint $table): void {
            $table->dropForeign(['published_version_id']);
        });
        Schema::dropIfExists('marketing_page_versions');
        Schema::dropIfExists('marketing_pages');
        Schema::dropIfExists('marketing_sites');
    }
};
