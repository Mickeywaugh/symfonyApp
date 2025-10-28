<?php

namespace App\Command;

use App\Service\Logger;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(name: 'app:basecmd', description: 'base class for daemon process')]
class BaseCommand extends Command
{
  protected $pidFile = 'var/run/basecmd.pid';
  protected $logFileName = 'basecmd';
  protected $io;
  protected $verbose = false;
  public function __construct()
  {
    parent::__construct();
    date_default_timezone_set('Asia/Shanghai');
  }

  protected function configure(): void
  {
    $this
      ->addArgument('action', InputArgument::REQUIRED, 'Action: start|stop|restart')
      ->addOption('startDate', 'd', InputOption::VALUE_OPTIONAL, 'An option parameter')
      ->addOption('startId', 'i', InputOption::VALUE_OPTIONAL, 'An option parameter')
    ;
  }

  protected function initialize(InputInterface $input, OutputInterface $output): void
  {
    $this->verbose = $input->getOption('verbose');
    $this->io = new SymfonyStyle($input, $output);
  }

  protected function execute(InputInterface $input, OutputInterface $output): int
  {
    $action = $input->getArgument('action');
    $options = ["startDate" => $input->getOption('startDate'), "startId" => $input->getOption('startId')];
    $this->note("options is " . json_encode($options));
    switch ($action) {
      case 'start':
        $this->startDaemon($options);
        break;
      case 'stop':
        $this->stopDaemon();
        break;
      case 'restart':
        $this->restartDaemon($options);
        break;
      default:
        if (method_exists($this, $action)) {
          $this->{$action}($options);
        } else {
          $this->error('Invalid action. Use "start|stop|restart".');
          return Command::FAILURE;
        }
    }

    return Command::SUCCESS;
  }

  protected function startDaemon(array $options): void
  {
    if ($this->isRunning()) {
      $this->warning('Daemon is already running.');
      return;
    }

    $pid = pcntl_fork();

    if ($pid == -1) {
      $this->error('Could not fork.');
      exit(1);
    } elseif ($pid) {
      // Parent process
      file_put_contents($this->pidFile, $pid);
      $this->success('Daemon started with PID: ' . $pid);
    } else {
      // Child process
      // Detach from the parent process group
      posix_setsid();
      $this->process($options);
    }
  }

  protected function stopDaemon(): void
  {
    if (!$this->isRunning()) {
      $this->warning('Daemon is not running.');
      return;
    }

    $pid = (int)file_get_contents($this->pidFile);
    posix_kill($pid, SIGTERM);

    // Wait for the process to terminate
    while (posix_kill($pid, 0)) {
      usleep(100000); // Sleep for 100ms
    }

    $fileSystem = new Filesystem();
    $fileSystem->remove($this->pidFile);
    $this->success('Daemon stopped.');
  }

  public function isRunning(): bool
  {
    if (!file_exists($this->pidFile)) {
      return false;
    }

    $pid = (int)file_get_contents($this->pidFile);
    return posix_kill($pid, 0);
  }

  protected function restartDaemon(array $options): void
  {
    if ($this->isRunning()) {
      $this->stopDaemon();
    }
    $this->startDaemon($options);
  }

  protected function note($message)
  {
    if (!$message) {
      return;
    }
    $date = date('Y-m-d H:i:s');
    $this->verbose && $this->io->note(sprintf('%s %s', $date, $message));
    Logger::log($message, logFile: $this->logFileName);
  }

  protected function info($message)
  {
    if (!$message) {
      return;
    }
    $date = date('Y-m-d H:i:s');
    $this->verbose && $this->io->info(sprintf('%s %s', $date, $message));
    Logger::log($message, logFile: $this->logFileName);
  }

  protected function warning($message)
  {
    if (!$message) {
      return;
    }
    $date = date('Y-m-d H:i:s');
    $this->verbose && $this->io->warning(sprintf('%s %s', $date, $message));
    Logger::log($message, logFile: $this->logFileName);
  }

  protected function error($message, string $logName = "")
  {
    if (!$message) {
      return;
    }
    $logName = $logName ?: $this->logFileName;
    $date = date('Y-m-d H:i:s');
    $this->verbose && $this->io->error(sprintf('%s %s', $date, $message));
    Logger::error($message, logFile: $this->logFileName);
  }

  protected function success($message, string $logName = "")
  {
    if (!$message) {
      return;
    }
    $logName = $logName ?: $this->logFileName;
    $date = date('Y-m-d H:i:s');
    $this->verbose && $this->io->success(sprintf('%s %s', $date, $message));
    Logger::log($message, logFile: $this->logFileName);
  }

  /**
   * Manage your daemon logic here
   */
  protected function process($options = null) {}
}
