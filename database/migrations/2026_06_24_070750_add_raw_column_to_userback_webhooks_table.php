<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRawColumnToUserbackWebhooksTable extends Migration
{
    public function up()
    {
        Schema::table('userback_webhooks', function (Blueprint $table) {
            // longText handles massive payloads (console_logs can be huge)
            $table->longText('raw')->nullable()->after('raw_payload');
            // change raw_payload to nullable so save doesn't fail if JSON is malformed
            $table->json('raw_payload')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('userback_webhooks', function (Blueprint $table) {
            $table->dropColumn('raw');
        });
    }
}
