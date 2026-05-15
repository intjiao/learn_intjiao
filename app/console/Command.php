<?php
namespace think;

use Symfony\Component\Console\Application as SymfonyConsole;

class Console extends SymfonyConsole
{
    public function __construct()
    {
        parent::__construct('ThinkPHP', '6.0');
        $this->addCommands([
            new command\Optimize(),
            new command\Clear(),
        ]);
    }

    public function run(): int
    {
        $this->doRun();
        return 0;
    }
}
