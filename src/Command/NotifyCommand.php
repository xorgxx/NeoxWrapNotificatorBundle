<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Command;

use Neox\WrapNotificatorBundle\Notification\DeliveryContext;
use Neox\WrapNotificatorBundle\Notification\DeliveryStatus;
use Neox\WrapNotificatorBundle\Service\NotifierFacade;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'notify:send', description: 'Send a notification through a selected channel and print the DeliveryStatus JSON')]
final class NotifyCommand extends Command
{
    public function __construct(private readonly NotifierFacade $facade)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('channel', null, InputOption::VALUE_REQUIRED, 'Channel: email|sms|chat|browser|push|desktop')
            // email
            ->addOption('to', null, InputOption::VALUE_OPTIONAL, 'Recipient (email or phone depending on channel)')
            ->addOption('subject', null, InputOption::VALUE_OPTIONAL, 'Email or chat subject')
            ->addOption('html', null, InputOption::VALUE_OPTIONAL, 'HTML content (email)')
            ->addOption('text', null, InputOption::VALUE_OPTIONAL, 'Text content (email/sms/chat)')
            // chat
            ->addOption('transport', null, InputOption::VALUE_OPTIONAL, 'Chat transport: slack|telegram')
            ->addOption('opt', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Chat option key:value (repeatable)')
            // browser
            ->addOption('topic', null, InputOption::VALUE_OPTIONAL, 'Mercure topic for browser notifications')
            ->addOption('data', null, InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'Data key:value (repeatable)')
            // push/desktop
            ->addOption('subscription-file', null, InputOption::VALUE_OPTIONAL, 'Path to web push subscription JSON file')
            ->addOption('ttl', null, InputOption::VALUE_OPTIONAL, 'TTL for web push', null)
            // correlation & idempotence
            ->addOption('correlation-id', null, InputOption::VALUE_OPTIONAL, 'Correlation ID (UUID v4). If omitted, it will be generated')
            ->addOption('dedupe-key', null, InputOption::VALUE_OPTIONAL, 'Stable deduplication key (e.g., sha1(event:user:window))')
            ->addOption('dedupe-ttl', null, InputOption::VALUE_OPTIONAL, 'Deduplication TTL in seconds (default 600)', '600')
            // deferral (async only)
            ->addOption('send-at', null, InputOption::VALUE_OPTIONAL, 'Schedule send at date-time (e.g., 2025-12-01T10:30:00+01:00) — async only')
            ->addOption('in', null, InputOption::VALUE_OPTIONAL, 'Schedule send in delay (e.g., PT10M, 15m, 2h) — async only')
            // transport override
            ->addOption('via-transport', null, InputOption::VALUE_OPTIONAL, 'Force Messenger transport for this notification (e.g., sync | asyncRabbitMq)')
            // output
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output machine-readable JSON only (no pretty output)')
        ;

        $this->setHelp(<<<'HELP'
La commande permet d'envoyer une notification sur plusieurs canaux et affiche un DeliveryStatus.

Utilisation basique:
  php bin/console notify:send --channel=email --to="user@example.com" --subject="Bienvenue" --html='<h1>Bonjour</h1>'

Canaux et options:
  --channel=email|sms|chat|browser|push|desktop
  Email:    --to --subject --html|--text
  SMS:      --to --text
  Chat:     --transport=slack|telegram --text [--subject] --opt=key:value (répétable)
  Browser:  --topic --data=key:value (répétable)
  Push:     --subscription-file=path.json --data=key:value [--ttl=3600]
  Desktop:  alias de Push (mêmes options)

Corrélation & Idempotence:
  --correlation-id=UUIDv4
  --dedupe-key="ag.reminder:42:2025-12-01" --dedupe-ttl=900

Sorties:
  Par défaut, un rendu convivial est affiché puis le JSON du DeliveryStatus.
  Ajoutez --json pour n'imprimer que le JSON (scripts/CI).

Exemples:
  # Email HTML
  php bin/console notify:send --channel=email --to="user@example.com" --subject="Bienvenue" --html='<h1>Bonjour</h1>'

  # SMS simple
  php bin/console notify:send --channel=sms --to="+33600000000" --text="Hello"

  # Slack avec options
  php bin/console notify:send --channel=chat --transport=slack --text="Déploiement ok" --subject="Release" --opt=channel:ops --opt=iconEmoji::rocket:

  # Telegram avec parseMode
  php bin/console notify:send --channel=chat --transport=telegram --text="<b>Alerte</b>" --opt=chatId:123456 --opt=parseMode:HTML --opt=disableWebPagePreview:1

  # Browser (Mercure)
  php bin/console notify:send --channel=browser --topic='users:42' --data=type:notification --data=title:"Bienvenue"

  # Push (Web Push)
  php bin/console notify:send --channel=push --subscription-file=./sub.json --data=title:"Hello" --ttl=120

  # Déduplication 15 min
  php bin/console notify:send --channel=sms --to="+33600000000" --text="Rappel" --dedupe-key="reminder:user:42:2025-12-01" --dedupe-ttl=900
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $channel = (string) $input->getOption('channel');
        if ($channel === '') {
            $output->writeln('<error>--channel is required</error>');
            return Command::INVALID;
        }

        $status = match ($channel) {
            'email' => $this->handleEmail($input),
            'sms' => $this->handleSms($input),
            'chat' => $this->handleChat($input),
            'browser' => $this->handleBrowser($input),
            'push' => $this->handlePush($input),
            'desktop' => $this->handleDesktop($input),
            default => DeliveryStatus::failed($channel, 'Unknown channel'),
        };

        $json = json_encode($status->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{}';
        }

        $onlyJson = (bool) $input->getOption('json');
        if ($onlyJson) {
            $output->writeln($json);
            return in_array($status->status, [DeliveryStatus::STATUS_SENT, DeliveryStatus::STATUS_QUEUED], true) ? Command::SUCCESS : Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('Wrap Notificator — notify:send');

        // Summary
        $io->section('Résumé de la requête');
        $summary = [
            'Channel' => $channel,
        ];
        $to = $input->getOption('to');
        if (is_string($to) && $to !== '') {
            $summary['To'] = $to;
        }
        $subject = $input->getOption('subject');
        if (is_string($subject) && $subject !== '') {
            $summary['Subject'] = $subject;
        }
        $transport = $input->getOption('transport');
        if (is_string($transport) && $transport !== '') {
            $summary['Transport'] = $transport;
        }
        $topic = $input->getOption('topic');
        if (is_string($topic) && $topic !== '') {
            $summary['Topic'] = $topic;
        }
        $ttl = $input->getOption('ttl');
        if (is_numeric($ttl)) {
            $summary['TTL'] = (int) $ttl.'s';
        }
        $corr = $input->getOption('correlation-id');
        if (is_string($corr) && $corr !== '') {
            $summary['CorrelationId'] = $corr;
        }
        $dedupe = $input->getOption('dedupe-key');
        if (is_string($dedupe) && $dedupe !== '') {
            $summary['DedupeKey'] = $dedupe;
        }
        $dedupeTtl = $input->getOption('dedupe-ttl');
        if (is_numeric($dedupeTtl)) {
            $summary['Dedupe TTL'] = (int) $dedupeTtl.'s';
        }
        foreach ($summary as $k => $v) {
            $io->writeln(sprintf('• %s: %s', $k, (string) $v));
        }

        // Result
        $io->section('Résultat');
        $ok = in_array($status->status, [DeliveryStatus::STATUS_SENT, DeliveryStatus::STATUS_QUEUED], true);
        $icon = match ($status->status) {
            DeliveryStatus::STATUS_SENT => '✔',
            DeliveryStatus::STATUS_QUEUED => '➜',
            default => '✖',
        };
        $io->writeln(sprintf('%s Status: %s', $icon, strtoupper($status->status)));
        if ($status->id !== null) {
            $io->writeln(sprintf('• Id: %s', $status->id));
        }
        if ($status->message !== null) {
            if ($ok) {
                $io->note($status->message);
            } else {
                $io->error($status->message);
            }
        }
        if (!empty($status->metadata)) {
            $io->writeln('• Metadata:');
            $metaLines = [];
            foreach ($status->metadata as $k => $v) {
                $metaLines[] = sprintf('   - %s: %s', (string) $k, is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            $io->writeln($metaLines);
        }

        $io->newLine();
        $io->writeln('<info>JSON</info>');
        $output->writeln($json);

        return $ok ? Command::SUCCESS : Command::FAILURE;
    }

    private function buildContext(InputInterface $input): DeliveryContext
    {
        $corr = $input->getOption('correlation-id');
        $dedupe = $input->getOption('dedupe-key');
        $ttlOpt = $input->getOption('dedupe-ttl');
        $ttl = is_numeric($ttlOpt) ? (int) $ttlOpt : 600;

        $deferAt = $this->parseDeferral($input);
        $via = $input->getOption('via-transport');
        $viaTransport = is_string($via) && $via !== '' ? $via : null;

        if (is_string($dedupe) && $dedupe !== '') {
            return DeliveryContext::for($dedupe, $ttl, $deferAt, $viaTransport);
        }
        $corrId = is_string($corr) && $corr !== '' ? $corr : null;
        return DeliveryContext::create($corrId, null, null, $deferAt, $viaTransport);
    }

    private function parseDeferral(InputInterface $input): ?\DateTimeImmutable
    {
        $sendAt = $input->getOption('send-at');
        $in = $input->getOption('in');
        if (is_string($sendAt) && $sendAt !== '' && $in) {
            // both provided -> prefer send-at and ignore in
        }
        if (is_string($sendAt) && $sendAt !== '') {
            // Accept ISO8601 (DATE_ATOM) or common "Y-m-d H:i[:s]" (local timezone)
            $dt = \DateTimeImmutable::createFromFormat(DATE_ATOM, $sendAt) ?: new \DateTimeImmutable($sendAt);
            if ($dt instanceof \DateTimeImmutable && $dt > new \DateTimeImmutable('now')) {
                return $dt;
            }
            return null;
        }
        if (is_string($in) && $in !== '') {
            $seconds = $this->parseDurationToSeconds($in);
            if ($seconds !== null && $seconds > 0) {
                return (new \DateTimeImmutable('now'))->modify('+' . $seconds . ' seconds');
            }
        }
        return null;
    }

    private function parseDurationToSeconds(string $expr): ?int
    {
        $expr = trim($expr);
        // ISO8601 duration like PT10M, P1DT2H
        if ($expr !== '' && ($expr[0] === 'P' || $expr[0] === 'p')) {
            try {
                $interval = new \DateInterval(strtoupper($expr));
                $base = new \DateTimeImmutable('now');
                $end = $base->add($interval);
                return max(0, $end->getTimestamp() - $base->getTimestamp());
            } catch (\Throwable) {
                // fall through to human parsing
            }
        }
        // Human formats: 15m, 2h, 1d, 30s or composited like 1h30m
        $total = 0;
        $pattern = '/(\d+)\s*(d|h|m|s)/i';
        if (preg_match_all($pattern, $expr, $m, PREG_SET_ORDER)) {
            foreach ($m as $part) {
                $n = (int)$part[1];
                $u = strtolower($part[2]);
                $total += match ($u) {
                    'd' => $n * 86400,
                    'h' => $n * 3600,
                    'm' => $n * 60,
                    's' => $n,
                    default => 0,
                };
            }
            return $total > 0 ? $total : null;
        }
        // Plain seconds
        if (ctype_digit($expr)) {
            $v = (int)$expr;
            return $v > 0 ? $v : null;
        }
        return null;
    }

    private function handleEmail(InputInterface $input): DeliveryStatus
    {
        $to = (string) ($input->getOption('to') ?? '');
        $subject = (string) ($input->getOption('subject') ?? '');
        $html = $input->getOption('html');
        $text = $input->getOption('text');
        $isHtml = $html !== null || $text === null; // prefer html when provided
        $content = is_string($html) ? $html : (is_string($text) ? $text : '');
        if ($to === '' || $subject === '' || $content === '') {
            return DeliveryStatus::failed('email', 'Missing --to, --subject or --html/--text');
        }
        $ctx = $this->buildContext($input);
        return $this->facade->notifyEmail($subject, $content, $to, $isHtml, [], [], $ctx);
    }

    private function handleSms(InputInterface $input): DeliveryStatus
    {
        $to = (string) ($input->getOption('to') ?? '');
        $text = (string) ($input->getOption('text') ?? '');
        if ($to === '' || $text === '') {
            return DeliveryStatus::failed('sms', 'Missing --to or --text');
        }
        $ctx = $this->buildContext($input);
        return $this->facade->notifySms($text, $to, [], $ctx);
    }

    /**
     * @return array<string,mixed>
     */
    private function parseKeyValueArray(?array $pairs): array
    {
        $result = [];
        if ($pairs === null) {
            return $result;
        }
        foreach ($pairs as $pair) {
            if (!is_string($pair)) {
                continue;
            }
            $pos = strpos($pair, ':');
            if ($pos === false) {
                continue;
            }
            $key = substr($pair, 0, $pos);
            $value = substr($pair, $pos + 1);
            $result[$key] = $value;
        }
        return $result;
    }

    private function handleChat(InputInterface $input): DeliveryStatus
    {
        $transport = (string) ($input->getOption('transport') ?? '');
        $text = (string) ($input->getOption('text') ?? '');
        $subject = $input->getOption('subject');
        $opts = $this->parseKeyValueArray($input->getOption('opt'));
        if ($transport === '' || $text === '') {
            return DeliveryStatus::failed('chat', 'Missing --transport or --text');
        }
        $ctx = $this->buildContext($input);
        return $this->facade->notifyChat($transport, $text, is_string($subject) ? $subject : null, $opts, [], $ctx);
    }

    private function handleBrowser(InputInterface $input): DeliveryStatus
    {
        $topic = (string) ($input->getOption('topic') ?? '');
        $data = $this->parseKeyValueArray($input->getOption('data'));
        if ($topic === '') {
            return DeliveryStatus::failed('browser', 'Missing --topic');
        }
        $ctx = $this->buildContext($input);
        return $this->facade->notifyBrowser($topic, $data, [], $ctx);
    }

    /**
     * @return array{endpoint:string,keys:array{p256dh:string,auth:string}}|null
     */
    private function readSubscriptionFromFile(?string $file): ?array
    {
        if ($file === null || $file === '') {
            return null;
        }
        if (!is_readable($file)) {
            return null;
        }
        $json = file_get_contents($file);
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!is_array($data) || !isset($data['endpoint'], $data['keys']['p256dh'], $data['keys']['auth'])) {
            return null;
        }
        return [
            'endpoint' => (string) $data['endpoint'],
            'keys' => [
                'p256dh' => (string) $data['keys']['p256dh'],
                'auth' => (string) $data['keys']['auth'],
            ],
        ];
    }

    private function handlePush(InputInterface $input): DeliveryStatus
    {
        $subscriptionFile = $input->getOption('subscription-file');
        $subscription = $this->readSubscriptionFromFile(is_string($subscriptionFile) ? $subscriptionFile : null);
        $ttl = $input->getOption('ttl');
        $data = $this->parseKeyValueArray($input->getOption('data'));
        if ($subscription === null) {
            return DeliveryStatus::failed('push', 'Missing or invalid --subscription-file');
        }
        $ctx = $this->buildContext($input);
        return $this->facade->notifyPush($subscription, $data, is_numeric($ttl) ? (int) $ttl : null, [], $ctx);
    }

    private function handleDesktop(InputInterface $input): DeliveryStatus
    {
        $subscriptionFile = $input->getOption('subscription-file');
        $subscription = $this->readSubscriptionFromFile(is_string($subscriptionFile) ? $subscriptionFile : null);
        $ttl = $input->getOption('ttl');
        $data = $this->parseKeyValueArray($input->getOption('data'));
        if ($subscription === null) {
            return DeliveryStatus::failed('desktop', 'Missing or invalid --subscription-file');
        }
        $ctx = $this->buildContext($input);
        return $this->facade->notifyDesktop($subscription, $data, is_numeric($ttl) ? (int) $ttl : null, [], $ctx);
    }
}
