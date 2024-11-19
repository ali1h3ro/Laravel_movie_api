<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMovieBatchesTable extends Migration
{
    public function up()
    {
        Schema::create('movie_batches', function (Blueprint $table) {
            $table->id();

            // Timing
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();

            // Status
            $table->string('status')->default('pending');

            // Pagination
            $table->integer('total_pages')->nullable();
            $table->integer('pages_processed')->default(0);

            // Movies
            $table->integer('total_movies')->nullable();
            $table->integer('movies_processed')->default(0);

            // Error handling
            $table->text('error_message')->nullable();

            // API metadata
            $table->string('source_api')->nullable();
            $table->string('api_endpoint')->nullable();
            $table->json('fetch_parameters')->nullable();

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('movie_batches');
    }
}
