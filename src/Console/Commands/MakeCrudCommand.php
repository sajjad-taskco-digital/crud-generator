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

        $stub = $this->getStub('controller');
        $content = str_replace(
            ['{{ class }}', '{{ model }}', '{{ service }}', '{{ request }}', '{{ variable }}', '{{ resource }}'],
            [$controllerName, $modelClass, $serviceName, $requestName, Str::camel($name), Str::plural(Str::lower($name))],
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

//    protected function generateRoutes($name)
//    {
//        $routeName = Str::kebab(Str::plural($name));
//        $controllerName = Str::studly($name) . 'Controller';
//
//        $routes = "\n// {$name} CRUD Routes - Generated by TaskcoDigital CRUD Generator\n";
//        $routes .= "Route::apiResource('{$routeName}', App\\Http\\Controllers\\Api\\{$controllerName}::class);\n";
//
//        $apiRoutesPath = base_path('routes/api.php');
//        if (File::exists($apiRoutesPath)) {
//            File::append($apiRoutesPath, $routes);
//            $this->info("📄 Routes added to api.php");
//        } else {
//            $this->warn("⚠️  api.php not found. Please add routes manually:");
//            $this->line($routes);
//        }
//    }

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

        // Fallback to default stubs
        return $this->getDefaultStub($type);
    }

    protected function getDefaultStub($type)
    {
        $stubs = [
            'migration' => '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create(\'{{ table }}\', function (Blueprint $table) {
            $table->id();
            $table->string(\'title\');
            $table->text(\'description\')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists(\'{{ table }}\');
    }
};',

            'model' => '<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {{ class }} extends Model
{
    use HasFactory;

    protected $table = \'{{ table }}\';

    protected $fillable = [
        \'title\',
        \'description\',
    ];

    protected $casts = [
        \'created_at\' => \'datetime\',
        \'updated_at\' => \'datetime\',
    ];
}',

            'controller' => '<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\{{ request }};
use App\Services\{{ service }};
use Illuminate\Http\JsonResponse;

class {{ class }} extends Controller
{
    protected ${{ variable }}Service;

    public function __construct({{ service }} ${{ variable }}Service)
    {
        $this->{{ variable }}Service = ${{ variable }}Service;
    }

    /**
     * Display a listing of {{ resource }}.
     */
    public function index(): JsonResponse
    {
        try {
            ${{ variable }}s = $this->{{ variable }}Service->getAll();

            return response()->json([
                \'success\' => true,
                \'message\' => \'{{ class }}s retrieved successfully\',
                \'data\' => ${{ variable }}s
            ]);
        } catch (\Exception $e) {
            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to retrieve {{ resource }}\',
                \'error\' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created {{ variable }} in storage.
     */
    public function store({{ request }} $request): JsonResponse
    {
        try {
            ${{ variable }} = $this->{{ variable }}Service->create($request->validated());

            return response()->json([
                \'success\' => true,
                \'message\' => \'{{ class }} created successfully\',
                \'data\' => ${{ variable }}
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to create {{ variable }}\',
                \'error\' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified {{ variable }}.
     */
    public function show(int $id): JsonResponse
    {
        try {
            ${{ variable }} = $this->{{ variable }}Service->getById($id);

            return response()->json([
                \'success\' => true,
                \'message\' => \'{{ class }} retrieved successfully\',
                \'data\' => ${{ variable }}
            ]);
        } catch (\Exception $e) {
            return response()->json([
                \'success\' => false,
                \'message\' => \'{{ class }} not found\',
                \'error\' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Update the specified {{ variable }} in storage.
     */
    public function update({{ request }} $request, int $id): JsonResponse
    {
        try {
            ${{ variable }} = $this->{{ variable }}Service->update($id, $request->validated());

            return response()->json([
                \'success\' => true,
                \'message\' => \'{{ class }} updated successfully\',
                \'data\' => ${{ variable }}
            ]);
        } catch (\Exception $e) {
            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to update {{ variable }}\',
                \'error\' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified {{ variable }} from storage.
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->{{ variable }}Service->delete($id);

            return response()->json([
                \'success\' => true,
                \'message\' => \'{{ class }} deleted successfully\'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                \'success\' => false,
                \'message\' => \'Failed to delete {{ variable }}\',
                \'error\' => $e->getMessage()
            ], 500);
        }
    }
}',

            'service' => '<?php

namespace App\Services;

use App\Models\{{ model }};
use Illuminate\Database\Eloquent\Collection;

class {{ class }}
{
    protected ${{ variable }};

    public function __construct({{ model }} ${{ variable }})
    {
        $this->{{ variable }} = ${{ variable }};
    }

    /**
     * Get all {{ variable }}s
     */
    public function getAll(): Collection
    {
        return $this->{{ variable }}->orderBy(\'created_at\', \'desc\')->get();
    }

    /**
     * Get {{ variable }} by ID
     */
    public function getById(int $id): {{ model }}
    {
        return $this->{{ variable }}->findOrFail($id);
    }

    /**
     * Create new {{ variable }}
     */
    public function create(array $data): {{ model }}
    {
        return $this->{{ variable }}->create($data);
    }

    /**
     * Update {{ variable }}
     */
    public function update(int $id, array $data): {{ model }}
    {
        ${{ variable }} = $this->getById($id);
        ${{ variable }}->update($data);
        return ${{ variable }}->fresh();
    }

    /**
     * Delete {{ variable }}
     */
    public function delete(int $id): bool
    {
        ${{ variable }} = $this->getById($id);
        return ${{ variable }}->delete();
    }
}',

            'request' => '<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class {{ class }} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $rules = [
            \'title\' => \'required|string|max:255\',
            \'description\' => \'nullable|string\',
        ];

        // For update requests, make fields optional
        if ($this->getMethod() === \'PUT\' || $this->getMethod() === \'PATCH\') {
            $rules[\'title\'] = \'sometimes|required|string|max:255\';
        }

        return $rules;
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            \'title.required\' => \'The title field is required.\',
            \'title.string\' => \'The title must be a string.\',
            \'title.max\' => \'The title may not be greater than 255 characters.\',
            \'description.string\' => \'The description must be a string.\',
        ];
    }
}',

            'seeder' => '<?php

namespace Database\Seeders;

use App\Models\{{ model }};
use Illuminate\Database\Seeder;

class {{ class }} extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            [
                \'title\' => \'Sample Title 1\',
                \'description\' => \'This is a sample description for the first item.\',
                \'created_at\' => now(),
                \'updated_at\' => now(),
            ],
            [
                \'title\' => \'Sample Title 2\',
                \'description\' => \'This is a sample description for the second item.\',
                \'created_at\' => now(),
                \'updated_at\' => now(),
            ],
            [
                \'title\' => \'Sample Title 3\',
                \'description\' => \'This is a sample description for the third item.\',
                \'created_at\' => now(),
                \'updated_at\' => now(),
            ],
        ];

        {{ model }}::insert($data);
    }
}'
        ];

        return $stubs[$type] ?? '';
    }

    protected function ensureDirectoryExists($directory)
    {
        if (!File::exists($directory)) {
            $this->info("📁 Creating directory: {$directory}");
            File::makeDirectory($directory, 0755, true);
        }
    }
}
