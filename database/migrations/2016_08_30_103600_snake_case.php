<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class SnakeCase extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('access_control_structure', function ($table) {
            $table->renameColumn('"parentId"', 'parent_id');
            $table->renameColumn('"accessKey"', 'access_key');
        });

        Schema::table('access_controls', function ($table) {
            $table->renameColumn('"userId"', 'user_id');
            $table->renameColumn('"groupId"', 'group_id');
            $table->renameColumn('"accessControlStructureId"', 'access_control_structure_id');
        });

        Schema::table('assignments', function ($table) {
            $table->renameColumn('"atomEntityId"', 'atom_entity_id');
            $table->renameColumn('"userId"', 'user_id');
            $table->renameColumn('"taskId"', 'task_id');
            $table->renameColumn('"taskEnd"', 'task_end');
            $table->renameColumn('"createdBy"', 'created_by');
        });

        Schema::table('atoms', function ($table) {
            $table->renameColumn('"entityId"', 'entity_id');
            $table->renameColumn('"moleculeCode"', 'molecule_code');
            $table->renameColumn('"alphaTitle"', 'alpha_title');
            $table->renameColumn('"modifiedBy"', 'modified_by');
            $table->renameColumn('"statusId"', 'status_id');
        });

        Schema::table('comments', function ($table) {
            $table->renameColumn('"atomEntityId"', 'atom_entity_id');
            $table->renameColumn('"userId"', 'user_id');
            $table->renameColumn('"parentId"', 'parent_id');
        });

        Schema::table('users', function ($table) {
            $table->renameColumn('"groupId"', 'group_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('access_control_structure', function ($table) {
            $table->renameColumn('parent_id', '"parentId"');
            $table->renameColumn('access_key', '"accessKey"');
        });

        Schema::table('access_controls', function ($table) {
            $table->renameColumn('user_id', '"userId"');
            $table->renameColumn('group_id', '"groupId"');
            $table->renameColumn('access_control_structure_id', '"accessControlStructureId"');
        });

        Schema::table('assignments', function ($table) {
            $table->renameColumn('atom_entity_id', '"atomEntityId"');
            $table->renameColumn('user_id', '"userId"');
            $table->renameColumn('task_id', '"taskId"');
            $table->renameColumn('task_end', '"taskEnd"');
            $table->renameColumn('created_by', '"createdBy"');
        });

        Schema::table('atoms', function ($table) {
            $table->renameColumn('entity_id', '"entityId"');
            $table->renameColumn('molecule_code', '"moleculeCode"');
            $table->renameColumn('alpha_title', '"alphaTitle"');
            $table->renameColumn('modified_by', '"modifiedBy"');
            $table->renameColumn('status_id', '"statusId"');
        });

        Schema::table('comments', function ($table) {
            $table->renameColumn('atom_entity_id', '"atomEntityId"');
            $table->renameColumn('user_id', '"userId"');
            $table->renameColumn('parent_id', '"parentId"');
        });

        Schema::table('users', function ($table) {
            $table->renameColumn('group_id', '"groupId"');
        });
    }
}
