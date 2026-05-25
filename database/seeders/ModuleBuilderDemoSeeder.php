<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Services\ModuleGenerator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ModuleBuilderDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $generator = app(ModuleGenerator::class);

        $usersModule = $this->createUsersModuleWrapper();
        $projectsModule = $this->createProjectsModule($generator);
        $tasksModule = $this->createTasksModule($generator, $projectsModule);
        $this->createTaskCommentsModule($generator, $tasksModule, $usersModule);
    }

    protected function createUsersModuleWrapper(): Module
    {
        $module = Module::query()->firstOrCreate([
            'table_name' => 'users',
        ], [
            'name' => 'Users',
            'description' => 'Application users',
            'icon' => 'users',
            'soft_deletes' => false,
            'has_timestamps' => true,
            'settings' => [
                'search' => true,
                'pagination' => 8,
                'sorting' => true,
                'filters' => true,
            ],
            'generated_at' => now(),
        ]);

        if (! $module->fields()->exists()) {
            $module->fields()->createMany([
                [
                    'label' => 'Name',
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 0,
                ],
                [
                    'label' => 'Email',
                    'name' => 'email',
                    'type' => 'email',
                    'required' => true,
                    'nullable' => false,
                    'unique' => true,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 1,
                ],
            ]);
        }

        return $module->fresh('fields');
    }

    protected function createProjectsModule(ModuleGenerator $generator): Module
    {
        $module = Module::query()->firstOrCreate([
            'table_name' => 'projects',
        ], [
            'name' => 'Projects',
            'description' => 'Top-level project records',
            'icon' => 'briefcase',
            'soft_deletes' => true,
            'has_timestamps' => true,
            'settings' => [
                'search' => true,
                'pagination' => 8,
                'sorting' => true,
                'filters' => true,
            ],
        ]);

        if (! $module->fields()->exists()) {
            $module->fields()->createMany([
                [
                    'label' => 'Name',
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                    'nullable' => false,
                    'unique' => true,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 0,
                ],
                [
                    'label' => 'Description',
                    'name' => 'description',
                    'type' => 'rich_text',
                    'required' => false,
                    'nullable' => true,
                    'unique' => false,
                    'show_in_list' => false,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'sortable' => false,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 1,
                ],
                [
                    'label' => 'Start Date',
                    'name' => 'start_date',
                    'type' => 'date',
                    'required' => false,
                    'nullable' => true,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 2,
                ],
                [
                    'label' => 'End Date',
                    'name' => 'end_date',
                    'type' => 'date',
                    'required' => false,
                    'nullable' => true,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 3,
                ],
                [
                    'label' => 'Budget',
                    'name' => 'budget',
                    'type' => 'currency',
                    'required' => false,
                    'nullable' => true,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 4,
                ],
                [
                    'label' => 'Status',
                    'name' => 'status',
                    'type' => 'select',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'options' => ['Planning', 'Active', 'Completed'],
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 5,
                ],
            ]);

            if (! Schema::hasTable($module->table_name)) {
                $generator->generate($module);
            } elseif (! $module->generated_at) {
                $module->forceFill(['generated_at' => now()])->save();
            }
        }

        return $module->fresh('fields');
    }

    protected function createTasksModule(ModuleGenerator $generator, Module $projectsModule): Module
    {
        $module = Module::query()->firstOrCreate([
            'table_name' => 'tasks',
        ], [
            'name' => 'Tasks',
            'description' => 'Project tasks',
            'icon' => 'clipboard-list',
            'soft_deletes' => true,
            'has_timestamps' => true,
            'settings' => [
                'search' => true,
                'pagination' => 8,
                'sorting' => true,
                'filters' => true,
            ],
        ]);

        if (! $module->fields()->exists()) {
            $module->fields()->createMany([
                [
                    'label' => 'Title',
                    'name' => 'title',
                    'type' => 'text',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 0,
                ],
                [
                    'label' => 'Project',
                    'name' => 'project_id',
                    'type' => 'relationship',
                    'relationship_module_id' => $projectsModule->id,
                    'relationship_display_field' => 'name',
                    'relationship_type' => 'belongs_to',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'sort_order' => 1,
                ],
                [
                    'label' => 'Description',
                    'name' => 'description',
                    'type' => 'textarea',
                    'required' => false,
                    'nullable' => true,
                    'unique' => false,
                    'show_in_list' => false,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => false,
                    'sortable' => false,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 2,
                ],
                [
                    'label' => 'Due Date',
                    'name' => 'due_date',
                    'type' => 'date',
                    'required' => false,
                    'nullable' => true,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 3,
                ],
                [
                    'label' => 'Status',
                    'name' => 'status',
                    'type' => 'select',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'options' => ['Open', 'In Progress', 'Done'],
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 4,
                ],
            ]);

            if (! Schema::hasTable($module->table_name)) {
                $generator->generate($module);
            } elseif (! $module->generated_at) {
                $module->forceFill(['generated_at' => now()])->save();
            }
        }

        return $module->fresh('fields');
    }

    protected function createTaskCommentsModule(ModuleGenerator $generator, Module $tasksModule, Module $usersModule): Module
    {
        $module = Module::query()->firstOrCreate([
            'table_name' => 'task_comments',
        ], [
            'name' => 'Task Comments',
            'description' => 'Comments attached to project tasks',
            'icon' => 'chat-bubble-left-right',
            'soft_deletes' => true,
            'has_timestamps' => true,
            'settings' => [
                'search' => true,
                'pagination' => 12,
                'sorting' => true,
                'filters' => true,
            ],
        ]);

        if (! $module->fields()->exists()) {
            $module->fields()->createMany([
                [
                    'label' => 'Task',
                    'name' => 'task_id',
                    'type' => 'relationship',
                    'relationship_module_id' => $tasksModule->id,
                    'relationship_display_field' => 'title',
                    'relationship_type' => 'belongs_to',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'sort_order' => 0,
                ],
                [
                    'label' => 'User',
                    'name' => 'user_id',
                    'type' => 'relationship',
                    'relationship_module_id' => $usersModule->id,
                    'relationship_display_field' => 'name',
                    'relationship_type' => 'belongs_to',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => true,
                    'sortable' => true,
                    'sort_order' => 1,
                ],
                [
                    'label' => 'Comment',
                    'name' => 'comment',
                    'type' => 'textarea',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => false,
                    'visible_in_form' => true,
                    'searchable' => true,
                    'filterable' => false,
                    'sortable' => false,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 2,
                ],
                [
                    'label' => 'Posted At',
                    'name' => 'posted_at',
                    'type' => 'datetime',
                    'required' => true,
                    'nullable' => false,
                    'unique' => false,
                    'show_in_list' => true,
                    'visible_in_form' => true,
                    'searchable' => false,
                    'filterable' => true,
                    'sortable' => true,
                    'relationship_type' => 'belongs_to',
                    'sort_order' => 3,
                ],
            ]);

            if (! Schema::hasTable($module->table_name)) {
                $generator->generate($module);
            } elseif (! $module->generated_at) {
                $module->forceFill(['generated_at' => now()])->save();
            }
        }

        return $module->fresh('fields');
    }
}
