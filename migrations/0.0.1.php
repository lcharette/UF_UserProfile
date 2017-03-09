<?php
    use Illuminate\Database\Capsule\Manager as Capsule;
    use Illuminate\Database\Schema\Blueprint;

    /**
     * Custom fields table
     */
    if (!$schema->hasTable('profile_fields')) {
        $schema->create('profile_fields', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id')->unsigned();
            $table->string('slug', 255);
            $table->string('value', 255)->nullable();
            $table->timestamps();

            $table->engine = 'InnoDB';
            $table->collation = 'utf8_unicode_ci';
            $table->charset = 'utf8';
            $table->foreign('user_id')->references('id')->on('users');
            $table->index('id');
        });
        echo "Created table 'profile_fields'..." . PHP_EOL;
    } else {
        echo "Table 'profile_fields' already exists.  Skipping..." . PHP_EOL;
    }
