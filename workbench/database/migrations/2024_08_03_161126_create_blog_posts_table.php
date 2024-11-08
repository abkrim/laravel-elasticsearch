<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use PDPhilip\Elasticsearch\Schema\Blueprint;
use PDPhilip\Elasticsearch\Schema\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::deleteIfExists('blog_posts');
        Schema::createIfNotExists('blog_posts', function (Blueprint $index) {

            $index->text('title');
            $index->keyword('title');

            $index->text('content');

            $index->nested('comments');

            $index->integer('status');
            $index->boolean('active');

            $index->date('created_at');
            $index->date('updated_at');
            $index->date('deleted_at');

        });

    }

    public function down(): void
    {
        Schema::deleteIfExists('blog_posts');
    }
};
