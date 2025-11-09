<?php
declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Command;

use DateTimeImmutable;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\TransportNamesStamp;

// ... existing code ...
#[AsCommand(name: 'wrap:notificator:diagnose', description: 'Diagnose Mercure and Messenger integrations and print a JSON report')]
final class DiagnoseCommand extends Command
{
    public function __construct(
        private readonly ?HubInterface $hub = null,
        private readonly ?MessageBusInterface $bus = null,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('topic', null, InputOption::VALUE_REQUIRED, 'Topic to publish the Mercure test update to', 'wrap_notificator/diag')
            ->addOption('mercure-only', null, InputOption::VALUE_NONE, 'Run only Mercure diagnostics')
            ->addOption('messenger-only', null, InputOption::VALUE_NONE, 'Run only Messenger diagnostics')
            ->addOption('async', null, InputOption::VALUE_NONE, 'Dispatch the Mercure publication via Messenger if available (simulation)')
            ->addOption('delay', null, InputOption::VALUE_REQUIRED, 'Delay in seconds when using --async (Messenger)')
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output machine-readable JSON only (no pretty output)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mercureOnly = (bool) $input->getOption('mercure-only');
        $messengerOnly = (bool) $input->getOption('messenger-only');

        $attemptMercure = !$messengerOnly; // default: both
        $attemptMessenger = !$mercureOnly; // default: both

        $report = [
            'ts' => (new DateTimeImmutable())->format(DATE_ATOM),
            'mercure' => [
                'available' => $this->hub !== null,
                'published' => false,
                'id' => null,
                'error' => null,
                'topic' => null,
            ],
            'messenger' => [
                'available' => $this->bus !== null,
                'dispatched' => false,
                'transportNames' => null,
                'handled' => false,
                'handlers' => [],
                'error' => null,
            ],
        ];

        $exitOk = true;

        // Mercure diagnostic
        if ($attemptMercure) {
            $report['mercure']['topic'] = (string) $input->getOption('topic');
            if ($this->hub === null) {
                $report['mercure']['error'] = 'Mercure HubInterface service not available';
                $exitOk = false;
            } else {
                try {
                    $payload = [
                        'type' => 'wrap_notificator.diagnostic',
                        'message' => 'Mercure diagnostic ping',
                        'time' => (new DateTimeImmutable())->format('c'),
                    ];
                    $update = new Update(
                        $report['mercure']['topic'],
                        json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    );
                    $id = $this->hub->publish($update);
                    $report['mercure']['published'] = true;
                    if ($id !== null) {
                        $report['mercure']['id'] = $id;
                    }
                } catch (\Throwable $e) {
                    $report['mercure']['error'] = $e->getMessage();
                    $exitOk = false;
                }
            }
        }

        // Messenger diagnostic
        if ($attemptMessenger) {
            if ($this->bus === null) {
                $report['messenger']['error'] = 'MessageBusInterface service not available';
                $exitOk = false;
            } else {
                try {
                    $message = new DiagPing(); // classe dédiée (plus d’anonyme)
                    $stamps = $this->buildStampsFromInput($input);
                    /** @var Envelope $envelope */
                    $envelope = $this->bus->dispatch($message, $stamps);
                    $report['messenger']['dispatched'] = true;
                    $tnStamp = $envelope->last(TransportNamesStamp::class);
                    if ($tnStamp instanceof TransportNamesStamp) {
                        $report['messenger']['transportNames'] = $tnStamp->getTransportNames();
                    }
                    $handledStamp = $envelope->last(HandledStamp::class);
                    if ($handledStamp instanceof HandledStamp) {
                        $report['messenger']['handled'] = true;
                        $report['messenger']['handlers'][] = $handledStamp->getHandlerName();
                    }
                } catch (\Throwable $e) {
                    $report['messenger']['error'] = $e->getMessage();
                    $exitOk = false;
                }
            }
        }

        $json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{}';
        }

        $onlyJson = (bool) $input->getOption('json');
        if ($onlyJson) {
            $output->writeln($json);
            return $exitOk ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('Wrap Notificator — Diagnostic Mercure & Messenger');

        // Mercure pretty section
        $io->section('Mercure');
        if ($report['mercure']['available']) {
            $status = $report['mercure']['published'] ? 'OK' : 'NOT PUBLISHED';
            $icon = $report['mercure']['published'] ? '✔' : '✖';
            $io->writeln(sprintf('%s Hub available', $report['mercure']['available'] ? '✔' : '✖'));
            $io->writeln(sprintf('%s Published: %s', $icon, $status));
            if (!empty($report['mercure']['topic'])) {
                $io->writeln(sprintf('• Topic: %s', $report['mercure']['topic']));
            }
            if (!empty($report['mercure']['id'])) {
                $io->writeln(sprintf('• Publication id: %s', (string) $report['mercure']['id']));
            }
            if (!empty($report['mercure']['error'])) {
                $io->error((string) $report['mercure']['error']);
            }
        } else {
            $io->warning('Mercure HubInterface not available');
        }

        // Messenger pretty section
        $io->section('Messenger');
        if ($report['messenger']['available']) {
            $io->writeln(sprintf('%s Bus available', $report['messenger']['available'] ? '✔' : '✖'));
            $io->writeln(sprintf('%s Dispatched: %s', $report['messenger']['dispatched'] ? '✔' : '✖', $report['messenger']['dispatched'] ? 'YES' : 'NO'));
            if (!empty($report['messenger']['transportNames'])) {
                $tn = is_array($report['messenger']['transportNames']) ? implode(', ', $report['messenger']['transportNames']) : (string) $report['messenger']['transportNames'];
                $io->writeln(sprintf('• Transports: %s', $tn));
            }
            $io->writeln(sprintf('%s Handled by handler: %s', $report['messenger']['handled'] ? '✔' : '✖', $report['messenger']['handled'] ? 'YES' : 'NO'));
            if (!empty($report['messenger']['handlers'])) {
                $io->listing(array_map('strval', $report['messenger']['handlers']));
            }
            if (!empty($report['messenger']['error'])) {
                $io->error((string) $report['messenger']['error']);
            }
        } else {
            $io->warning('MessageBusInterface not available');
        }

        $io->newLine();
        $io->writeln('<info>JSON report</info>');
        $output->writeln($json);

        return $exitOk ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * Extrait: construit les stamps Messenger à partir des options CLI.
     * @return array<int, object>
     */
    private function buildStampsFromInput(InputInterface $input): array
    {
        if (!(bool) $input->getOption('async')) {
            return [];
        }
        $stamps = [];
        $delay = $input->getOption('delay');
        if (is_numeric($delay)) {
            $stamps[] = new DelayStamp(((int) $delay) * 1000);
        }
        return $stamps;
    }
}
// ... existing code ...
final class DiagPing
{
    public function __toString(): string
    {
        return 'WrapNotificator\\DiagPing';
    }
}
