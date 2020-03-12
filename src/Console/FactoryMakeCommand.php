<?php

namespace ArtemSchander\L5Modular\Console;

use ArtemSchander\L5Modular\Traits\ConfiguresFolder;
use ArtemSchander\L5Modular\Traits\HasModuleOption;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Database\Console\Factories\FactoryMakeCommand as BaseFactoryCommand;

class FactoryMakeCommand extends BaseFactoryCommand
{
    use ConfiguresFolder, HasModuleOption;

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:module:factory';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new model factory in a module';

    /**
     * Execute the console command.
     *
     * @return bool|null
     */
    public function handle()
    {
        $this->initModuleOption();

        return $this->module ? parent::handle() : false;
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getPath($name)
    {
        $name = str_replace(
            ['\\', '/'],
            '',
            $this->argument('name')
        );

        return $this->laravel['path'] . '/Modules/' . $this->module . '/' . $this->getConfiguredFolder('factories') . '/' . $name . '.php';
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        $options = parent::getOptions();

        $options[] = ['module', null, InputOption::VALUE_OPTIONAL, 'Generate a factory in a certain module'];

        return $options;
    }
}