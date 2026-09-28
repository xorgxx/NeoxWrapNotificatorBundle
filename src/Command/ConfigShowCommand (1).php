<?php

declare(strict_types=1);

namespace Neox\WrapNotificatorBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'wrap:notificator:config', description: 'Display all WrapNotificator bundle configuration')]
final class ConfigShowCommand extends Command
{
    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        private readonly array $config = [],
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('json', null, InputOption::VALUE_NONE, 'Output machine-readable JSON only (no pretty output)')
            ->addOption('section', null, InputOption::VALUE_REQUIRED, 'Show only a specific section: mercure|logging|live_flash|ui')
            ->setHelp(<<<'HELP'
Affiche toute la configuration du bundle WrapNotificator.

Utilisation:
  php bin/console wrap:notificator:config                    # Affichage complet
  php bin/console wrap:notificator:config --json             # Format JSON uniquement
  php bin/console wrap:notificator:config --section=mercure  # Section spécifique
  php bin/console wrap:notificator:config --section=ui       # Configuration UI uniquement

Sections disponibles:
  - mercure     : Configuration Mercure (enabled, public_url, topics, ui, etc.)
  - logging     : Configuration des logs
  - live_flash  : Configuration des flash messages en temps réel
  - ui          : Configuration UI (renderer, theme, CSS, etc.)

Exemples:
  # Voir la configuration complète
  php bin/console wrap:notificator:config

  # Exporter en JSON pour scripts
  php bin/console wrap:notificator:config --json

  # Vérifier uniquement le renderer UI
  php bin/console wrap:notificator:config --section=ui
HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $section = $input->getOption('section');
        $onlyJson = (bool) $input->getOption('json');

        $displayConfig = $this->config;

        // Filter by section if requested
        if (is_string($section) && $section !== '') {
            $sectionKey = strtolower(trim($section));
            
            // Special case: 'ui' is nested under 'mercure'
            if ($sectionKey === 'ui') {
                $displayConfig = $this->config['mercure']['ui'] ?? [];
            } elseif (isset($this->config[$sectionKey])) {
                $displayConfig = [$sectionKey => $this->config[$sectionKey]];
            } else {
                $output->writeln(sprintf('<error>Section "%s" not found. Available: mercure, logging, live_flash, ui</error>', $section));
                return Command::INVALID;
            }
        }

        $json = json_encode($displayConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{}';
        }

        if ($onlyJson) {
            $output->writeln($json);
            return Command::SUCCESS;
        }

        $io = new SymfonyStyle($input, $output);
        $io->title('WrapNotificator — Configuration');

        // Mercure section
        if (!isset($displayConfig['mercure']) || isset($displayConfig['mercure'])) {
            $mercure = $displayConfig['mercure'] ?? $this->config['mercure'] ?? [];
            if ($mercure !== []) {
                $this->displayMercureSection($io, $mercure);
            }
        }

        // Logging section
        if (!isset($displayConfig['logging']) || isset($displayConfig['logging'])) {
            $logging = $displayConfig['logging'] ?? $this->config['logging'] ?? [];
            if ($logging !== []) {
                $this->displayLoggingSection($io, $logging);
            }
        }

        // Live Flash section
        if (!isset($displayConfig['live_flash']) || isset($displayConfig['live_flash'])) {
            $liveFlash = $displayConfig['live_flash'] ?? $this->config['live_flash'] ?? [];
            if ($liveFlash !== []) {
                $this->displayLiveFlashSection($io, $liveFlash);
            }
        }

        // If only UI section requested
        if (is_string($section) && strtolower(trim($section)) === 'ui' && is_array($displayConfig)) {
            $this->displayUiSection($io, $displayConfig);
        }

        $io->newLine();
        $io->writeln('<info>Configuration JSON complète</info>');
        $output->writeln($json);

        return Command::SUCCESS;
    }

