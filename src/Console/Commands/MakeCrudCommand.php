<?php

namespace TaskcoDigital\CrudGenerator\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;

class MakeCrudCommand extends Command
{
    protected $signature = 'make:crud {name} {--model=} {--force}';
    protected $description = 'Generate API CRUD operations with all necessary files';

    public function handle()
    {
        $name = $this->argument('name');
        $modelName = $this->option('model') ?: $name;
        $force = $this->option('force');

        $this->info("🚀 Generating CRUD for: {$name}");
        $this->info("📦 Model: {$modelName}");
        $this->info("📍 Laravel Version: " . app()->version());

        // Debug paths
        $this->info("📁 App Path: " . app_path());
        $this->info("📁 Database Path: " . database_path());
        $this->info("📁 Base Path: " . base_path());

        try {
            // Generate all files
            $this->generateMigration($name);
            $this->generateModel($modelName);
            $this->generateController($name, $modelName);
            $this->generateService($name, $modelName);
            $this->generateRequest($name);
            $this->generateSeeder($name);
            $this->generateRoutes($name);

            // Ensure Resource generation is called
            $this->generateResource($name);

            $this->info('');
            $this->info('✅ CRUD generation completed successfully!');
            $this->info('');
            $this->info('📝 Next steps:');
            $this->info('   1. Run: php artisan migrate');
            $this->info('   2. Optionally run: php artisan db:seed --class=' . Str::studly($name) . 'Seeder');
            $this->info('');
            $this->info('🎯 API Endpoints available at:');
            $routeName = Str::kebab(Str::plural($name));
            $this->info("   GET    /api/{$routeName}");
            $this->info("   POST   /api/{$routeName}");
            $this->info("   GET    /api/{$routeName}/{id}");
            $this->info("   PUT    /api/{$routeName}/{id}");
            $this->info("   DELETE /api/{$routeName}/{id}");
        } catch (\Exception $e) {
            $this->error("❌ Error generating CRUD: " . $e->getMessage());
            $this->error("💡 Error file: " . $e->getFile());
            $this->error("💡 Error line: " . $e->getLine());
            return 1;
        }

        return 0;
    }

    protected function generateResource($name)
    {
        $resourceName = Str::studly($name) . 'Resource';

        $stub = $this->getStub('resource');
        $content = str_replace('{{ class }}', $resourceName, $stub);

        $path = app_path("Http/Resources/{$resourceName}.php");
        $this->ensureDirectoryExists(dirname($path));

        $this->info("📍 Creating resource at: {$path}");
        File::put($path, $content);
        $this->info("📄 Resource created: {$resourceName}.php");

    }


    protected function generateMigration($name)
    {
        $tableName = Str::snake(Str::plural($name));
        $className = 'Create' . Str::studly(Str::plural($name)) . 'Table';
        $timestamp = date('Y_m_d_His');
        $filename = "{$timestamp}_create_{$tableName}_table.php";

        $stub = $this->getStub('migration');
        $content = str_replace(
            ['{{ class }}', '{{ table }}'],
            [$className, $tableName],
            $stub
        );

        $migrationPath = database_path("migrations/{$filename}");
        $this->ensureDirectoryExists(dirname($migrationPath));

        $this->info("📍 Creating migration at: {$migrationPath}");
        File::put($migrationPath, $content);
        $this->info("📄 Migration created: {$filename}");
    }

    protected function generateModel($name)
    {
        $modelName = Str::studly($name);
        $tableName = Str::snake(Str::plural($name));

        $stub = $this->getStub('model');
        $content = str_replace(
            ['{{ class }}', '{{ table }}'],
            [$modelName, $tableName],
            $stub
        );

        $path = app_path("Models/{$modelName}.php");
        $this->ensureDirectoryExists(dirname($path));

        $this->info("📍 Creating model at: {$path}");
        File::put($path, $content);
        $this->info("📄 Model created: {$modelName}.php");
    }
    protected function generateController($name, $modelName)
    {
        $controllerName = Str::studly($name) . 'Controller';
        $modelClass = Str::studly($modelName);
        $serviceName = Str::studly($name) . 'Service';
        $requestName = Str::studly($name) . 'Request';

        // Correctly generate the resource name by appending 'Resource'
        $resourceName = Str::studly($name) . 'Resource';  // Add 'Resource' to the class name

        $stub = $this->getStub('controller');
        $content = str_replace(
            ['{{ class }}', '{{ model }}', '{{ service }}', '{{ request }}', '{{ variable }}', '{{ resource }}'],
            [
                $controllerName,
                $modelClass,
                $serviceName,
                $requestName,
                Str::camel($name),
                $resourceName,  // Use the resource name with 'Resource' appended
            ],
            $stub
        );

        $path = app_path("Http/Controllers/Api/{$controllerName}.php");
        $this->ensureDirectoryExists(dirname($path));

        $this->info("📍 Creating controller at: {$path}");
        File::put($path, $content);
        $this->info("📄 Controller created: {$controllerName}.php");
    }

