<?php
namespace App;

use Symfony\Component\Console\Application as ApplicationBase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends ApplicationBase
{
    /**
     * The output from the previous command.
     *
     * @var \Symfony\Component\Console\Output\BufferedOutput
     */
    protected $lastOutput;

    /**
     * 应用创建的命令
     *
     * @var array
     */
    protected $commands = [
    ];

    /**
     * Class Constructor.
     *
     * Initialize the Pails console application.
     */
    public function __construct()
    {
        parent::__construct('JasonHorse', '1.0.0');

        // Pails commands
        $this->resolveCommands($this->commands);
    }

    /**
     * Runs the current application.
     *
     * @param InputInterface  $input  An Input instance
     * @param OutputInterface $output An Output instance
     *
     * @return int 0 if everything went fine, or an error code
     */
    public function doRun(InputInterface $input, OutputInterface $output)
    {
        // always show the version information except when the user invokes the help
        // command as that already does it
        if (false === $input->hasParameterOption(['--help', '-h']) && null !== $input->getFirstArgument()) {
            $output->writeln($this->getLongVersion());
            $output->writeln('');
        }

        return parent::doRun($input, $output);
    }

    /**
     * Get the default input definitions for the applications.
     *
     * This is used to add the --env option to every available command.
     *
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    protected function getDefaultInputDefinition()
    {
        $definition = parent::getDefaultInputDefinition();

        $definition->addOption($this->getEnvironmentOption());

        return $definition;
    }

    /**
     * Get the global environment option for the definition.
     *
     * @return \Symfony\Component\Console\Input\InputOption
     */
    protected function getEnvironmentOption()
    {
        $message = 'The environment the command should run under.';

        return new InputOption('--env', null, InputOption::VALUE_OPTIONAL, $message, 'development');
    }

    /**
     * Run an console command by name.
     *
     * @param string $command
     * @param array  $parameters
     *
     * @return int
     */
    public function call($command, array $parameters = [])
    {
        array_unshift($parameters, $command);

        $this->lastOutput = new BufferedOutput;

        $this->setCatchExceptions(false);

        $result = $this->run(new ArrayInput($parameters), $this->lastOutput);

        $this->setCatchExceptions(true);

        return $result;
    }

    /**
     * @return $this
     */
    public function init()
    {
        // load from Application.php
        $this->resolveCommands($this->commands);

        return $this;
    }

    /**
     *
     */
    public function boot()
    {
        $appPath = __DIR__;
        $commandsPath = $appPath . DIRECTORY_SEPARATOR . 'Commands';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($commandsPath), \RecursiveIteratorIterator::SELF_FIRST); 
        foreach ($iterator as $item) { 
            if (substr($item, -11) ==  'Command.php') { 
                $className = str_replace([$appPath, '.php'], ['App', ''], $item);
                $className = str_replace(DIRECTORY_SEPARATOR, '\\', $className); 
                $this->resolve($className);
            } 
        }
        return $this;
    }

    /**
     * @return mixed
     */
    public function handle()
    {
        $result = $this->run();

        return $result;
    }

    /**
     * Get the output for the last run command.
     *
     * @return string
     */
    public function output()
    {
        return $this->lastOutput ? $this->lastOutput->fetch() : '';
    }

    /**
     * Add a command, resolving through the application. 通过DI的自动注入功能，注入DI和事件管理器
     *
     * @param string $command
     *
     * @return \Symfony\Component\Console\Command\Command
     */
    public function resolve($command)
    {
        if (!class_exists($command)) {
           throw new \Exception("class $command not exists");
        }
        $commandInstance = new $command();
        return $this->add($commandInstance);
    }

    /**
     * Resolve an array of commands through the application.
     *
     * @param array|mixed $commands
     *
     * @return $this
     */
    public function resolveCommands($commands)
    {
        $commands = is_array($commands) ? $commands : func_get_args();

        foreach ($commands as $command) {
            $this->resolve($command);
        }

        return $this;
    }
}
