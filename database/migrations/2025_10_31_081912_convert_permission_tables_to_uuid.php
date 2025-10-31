<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        // Step 1: Add UUID columns to main tables
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->uuid('uuid_id')->nullable()->after('id');
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->uuid('uuid_id')->nullable()->after('id');
        });

        // Step 2: Generate UUIDs for existing records
        DB::table($tableNames['permissions'])->get()->each(function ($permission) use ($tableNames) {
            DB::table($tableNames['permissions'])
                ->where('id', $permission->id)
                ->update(['uuid_id' => Str::uuid()]);
        });

        DB::table($tableNames['roles'])->get()->each(function ($role) use ($tableNames) {
            DB::table($tableNames['roles'])
                ->where('id', $role->id)
                ->update(['uuid_id' => Str::uuid()]);
        });

        // Step 3: Add UUID foreign key columns to pivot tables
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($pivotPermission) {
            $table->uuid('uuid_' . $pivotPermission)->nullable()->after($pivotPermission);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($pivotRole) {
            $table->uuid('uuid_' . $pivotRole)->nullable()->after($pivotRole);
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) use ($pivotRole, $pivotPermission) {
            $table->uuid('uuid_' . $pivotPermission)->nullable()->after($pivotPermission);
            $table->uuid('uuid_' . $pivotRole)->nullable()->after($pivotRole);
        });

        // Step 4: Populate UUID foreign keys in pivot tables
        $this->populateUuidForeignKeys($tableNames, $pivotRole, $pivotPermission);

        // Step 5: Drop old foreign key constraints
        $this->dropOldForeignKeys($tableNames, $pivotRole, $pivotPermission);

        // Step 6: Drop old columns and rename UUID columns
        $this->replaceWithUuidColumns($tableNames, $pivotRole, $pivotPermission);

        // Step 7: Add new foreign key constraints
        $this->addUuidForeignKeys($tableNames, $pivotRole, $pivotPermission);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $pivotRole = $columnNames['role_pivot_key'] ?? 'role_id';
        $pivotPermission = $columnNames['permission_pivot_key'] ?? 'permission_id';

        // This is a destructive operation - we cannot reliably convert UUIDs back to auto-incrementing integers
        // Drop and recreate tables with original structure
        Schema::drop($tableNames['role_has_permissions']);
        Schema::drop($tableNames['model_has_roles']);
        Schema::drop($tableNames['model_has_permissions']);
        Schema::drop($tableNames['roles']);
        Schema::drop($tableNames['permissions']);

        // Recreate with original bigint structure (this will lose data)
        $this->createOriginalTables($tableNames, $columnNames, $pivotRole, $pivotPermission);
    }

    private function populateUuidForeignKeys($tableNames, $pivotRole, $pivotPermission): void
    {
        // Update model_has_permissions
        DB::statement("
            UPDATE {$tableNames['model_has_permissions']} mhp
            JOIN {$tableNames['permissions']} p ON mhp.{$pivotPermission} = p.id
            SET mhp.uuid_{$pivotPermission} = p.uuid_id
        ");

        // Update model_has_roles
        DB::statement("
            UPDATE {$tableNames['model_has_roles']} mhr
            JOIN {$tableNames['roles']} r ON mhr.{$pivotRole} = r.id
            SET mhr.uuid_{$pivotRole} = r.uuid_id
        ");

        // Update role_has_permissions
        DB::statement("
            UPDATE {$tableNames['role_has_permissions']} rhp
            JOIN {$tableNames['permissions']} p ON rhp.{$pivotPermission} = p.id
            JOIN {$tableNames['roles']} r ON rhp.{$pivotRole} = r.id
            SET rhp.uuid_{$pivotPermission} = p.uuid_id, rhp.uuid_{$pivotRole} = r.uuid_id
        ");
    }

    private function dropOldForeignKeys($tableNames, $pivotRole, $pivotPermission): void
    {
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($pivotPermission) {
            $table->dropForeign(['permission_id']);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($pivotRole) {
            $table->dropForeign(['role_id']);
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) use ($pivotRole, $pivotPermission) {
            $table->dropForeign(['permission_id']);
            $table->dropForeign(['role_id']);
        });
    }

    private function replaceWithUuidColumns($tableNames, $pivotRole, $pivotPermission): void
    {
        // Drop primary keys first
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) {
            $table->dropPrimary();
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) {
            $table->dropPrimary();
        });

        // Replace columns in main tables
        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('id');
        });

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->renameColumn('uuid_id', 'id');
        });

        Schema::table($tableNames['permissions'], function (Blueprint $table) {
            $table->primary('id');
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->dropPrimary();
            $table->dropColumn('id');
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->renameColumn('uuid_id', 'id');
        });

        Schema::table($tableNames['roles'], function (Blueprint $table) {
            $table->primary('id');
        });

        // Replace columns in pivot tables
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($pivotPermission) {
            $table->dropColumn($pivotPermission);
        });

        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($pivotPermission) {
            $table->renameColumn('uuid_' . $pivotPermission, $pivotPermission);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($pivotRole) {
            $table->dropColumn($pivotRole);
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($pivotRole) {
            $table->renameColumn('uuid_' . $pivotRole, $pivotRole);
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) use ($pivotRole, $pivotPermission) {
            $table->dropColumn([$pivotPermission, $pivotRole]);
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) use ($pivotRole, $pivotPermission) {
            $table->renameColumn('uuid_' . $pivotPermission, $pivotPermission);
            $table->renameColumn('uuid_' . $pivotRole, $pivotRole);
        });
    }

    private function addUuidForeignKeys($tableNames, $pivotRole, $pivotPermission): void
    {
        $teams = config('permission.teams');
        $columnNames = config('permission.column_names');

        // Add foreign keys and primary keys back
        Schema::table($tableNames['model_has_permissions'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            if ($teams) {
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::table($tableNames['model_has_roles'], function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            if ($teams) {
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::table($tableNames['role_has_permissions'], function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });
    }

    private function createOriginalTables($tableNames, $columnNames, $pivotRole, $pivotPermission): void
    {
        $teams = config('permission.teams');

        Schema::create($tableNames['permissions'], static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create($tableNames['roles'], static function (Blueprint $table) use ($teams, $columnNames) {
            $table->bigIncrements('id');
            if ($teams || config('permission.testing')) {
                $table->unsignedBigInteger($columnNames['team_foreign_key'])->nullable();
                $table->index($columnNames['team_foreign_key'], 'roles_team_foreign_key_index');
            }
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            if ($teams || config('permission.testing')) {
                $table->unique([$columnNames['team_foreign_key'], 'name', 'guard_name']);
            } else {
                $table->unique(['name', 'guard_name']);
            }
        });

        Schema::create($tableNames['model_has_permissions'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotPermission, $teams) {
            $table->unsignedBigInteger($pivotPermission);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_permissions_model_id_model_type_index');

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_permissions_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            } else {
                $table->primary([$pivotPermission, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_permissions_permission_model_type_primary');
            }
        });

        Schema::create($tableNames['model_has_roles'], static function (Blueprint $table) use ($tableNames, $columnNames, $pivotRole, $teams) {
            $table->unsignedBigInteger($pivotRole);
            $table->string('model_type');
            $table->unsignedBigInteger($columnNames['model_morph_key']);
            $table->index([$columnNames['model_morph_key'], 'model_type'], 'model_has_roles_model_id_model_type_index');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');
            if ($teams) {
                $table->unsignedBigInteger($columnNames['team_foreign_key']);
                $table->index($columnNames['team_foreign_key'], 'model_has_roles_team_foreign_key_index');
                $table->primary([$columnNames['team_foreign_key'], $pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            } else {
                $table->primary([$pivotRole, $columnNames['model_morph_key'], 'model_type'],
                    'model_has_roles_role_model_type_primary');
            }
        });

        Schema::create($tableNames['role_has_permissions'], static function (Blueprint $table) use ($tableNames, $pivotRole, $pivotPermission) {
            $table->unsignedBigInteger($pivotPermission);
            $table->unsignedBigInteger($pivotRole);

            $table->foreign($pivotPermission)
                ->references('id')
                ->on($tableNames['permissions'])
                ->onDelete('cascade');

            $table->foreign($pivotRole)
                ->references('id')
                ->on($tableNames['roles'])
                ->onDelete('cascade');

            $table->primary([$pivotPermission, $pivotRole], 'role_has_permissions_permission_id_role_id_primary');
        });
    }
};