    /**
     * @param array<string, mixed> $mercure
     */
    private function displayMercureSection(SymfonyStyle $io, array $mercure): void
    {
        $io->section('Mercure');
        
        $enabled = $mercure['enabled'] ?? false;
        $io->writeln(sprintf('%s Enabled: %s', $enabled ? '✔' : '✖', $enabled ? 'YES' : 'NO'));
        
        if (isset($mercure['public_url'])) {
            $io->writeln(sprintf('• Public URL: %s', (string) $mercure['public_url']));
        }
        
        if (isset($mercure['notify_status'])) {
            $io->writeln(sprintf('• Notify Status: %s', $mercure['notify_status'] ? 'YES' : 'NO'));
        }
        
        if (isset($mercure['auto_inject'])) {
            $io->writeln(sprintf('• Auto Inject: %s', $mercure['auto_inject'] ? 'YES' : 'NO'));
        }
        
        if (isset($mercure['turbo_enabled'])) {
            $io->writeln(sprintf('• Turbo Enabled: %s', $mercure['turbo_enabled'] ? 'YES' : 'NO'));
        }
        
        if (isset($mercure['only_authenticated'])) {
            $io->writeln(sprintf('• Only Authenticated: %s', $mercure['only_authenticated'] ? 'YES' : 'NO'));
        }
        
        if (isset($mercure['with_credentials_default'])) {
            $io->writeln(sprintf('• With Credentials Default: %s', $mercure['with_credentials_default'] ? 'YES' : 'NO'));
        }
        
        if (isset($mercure['default_topics']) && is_array($mercure['default_topics'])) {
            $io->writeln('• Default Topics:');
            foreach ($mercure['default_topics'] as $topic) {
                $io->writeln(sprintf('  - %s', (string) $topic));
            }
        }
        
        if (isset($mercure['ui']) && is_array($mercure['ui'])) {
            $this->displayUiSection($io, $mercure['ui']);
        }
    }

    /**
     * @param array<string, mixed> $ui
     */
    private function displayUiSection(SymfonyStyle $io, array $ui): void
    {
        $io->section('UI Configuration');
        
        if (isset($ui['renderer'])) {
            $renderer = (string) $ui['renderer'];
            $icon = match ($renderer) {
                'izitoast' => '🎨',
                'bootstrap' => '🅱️',
                'auto' => '🔄',
                default => '•',
            };
            $io->writeln(sprintf('%s Renderer: %s', $icon, strtoupper($renderer)));
            
            if ($renderer === 'auto') {
                $io->note('Mode AUTO: iziToast si disponible, sinon Bootstrap 5');
            }
        }
        
        if (isset($ui['force_theme'])) {
            $io->writeln(sprintf('• Force Theme: %s', (string) $ui['force_theme']));
        }
        
        if (isset($ui['toast_theme'])) {
            $io->writeln(sprintf('• Toast Theme: %s', (string) $ui['toast_theme']));
        }
        
        if (isset($ui['external_css'])) {
            $io->writeln(sprintf('• External CSS: %s', $ui['external_css'] ? 'YES' : 'NO'));
        }
        
        if (isset($ui['auto_link_css'])) {
            $io->writeln(sprintf('• Auto Link CSS: %s', $ui['auto_link_css'] ? 'YES' : 'NO'));
        }
        
        if (isset($ui['asset_path'])) {
            $io->writeln(sprintf('• Asset Path: %s', (string) $ui['asset_path']));
        }
        
        if (isset($ui['asset_fallback_prefix'])) {
            $io->writeln(sprintf('• Asset Fallback Prefix: %s', (string) $ui['asset_fallback_prefix']));
        }
    }

    /**
     * @param array<string, mixed> $logging
     */
    private function displayLoggingSection(SymfonyStyle $io, array $logging): void
    {
        $io->section('Logging');
        
        $enabled = $logging['enabled'] ?? false;
        $io->writeln(sprintf('%s Enabled: %s', $enabled ? '✔' : '✖', $enabled ? 'YES' : 'NO'));
    }

    /**
     * @param array<string, mixed> $liveFlash
     */
    private function displayLiveFlashSection(SymfonyStyle $io, array $liveFlash): void
    {
        $io->section('Live Flash Messages');
        
        $enabled = $liveFlash['enabled'] ?? false;
        $io->writeln(sprintf('%s Enabled: %s', $enabled ? '✔' : '✖', $enabled ? 'YES' : 'NO'));
        
        if (isset($liveFlash['consume'])) {
            $io->writeln(sprintf('• Consume: %s', $liveFlash['consume'] ? 'YES' : 'NO'));
        }
        
        if (isset($liveFlash['group_messages'])) {
            $io->writeln(sprintf('• Group Messages: %s', $liveFlash['group_messages'] ? 'YES' : 'NO'));
        }
        
        if (isset($liveFlash['default_topic_prefix'])) {
            $io->writeln(sprintf('• Default Topic Prefix: %s', (string) $liveFlash['default_topic_prefix']));
        }
    }
}
