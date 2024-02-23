<?php

namespace UEMC\Core\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\RequestStack;

class UemcCleanSessions extends Command
{
    protected static $defaultName = 'uemc:clean:session';
    protected static $defaultDescription = 'Limpia todas las sesiones almacenadas';

    private $session;

    public function __construct(RequestStack $requestStack) {
        parent::__construct();
    }

    protected function configure(): void
    {
        // No es necesario agregar argumentos ni opciones en este caso
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $session = $this->requestStack->getSession();
        // Invalidar la sesión
        $session->invalidate();

        $io->success('Éxito - Todas las sesiones eliminadas');

        return Command::SUCCESS;
    }
}
