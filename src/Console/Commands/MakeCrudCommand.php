<?php

namespace TaskcoDigital\CrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {name : Resource name. e.g. Blog or Admin/Blog}
                                      {--model= : Optional explicit model class name}
                                      {--force : Overwrite existing files}';

    protected $description = 'Generate API CRUD (Model, Request, Service, Controller, Migration, Seeder)';

    public function handle()
    {
        $rawName = trim($this->argument('name'));

        // Accept both / and \ as separators
        $parts = preg_split('#[\\/\\\\]+#', $rawName);
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));

        if (empty($parts)) {
            $this->error('Invalid name.');
            return 1;
        }

        $resourceClass = Str::studly(array_pop($parts)); // "Blog"
        $foldersStudly = array_map(fn ($p) => Str::studly($p), $parts);

        // Namespace suffix like "\Admin\Team"
        $nsSuffix = $foldersStudly ? '\\' . implode('\\', $foldersStudly) : '';

        // File path prefix like "Admin/Team/"
        $pathPrefix = $foldersStudly ? implode('/', $foldersStudly) . '/' : '';

        // Names and namespaces
        $modelClass = $this->option('model')
            ? Str::studly($this->option('model'))
            : $resourceClass;

        $names = [
            'class'             => $resourceClass,                         // e.g. Blog
            'variable'          => Str::camel($resourceClass),             // e.g. blog
            'resource'          => Str::kebab(Str::plural($resourceClass)),// e.g. blogs
            'table'             => Str::snake(Str::plural($modelClass)),   // e.g. blogs
            'namespaceSuffix'   => $nsSuffix,                              // e.g. \Admin\Team
            'pathPrefix'        => $pathPrefix,                            // e.g. Admin/Team/
            'modelClass'        => $modelClass,                            // e.g. Blog or CustomModel
        ];

        // Destination namespaces
        $namespaces = [
            'model'      => 'App\\Models' . $nsSuffix,
            'request'    => 'App\\Http\\Requests' . $nsSuffix,
            'service'    => 'App\\Services' . $nsSuffix,
            'controller' => 'App\\Http\\Controllers\\Api' . $nsSuffix,
        ];

        // Destination directories
        $paths = [
            'model'      => app_path('Models/' . $names['pathPrefix']),
            'request'    => app_path('Http/Requests/' . $names['pathPrefix']),
            'service'    => app_path('Services/' . $names['pathPrefix']),
            'controller' => app_path('Http/Controllers/Api/' . $names['pathPrefix']),
        ];
        foreach ($paths as $dir) {
            File::ensureDirectoryExists($dir);
        }

        // Generate files
        $this->generateModel($paths['model'], $namespaces['model'], $names);
        $this->generateRequest($paths['request'], $namespaces['request'], $names);
        $this->generateService($paths['service'], $namespaces['service'], $names, $namespaces);
        $this->generateController($paths['controller'], $namespaces['controller'], $names, $namespaces);
        $this->generateMigration($names);
        $this->generateSeeder($names, $namespaces);

        // Helpful output
        $this->line('');
        $this->info('✅ CRUD generation completed!');
        $this->line('');
        $this->info('Next:');
        $this->line('  php artisan migrate');
        $this->line('  php artisan db:seed --class=' . $names['class'] . 'Seeder');
        $this->line('');
        $this->info('API endpoints:');
        $this->line('  GET    /api/' . $names['resource']);
        $this->line('  POST   /api/' . $names['resource']);
        $this->line('  GET    /api/' . $names['resource'] . '/{id}');
        $this->line('  PUT    /api/' . $names['resource'] . '/{id}');
        $this->line('  DELETE /api/' . $names['resource'] . '/{id}');

        return 0;
    }

    protected function generateModel(string $dir, string $namespace, array $names): void
    {
        $dest = $dir . $names['modelClass'] . '.php';
        if ($this->skipIfExists($dest)) return;

        $this->putFromStub('model', $dest, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $names['modelClass'],
            '{{ table }}'     => $names['table'],
        ]);
        $this->info("Model: {$dest}");
    }

    protected function generateRequest(string $dir, string $namespace, array $names): void
    {
        $dest = $dir . $names['class'] . 'Request.php';
        if ($this->skipIfExists($dest)) return;

        $this->putFromStub('request', $dest, [
            '{{ namespace }}' => $namespace,
            '{{ class }}'     => $names['class'] . 'Request',
        ]);
        $this->info("Request: {$dest}");
    }

    protected function generateService(string $dir, string $namespace, array $names, array $namespaces): void
    {
        $dest = $dir . $names['class'] . 'Service.php';
        if ($this->skipIfExists($dest)) return;

        $this->putFromStub('service', $dest, [
            '{{ namespace }}'        => $namespace,
            '{{ class }}'            => $names['class'] . 'Service',
            '{{ modelNamespace }}'   => $namespaces['model'],
            '{{ model }}'            => $names['modelClass'],
            '{{ variable }}'         => $names['variable'],
        ]);
        $this->info("Service: {$dest}");
    }

    protected function generateController(string $dir, string $namespace, array $names, array $namespaces): void
    {
        $dest = $dir . $names['class'] . 'Controller.php';
        if ($this->skipIfExists($dest)) return;

        $this->putFromStub('controller', $dest, [
            '{{ namespace }}'         => $namespace,
            '{{ class }}'             => $names['class'] . 'Controller',
            '{{ requestNamespace }}'  => $namespaces['request'],
            '{{ request }}'           => $names['class'] . 'Request',
            '{{ serviceNamespace }}'  => $namespaces['service'],
            '{{ service }}'           => $names['class'] . 'Service',
            '{{ modelNamespace }}'    => $namespaces['model'],
            '{{ model }}'             => $names['modelClass'],
            '{{ variable }}'          => $names['variable'],
            '{{ resource }}'          => $names['resource'],
        ]);
        $this->info("Controller: {$dest}");
    }

    protected function generateMigration(array $names): void
    {
        $timestamp = date('Y_m_d_His');
        $file = database_path("migrations/{$timestamp}_create_{$names['table']}_table.php");

        // avoid duplicate migration for the same table (simple check)
        $dir = database_path('migrations');
        foreach (glob($dir . '/*_create_' . $names['table'] . '_table.php') as $existing) {
            $file = $existing;
            break;
        }

        $this->putFromStub('migration', $file, [
            '{{ table }}' => $names['table'],
        ], true);

        $this->info("Migration: {$file}");
    }

    protected function generateSeeder(array $names, array $namespaces): void
    {
        $dir = database_path('seeders');
        File::ensureDirectoryExists($dir);

        $file = $dir . '/' . $names['class'] . 'Seeder.php';
        if ($this->skipIfExists($file)) return;

        $this->putFromStub('seeder', $file, [
            '{{ class }}'          => $names['class'] . 'Seeder',
            '{{ modelNamespace }}' => $namespaces['model'],
            '{{ model }}'          => $names['modelClass'],
        ]);
        $this->info("Seeder: {$file}");
    }

    protected function skipIfExists(string $path): bool
    {
        if (File::exists($path) && ! $this->option('force')) {
            $this->warn("Exists (skip): {$path} (use --force to overwrite)");
            return true;
        }
        File::ensureDirectoryExists(dirname($path));
        return false;
    }

    protected function putFromStub(string $stubName, string $dest, array $replacements, bool $force = false): void
    {
        $stub = __DIR__ . '/../../stubs/' . $stubName . '.stub';
        $content = File::get($stub);
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        File::put($dest, $content);
    }
}
