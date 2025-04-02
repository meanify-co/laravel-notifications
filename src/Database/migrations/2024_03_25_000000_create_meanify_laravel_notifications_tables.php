<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * @return void
     */
    public function up(): void
    {

        Schema::dropIfExists('notifications_templates_variables');
        Schema::dropIfExists('notifications_templates_translations');
        Schema::dropIfExists('notifications_templates');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('emails_layouts');

        Schema::create('emails_layouts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key')->unique(); // ex: default_dark, festive, etc.
            $table->json('metadata')->nullable(); // cores, fontes, etc.
            $table->longText('blade_template'); // com {{ $content }} e variáveis
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notifications_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_layout_id')->nullable()->constrained('emails_layouts');
            $table->string('key')->unique(); // e.g.: user_registered
            $table->json('available_channels'); // ['email', 'in_app']
            $table->json('force_channels')->nullable(); // channels
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('notifications_templates_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_template_id')->nullable();
            $table->string('locale', 10); // e.g.: pt-br, en-us
            $table->string('subject')->nullable(); // email subject
            $table->string('title')->nullable(); // email subject
            $table->text('body')->nullable(); // rendered email body
            $table->string('short_message')->nullable(); // in-app
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('notification_template_id','nft_notification_template_id_fk')->references('id')->on('notifications_templates');
        });

        Schema::create('notifications_templates_variables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('notification_template_id')->nullable();
            $table->string('key'); // e.g.: user_name, action_url
            $table->text('description')->nullable();
            $table->string('example')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('notification_template_id','nfv_notification_template_id_fk')->references('id')->on('notifications_templates');
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_template_id')->nullable()->constrained('notifications_templates')->nullOnDelete();
            $table->unsignedBigInteger('application_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('channel'); // email, in_app, etc.
            $table->json('payload');
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        if(Schema::hasTable('accounts'))
        {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            });
        }
        else
        {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('account_id');
            });
        }

        if(Schema::hasTable('applications'))
        {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('application_id')->references('id')->on('applications')->onDelete('cascade');
            });
        }
        else
        {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('application_id');
            });
        }

        if(Schema::hasTable('sessions'))
        {
            Schema::table('notifications', function (Blueprint $table) {
                $table->foreign('session_id')->references('id')->on('sessions')->onDelete('cascade');
            });
        }
        else
        {
            Schema::table('notifications', function (Blueprint $table) {
                $table->dropColumn('session_id');
            });
        }
    }

    /**
     * @return void
     */
    public function down(): void
    {
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('notifications_templates_variables');
        Schema::dropIfExists('notifications_templates_translations');
        Schema::dropIfExists('notifications_templates');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('emails_layouts');

        Schema::enableForeignKeyConstraints();
    }
};
