<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserbackWebhooksTable extends Migration
{
    public function up()
    {
        Schema::create('userback_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('userback_id')->unique();
            $table->string('action');
            $table->string('type');
            $table->string('project')->nullable();
            $table->string('feedback_type')->nullable();
            $table->string('email')->nullable();
            $table->string('name')->nullable();
            $table->string('page')->nullable();
            $table->string('priority')->nullable();
            $table->string('browser')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('screenshot_url')->nullable();
            $table->string('share_url')->nullable();
            $table->json('raw_payload');
            $table->boolean('pushed_to_hubspot')->default(false);
            $table->timestamp('hubspot_pushed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('userback_webhooks');
    }
}
