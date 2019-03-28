<?php

namespace Arrilot\BitrixTinker\Console;

use Arrilot\BitrixTinker\ClassAliasAutoloader;
use Psy\Configuration;
use Psy\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TinkerCommand extends Command
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this->setName('tinker')
            ->setDescription('Interact with your application')
            ->addArgument(
                'include',
                InputArgument::IS_ARRAY,
                'Include file(s) before starting tinker'
            );;
    }

    /**
     * Executes the current command.
     *
     * @param InputInterface $input An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code.
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getApplication()->setCatchExceptions(false);
        $config = new Configuration([
            'updateCheck' => 'never',
            'errorLoggingLevel' => E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_USER_WARNING & ~E_USER_NOTICE & ~E_COMPILE_WARNING & ~E_DEPRECATED,
        ]);
        $config->getPresenter()->addCasters($this->getCasters());
        $shell = new Shell($config);
        $includes = $input->getArgument('include');
        $shell->setIncludes($includes);

        $path = $this->vendorPath().DIRECTORY_SEPARATOR.'composer/autoload_classmap.php';
        $loader = ClassAliasAutoloader::register($shell, $path);
        try {
            $shell->run();
        } finally {
            $loader->unregister();
        }
    }

    /**
     * @return bool|string
     */
    protected function vendorPath()
    {
        return dirname(dirname(dirname(dirname(__DIR__))));
    }

    /**
     * Get an array of Laravel tailored casters.
     *
     * @return array
     */
    protected function getCasters()
    {
        $casters = [];
        if (class_exists('Illuminate\Support\Collection')) {
            $casters['Illuminate\Support\Collection'] = 'Laravel\Tinker\TinkerCaster::castCollection';
        }
        if (class_exists('Illuminate\Database\Eloquent\Model')) {
            $casters['Illuminate\Database\Eloquent\Model'] = 'Laravel\Tinker\TinkerCaster::castModel';
        }

        return $casters;
    }
}