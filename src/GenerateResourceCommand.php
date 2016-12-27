<?php
namespace Hizbul\Generators;

use Illuminate\Console\AppNamespaceDetectorTrait;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class GenerateResourceCommand extends Command
{
    use AppNamespaceDetectorTrait;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = 'generate:all 
                      {--controller=}
                      {--model=} 
                      {--view=} 
                      {--migration=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate Model, Controller, View and migration in a single artisan command';

    /**
     * The filesystem instance.
     *
     * @var Filesystem
     */
    protected $files;


    /**
     * @var Composer
     */
    private $composer;

    /**
     * Create a new command instance.
     *
     * @param Filesystem $files
     * @param Composer $composer
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
        $this->composer = app()['composer'];
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function fire()
    {
        if($this->option('controller')) {
            $this->makeController();
        }

        if($this->option('model')) {
            $this->makeModel();
        }

        if($this->option('view')) {
            $this->makeView();
        }

        if($this->option('migration')) {
            $this->makeMigration();
        }
    }

    /**
     * Generate the desired controller.
     */
    protected function makeController()
    {
        $name = $this->getControllerName();

        if ($this->files->exists($path = $this->getControllerPath($name))) {
            return $this->error( $name . ' already exists!');
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileControllerStub());

        $this->info($name . ' Controller created successfully.');
    }

    /**
     * Generate the desired migration.
     */
    protected function makeMigration()
    {
        $name = $this->option('migration');
        if ($this->files->exists($path = $this->getMigrationPath($name))) {
            return $this->error( $name . ' already exists!');
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileMigrationStub());

        $this->info('Migration created successfully.');

        $this->composer->dumpAutoloads();
    }

    /**
     * Generate an Eloquent model, if the user wishes.
     */
    protected function makeModel()
    {
        $modelPath = $this->getModelPath($this->getModelName());

        if ($this->option('model') && !$this->files->exists($modelPath)) {
            $this->call('make:model', [
                'name' => $this->getModelName()
            ]);
        }
    }

    /**
     * Generate the view, if the user wishes.
     */
    
    protected function makeView()
    {
        $viewFileName = $this->option('view');
        if ($this->files->exists($path = $this->getViewPath($viewFileName))) {
            return $this->error($viewFileName . 'view already exists!');
        }

        $this->makeDirectory($path);

        $this->files->put($path, $this->compileViewStub());

        $this->info($viewFileName . ' view created successfully.');
    }
    /**
     * Build the directory for the class if necessary.
     *
     * @param  string $path
     * @return string
     */
    protected function makeDirectory($path)
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0777, true, true);
        }
    }

    /**
     * Get the path to where we should store the migration.
     *
     * @param  string $name
     * @return string
     */
    protected function getMigrationPath($name)
    {
        return base_path() . '/database/migrations/' . date('Y_m_d_His') . '_' . $name . '.php';
    }

    /**
     * Get the destination class path.
     *
     * @param  string $name
     * @return string
     */
    protected function getModelPath($name)
    {
        $name = str_replace($this->getAppNamespace(), '', $name);

        return $this->laravel['path'] . '/' . str_replace('\\', '/', $name) . '.php';
    }
    
    public function getControllerPath($name)
    {
        $name = str_replace($this->getAppNamespace(), '', $name);

        return $this->laravel['path'] . '/Http/Controllers/' . str_replace('\\', '/', $name) . '.php';
    }
    
    public function getViewPath($name)
    {

        return base_path() . '/resources/views/' . $name . '.blade.php';
    }
    /**
     * Compile the migration stub.
     *
     * @return string
     */
    protected function compileMigrationStub()
    {
        $stub = $this->files->get(__DIR__ . '/stubs/migration.stub');

        $this->replaceClassName($stub, 'migration')
            ->replaceTableName($stub);

        return $stub;
    }

    /**
     * Compile the view stub
     * @return string
     */
    protected function compileViewStub()
    {
        $stub = $this->files->get(__DIR__ . '/stubs/view.stub');

        return $stub;
    }

    /**
     * Compile the controller stub
     * @return string
     */
    protected function compileControllerStub()
    {
        $stub = $this->files->get(__DIR__ . '/stubs/controller.stub');
        $this->replaceClassName($stub, 'controller');

        return $stub;
    }

    /**
     * Replace the class name in the stub.
     *
     * @param  string $stub
     * @param  string $type
     * @return $this
     */
    protected function replaceClassName(&$stub, $type)
    {
        $className = ucwords(camel_case($this->option($type)));

        $stub = str_replace('{{class}}', $className, $stub);

        return $this;
    }

    /**
     * Replace the table name in the stub.
     *
     * @param  string $stub
     * @return $this
     */
    protected function replaceTableName(&$stub)
    {
        $table = $this->getTableName();
        $stub = str_replace('{{table}}', $table, $stub);

        return $this;
    }

    /**
     * Get the class name for the Eloquent model generator.
     *
     * @return string
     */
    protected function getModelName()
    {
        return ucwords(str_singular(camel_case($this->option('model'))));
    }

    /**
     * Get the class name for the Controller.
     * @return string
     */
    protected function getControllerName()
    {
        if(strpos(strtolower($this->option('controller')), 'controller') > 0)
        {
            return ucwords(camel_case($this->option('controller')));
        }

        return ucwords(camel_case($this->option('controller'))).'Controller';
    }

    /**
     * @return mixed|string
     */
    public function getTableName()
    {
        $migrationName = $this->option('migration');
        preg_match('/create_(.*)_table/', strtolower($migrationName), $table);

        return  $table[1];
    }
}