<?php

namespace ErikTrapman\Bundle\ReleaseCommandBundle\Command;

use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\ProcessBuilder;

class ReleaseCommand extends ContainerAwareCommand
{

    /**
     * 
     */
    protected function configure()
    {
        $this
            ->setName('eriktrapman:release')
            ->addArgument('target-dir', InputArgument::REQUIRED)
            ->setDescription("Prepares a release of a standard Symfony2-project by copying all files needed to a given path")
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $start = microtime(1); // TODO use StopWatch
        $targetDir = $input->getArgument('target-dir');
        $kernel = $this->getContainer()->get('kernel');
        $rootDir = $kernel->getRootDir();

        $f = new ExecutableFinder();
        $git = $f->find('git');
        if (!$git) {
            throw new RuntimeException("This command cannot run without GIT.");
        }
        $tarname = uniqid().'.tar';
        $pb = ProcessBuilder::create(array($git, 'archive', '-o', $tarname, 'HEAD'));
        $p = $pb->getProcess();
        $p->run(function ($type, $buffer) {
                if ('err' === $type) {
                    $output->write('ERR > '.$buffer);
                } else {
                    $output->write('OUT > '.$buffer);
                }
            });
        if ($errorOutput = $p->getErrorOutput()) {
            $output->writeln(nl2br($errorOuput, false));
        }
        $pb = ProcessBuilder::create(array('tar', '-xf', $tarname, '-C', $targetDir));
        $p = $pb->getProcess();
        $p->run();

        $finder = new Finder();
        $finder->in($rootDir.'/../vendor/')->ignoreDotFiles(true)->ignoreVCS(true);
        $filesystem = new Filesystem();
        $filesystem->mirror($rootDir.'/../vendor/', $targetDir.'/vendor/', $finder, array('copy_on_windows' => true));

        $this->injectApplicationVitals($rootDir, $targetDir);
        $filesystem->remove($rootDir.'/../'.$tarname);
        $d = microtime(1) - $start;
        $output->writeln('Released in '.$d.' microseconds');
        return;
    }

    private function injectApplicationVitals($rootDir, $targetDir)
    {
        $ignoredFiles = file($rootDir.'/../.gitignore');
        $fileSystem = new Filesystem();
        foreach ($ignoredFiles as $ignoreFile) {
            $ignoreFile = trim($ignoreFile);
            if (0 == strlen($ignoreFile) || false !== strpos($ignoreFile, '#')) {
                continue;
            }
            if (false !== strpos($ignoreFile, 'app/bootstrap')) {
                $fileSystem->copy($rootDir.'/bootstrap.php.cache', $targetDir.'/app/bootstrap.php.cache');
            }
            if (false !== strpos($ignoreFile, 'app/config/parameters')) {
                if ($fileSystem->exists($rootDir.'/config/parameters.yml')) {
                    $fileSystem->copy($rootDir.'/config/parameters.yml', $targetDir.'/app/config/parameters.yml');
                }
                if ($fileSystem->exists($rootDir.'/config/parameters.ini')) {
                    $fileSystem->copy($rootDir.'/config/parameters.ini', $targetDir.'/app/config/parameters.ini');
                }
            }
        }
    }
}