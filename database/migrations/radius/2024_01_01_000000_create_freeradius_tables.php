<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FreeRADIUS canonical schema (mysql) — only runs on the "radius" connection.
 *
 * Most existing FreeRADIUS deployments already have these tables; the migration
 * uses Schema::hasTable() guards so it is safe to run anyway. To execute:
 *
 *     php artisan migrate --database=radius --path=database/migrations/radius
 */
return new class extends Migration
{
    public function up(): void
    {
        $schema = Schema::connection('radius');

        if (!$schema->hasTable('radcheck')) {
            $schema->create('radcheck', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('username', 64)->default('');
                $t->string('attribute', 64)->default('');
                $t->char('op', 2)->default('==');
                $t->string('value', 253)->default('');
                $t->index('username', 'radcheck_username_idx');
            });
        }

        if (!$schema->hasTable('radreply')) {
            $schema->create('radreply', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('username', 64)->default('');
                $t->string('attribute', 64)->default('');
                $t->char('op', 2)->default('=');
                $t->string('value', 253)->default('');
                $t->index('username', 'radreply_username_idx');
            });
        }

        if (!$schema->hasTable('radusergroup')) {
            $schema->create('radusergroup', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('username', 64)->default('');
                $t->string('groupname', 64)->default('');
                $t->unsignedInteger('priority')->default(1);
                $t->index('username', 'radusergroup_username_idx');
            });
        }

        if (!$schema->hasTable('radgroupcheck')) {
            $schema->create('radgroupcheck', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('groupname', 64)->default('');
                $t->string('attribute', 64)->default('');
                $t->char('op', 2)->default('==');
                $t->string('value', 253)->default('');
                $t->index('groupname', 'radgroupcheck_groupname_idx');
            });
        }

        if (!$schema->hasTable('radgroupreply')) {
            $schema->create('radgroupreply', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('groupname', 64)->default('');
                $t->string('attribute', 64)->default('');
                $t->char('op', 2)->default('=');
                $t->string('value', 253)->default('');
                $t->index('groupname', 'radgroupreply_groupname_idx');
            });
        }

        if (!$schema->hasTable('radacct')) {
            $schema->create('radacct', function (Blueprint $t) {
                $t->bigIncrements('radacctid');
                $t->string('acctsessionid', 64)->default('')->index();
                $t->string('acctuniqueid', 32)->default('')->unique();
                $t->string('username', 64)->default('')->index();
                $t->string('groupname', 64)->default('');
                $t->string('realm', 64)->nullable();
                $t->string('nasipaddress', 15)->default('')->index();
                $t->string('nasportid', 32)->nullable();
                $t->string('nasporttype', 32)->nullable();
                $t->dateTime('acctstarttime')->nullable()->index();
                $t->dateTime('acctupdatetime')->nullable();
                $t->dateTime('acctstoptime')->nullable()->index();
                $t->unsignedInteger('acctinterval')->nullable();
                $t->unsignedInteger('acctsessiontime')->nullable();
                $t->string('acctauthentic', 32)->nullable();
                $t->string('connectinfo_start', 50)->nullable();
                $t->string('connectinfo_stop', 50)->nullable();
                $t->unsignedBigInteger('acctinputoctets')->nullable();
                $t->unsignedBigInteger('acctoutputoctets')->nullable();
                $t->string('calledstationid', 50)->default('');
                $t->string('callingstationid', 50)->default('');
                $t->string('acctterminatecause', 32)->default('');
                $t->string('servicetype', 32)->nullable();
                $t->string('framedprotocol', 32)->nullable();
                $t->string('framedipaddress', 15)->default('');
                $t->string('framedipv6address', 45)->nullable();
                $t->string('framedipv6prefix', 45)->nullable();
                $t->string('framedinterfaceid', 44)->nullable();
                $t->string('delegatedipv6prefix', 45)->nullable();
                $t->string('class', 64)->nullable();
            });
        }

        if (!$schema->hasTable('radpostauth')) {
            $schema->create('radpostauth', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->string('username', 64)->default('')->index();
                $t->string('pass', 64)->default('');
                $t->string('reply', 32)->default('');
                $t->dateTime('authdate')->useCurrent();
            });
        }
    }

    public function down(): void
    {
        // Dangerous — RADIUS tables are usually shared infra. Only drop in dev.
        $tables = ['radpostauth', 'radacct', 'radgroupreply', 'radgroupcheck', 'radusergroup', 'radreply', 'radcheck'];
        foreach ($tables as $table) {
            Schema::connection('radius')->dropIfExists($table);
        }
    }
};