    protected function generateService($name, $modelName)
    {
        $serviceName = Str::studly($name) . 'Service';
        $modelClass = Str::studly($modelName);

        $stub = $this->getStub('service');
        $content = str_replace(
            ['{{ class }}', '{{ model }}', '{{ variable }}'],
            [$serviceName, $modelClass, Str::camel($name)],
            $stub
        );

        $path = app_path("Services/{$serviceName}.php");
        $this->ensureDirectoryExists(dirname($path));

        $this->info("📍 Creating service at: {$path}");
        File::put($path, $content);
        $this->info("📄 Service created: {$serviceName}.php");
    }

    protected function generateRequest($name)
    {
        $requestName = Str::studly($name) . 'Request';

        $stub = $this->getStub('request');
        $content = str_replace('{{ class }}', $requestName, $stub);

        $path = app_path("Http/Requests/{$requestName}.php");
        $this->ensureDirectoryExists(dirname($path));

        $this->info("📍 Creating request at: {$path}");
        File::put($path, $content);
        $this->info("📄 Request created: {$requestName}.php");
    }

    protected function generateSeeder($name)
    {
        $seederName = Str::studly($name) . 'Seeder';
        $modelClass = Str::studly($name);

        $stub = $this->getStub('seeder');
        $content = str_replace(
            ['{{ class }}', '{{ model }}'],
            [$seederName, $modelClass],
            $stub
        );

        $path = database_path("seeders/{$seederName}.php");
        $this->ensureDirectoryExists(dirname($path));

        $this->info("📍 Creating seeder at: {$path}");
        File::put($path, $content);
        $this->info("📄 Seeder created: {$seederName}.php");
    }

    protected function generateRoutes($name)
    {
        $routeName = Str::kebab(Str::plural($name));
        $controllerName = Str::studly($name) . 'Controller';

        $routes = "\n// {$name} CRUD Routes - Generated by TaskcoDigital CRUD Generator\n";
        $routes .= "Route::apiResource('{$routeName}', App\\Http\\Controllers\\Api\\{$controllerName}::class);\n";

        $apiRoutesPath = base_path('routes/api.php');

        // ✅ If routes/api.php does not exist, run `php artisan install:api`
        if (!File::exists($apiRoutesPath)) {
            $this->warn("⚠️ routes/api.php not found. Running 'php artisan install:api'...");
            $this->call('install:api'); // or Artisan::call('install:api');
        }

        // Double-check if it exists after running install:api
        if (!File::exists($apiRoutesPath)) {
            $this->error("❌ routes/api.php still not found. Please create it manually.");
            return;
        }

        // Check if the route already exists
        $currentRoutes = File::get($apiRoutesPath);
        if (strpos($currentRoutes, "apiResource('{$routeName}'") !== false) {
            $this->warn("⚠️ Route for '{$routeName}' already exists in api.php");
            return;
        }

        File::append($apiRoutesPath, $routes);
        $this->info("📄 Routes added to api.php");
    }


    protected function getStub($type)
    {
        // Check for published stubs first
        $stubPath = base_path("stubs/crud-generator/{$type}.stub");

        if (File::exists($stubPath)) {
            return File::get($stubPath);
        }

        // Fallback to package stubs
        $packageStubPath = __DIR__ . "/../../stubs/{$type}.stub";
        if (File::exists($packageStubPath)) {
            return File::get($packageStubPath);
        }
        // Handle the case where stub is not found
        $this->error("❌ Stub not found for type: {$type}");
    }

    protected function ensureDirectoryExists($directory)
    {
        if (!File::exists($directory)) {
            $this->info("📁 Creating directory: {$directory}");
            File::makeDirectory($directory, 0755, true);
        }
    }
}
