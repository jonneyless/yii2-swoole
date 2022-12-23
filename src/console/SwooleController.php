<?php

namespace ijony\yiis\console;

use ijony\yiis\traits\LogTrait;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\Console\Style\SymfonyStyle;
use Yii;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Yii2 Swoole server
 */
class SwooleController extends Controller
{

    use LogTrait;

    public $env;

    public $daemonize;

    public $ignore;

    public $version;

    /**
     * @var OutputStyle
     */
    protected $output;

    /**
     * Root for project
     *
     * @var String
     */
    protected $basePath;

    /**
     * The default verbosity of output commands.
     *
     * @var int
     */
    protected $verbosity = OutputInterface::VERBOSITY_NORMAL;

    /**
     * The mapping between human readable verbosity levels and Symfony's OutputInterface.
     *
     * @var array
     */
    protected $verbosityMap = [
        'v' => OutputInterface::VERBOSITY_VERBOSE,
        'vv' => OutputInterface::VERBOSITY_VERY_VERBOSE,
        'vvv' => OutputInterface::VERBOSITY_DEBUG,
        'quiet' => OutputInterface::VERBOSITY_QUIET,
        'normal' => OutputInterface::VERBOSITY_NORMAL,
    ];

    public function init()
    {
        parent::init();

        $this->output = new SymfonyStyle(new ArgvInput(), new ConsoleOutput());
        $this->basePath = dirname(Yii::getAlias('@vendor'));
    }

    public function options($actionID)
    {
        return ArrayHelper::merge(parent::options($actionID), [
            'env', 'daemonize', 'ignore', 'version',
        ]);
    }

    public function optionAliases()
    {
        return ArrayHelper::merge(parent::optionAliases(), [
            'e' => 'env', 'd' => 'daemonize', 'i' => 'ignore', 'v' => 'version',
        ]);
    }

    /**
     * Confirm a question with the user.
     *
     * @param string $question
     * @param bool $default
     *
     * @return bool
     */
    public function confirm($question, $default = false)
    {
        return $this->output->confirm($question, $default);
    }

    /**
     * Prompt the user for input.
     *
     * @param string $question
     * @param string|null $default
     *
     * @return mixed
     */
    public function ask($question, $default = null)
    {
        return $this->output->ask($question, $default);
    }

    /**
     * Prompt the user for input but hide the answer from the console.
     *
     * @param string $question
     * @param bool $fallback
     *
     * @return mixed
     */
    public function secret($question, $fallback = true)
    {
        $question = new Question($question);

        $question->setHidden(true)->setHiddenFallback($fallback);

        return $this->output->askQuestion($question);
    }

    /**
     * Give the user a single choice from an array of answers.
     *
     * @param string $question
     * @param array $choices
     * @param string|null $default
     * @param mixed|null $attempts
     * @param bool|null $multiple
     *
     * @return string
     */
    public function choice($question, array $choices, $default = null, $attempts = null, $multiple = null)
    {
        $question = new ChoiceQuestion($question, $choices, $default);

        $question->setMaxAttempts($attempts)->setMultiselect($multiple);

        return $this->output->askQuestion($question);
    }

    /**
     * Write a string as question output.
     *
     * @param string $string
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function question($string, $verbosity = null)
    {
        $this->line($string, 'question', $verbosity);
    }

    /**
     * Write a string as standard output.
     *
     * @param string $string
     * @param string|null $style
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function line($string, $style = null, $verbosity = null)
    {
        $styled = $style ? "<$style>$string</$style>" : $string;

        $this->output->writeln($styled, $this->parseVerbosity($verbosity));
    }

    /**
     * Get the verbosity level in terms of Symfony's OutputInterface level.
     *
     * @param string|int|null $level
     *
     * @return int
     */
    protected function parseVerbosity($level = null)
    {
        if (isset($this->verbosityMap[$level])) {
            $level = $this->verbosityMap[$level];
        } elseif (!is_int($level)) {
            $level = $this->verbosity;
        }

        return $level;
    }

    /**
     * Write a string as warning output.
     *
     * @param string $string
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function warn($string, $verbosity = null)
    {
        if (!$this->output->getFormatter()->hasStyle('warning')) {
            $style = new OutputFormatterStyle('yellow');

            $this->output->getFormatter()->setStyle('warning', $style);
        }

        $this->line($string, 'warning', $verbosity);
    }

    /**
     * Write a string in an alert box.
     *
     * @param string $string
     *
     * @return void
     */
    public function alert($string)
    {
        $length = Str::length(strip_tags($string)) + 12;

        $this->comment(str_repeat('*', $length));
        $this->comment('*     ' . $string . '     *');
        $this->comment(str_repeat('*', $length));

        $this->output->newLine();
    }

    /**
     * Write a string as comment output.
     *
     * @param string $string
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function comment($string, $verbosity = null)
    {
        $this->line($string, 'comment', $verbosity);
    }

    public function actionConfig()
    {
        $this->prepareConfig();
    }

    protected function prepareConfig()
    {
        $svrConf = $this->loadConfig();

        $this->preSet($svrConf);

        $ret = $this->preCheck($svrConf);
        if ($ret !== 0) {
            return $ret;
        }

        // Fixed $_ENV['APP_ENV']
        if (isset($_SERVER['APP_ENV'])) {
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'];
        }

        $laravelConf = [
            'root_path' => $svrConf['yii_base_path'],
            'static_path' => $svrConf['swoole']['document_root'],
            'cleaners' => array_unique((array) ArrayHelper::getValue($svrConf, 'cleaners', [])),
            'destroy_controllers' => ArrayHelper::getValue($svrConf, 'destroy_controllers', []),
            '_SERVER' => $_SERVER,
            '_ENV' => $_ENV,
        ];

        $config = ['server' => $svrConf, 'laravel' => $laravelConf];

        return file_put_contents($this->getConfigPath(), serialize($config)) > 0 ? 0 : 1;
    }

    protected function loadConfig()
    {
        if (file_exists($this->formatPath(env('YIIS_WORK_PATH') . '/config/yiis.php'))) {
            $conf = require $this->formatPath(env('YIIS_WORK_PATH') . '/config/yiis.php');
        } else {
            $conf = require Yii::getAlias('@vendor/ijony/yii2-swoole/config/yiis.php');
        }

        return $conf;
    }

    protected function preSet(array &$svrConf)
    {
        if (!isset($svrConf['enable_gzip'])) {
            $svrConf['enable_gzip'] = false;
        }
        if (empty($svrConf['yii_base_path'])) {
            $svrConf['yii_base_path'] = $this->formatPath(env('YIIS_WORK_PATH'));
        }
        if (empty($svrConf['process_prefix'])) {
            $svrConf['process_prefix'] = $svrConf['yii_base_path'];
        }
        if ($this->ignore) {
            $svrConf['ignore_check_pid'] = true;
        } elseif (!isset($svrConf['ignore_check_pid'])) {
            $svrConf['ignore_check_pid'] = false;
        }
        if (empty($svrConf['swoole']['document_root'])) {
            $svrConf['swoole']['document_root'] = env('YIIS_DOCS_PATH');
        }
        if ($this->daemonize) {
            $svrConf['swoole']['daemonize'] = true;
        } elseif (!isset($svrConf['swoole']['daemonize'])) {
            $svrConf['swoole']['daemonize'] = false;
        }
        if (empty($svrConf['swoole']['pid_file'])) {
            $svrConf['swoole']['pid_file'] = $svrConf['yii_base_path'] . '/runtime/yiis.pid';
        }
        if (empty($svrConf['timer']['max_wait_time'])) {
            $svrConf['timer']['max_wait_time'] = 5;
        }

        // Set X-Version
        $xVersion = (string) $this->version;
        if ($xVersion !== '') {
            $_SERVER['X_VERSION'] = $_ENV['X_VERSION'] = $xVersion;
        }

        return 0;
    }

    protected function preCheck(array $svrConf)
    {
        if (!empty($svrConf['enable_gzip']) && version_compare(SWOOLE_VERSION, '4.1.0', '>=')) {
            $this->error('enable_gzip is DEPRECATED since Swoole 4.1.0, set http_compression of Swoole instead, http_compression is disabled by default.');
            $this->info('If there is a proxy server like Nginx, suggest that enable gzip in Nginx and disable gzip in Swoole, to avoid the repeated gzip compression for response.');

            return 1;
        }
        if (!empty($svrConf['events'])) {
            if (empty($svrConf['swoole']['task_worker_num']) || $svrConf['swoole']['task_worker_num'] <= 0) {
                $this->error('Asynchronous event listening needs to set task_worker_num > 0');

                return 1;
            }
        }

        return 0;
    }

    protected function getConfigPath()
    {
        return $this->formatPath(env('YIIS_WORK_PATH') . '/runtime/yiis.conf');
    }

    /**
     * @param string $path
     *
     * @return string
     */
    protected function formatPath(string $path)
    {
        if (substr($path, 0, 1) !== '/') {
            return $this->basePath . '/' . $path;
        }

        return $path;
    }

    /**
     * Write a string as information output.
     *
     * @param string $string
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function info($string, $verbosity = null)
    {
        $this->line($string, 'info', $verbosity);
    }

    /**
     * Write a string as error output.
     *
     * @param string $string
     * @param int|string|null $verbosity
     *
     * @return void
     */
    public function error($string, $verbosity = null)
    {
        $this->line($string, 'error', $verbosity);
    }

    /**
     * swoole server infomation
     *
     * @return bool|void
     * @throws \yii\base\InvalidConfigException
     */
    public function actionInfo()
    {
        $this->prepareConfig();
        $this->showLogo();
        $this->showComponents();
        $this->showProtocols();
    }

    protected function showLogo()
    {
        static $logo = <<<EOS
_____.___.__.__  _________
\__  |   |__|__|/   _____/
 /   |   |  |  |\_____  \ 
 \____   |  |  |/        \
 / ______|__|__/_______  /
 \/                    \/ 

EOS;
        $this->info($logo);
        $this->info('Speed up your Yii2');
    }

    protected function showComponents()
    {
        $this->comment('>>> Components');
        $YiiSVersion = '-';
        $lockFile = $this->formatPath('composer.lock');
        $cfg = file_exists($lockFile) ? Json::decode(file_get_contents($lockFile)) : [];
        if (isset($cfg['packages'])) {
            $packages = ArrayHelper::merge($cfg['packages'], ArrayHelper::getValue($cfg, 'packages-dev', []));
            foreach ($packages as $package) {
                if (isset($package['name']) && $package['name'] === 'ijony/yii2-swoole') {
                    $YiiSVersion = ltrim($package['version'], 'vV');
                    break;
                }
            }
        }
        $this->table(['Component', 'Version'], [
            [
                'PHP',
                PHP_VERSION,
            ],
            [
                'Swoole',
                SWOOLE_VERSION,
            ],
            [
                'YiiS',
                $YiiSVersion,
            ],
            [
                'Yii Framework [<info>' . env('APP_ENV') . '</info>]',
                Yii::getVersion(),
            ],
        ]);
    }

    protected function showProtocols()
    {
        $this->comment('>>> Protocols');

        $config = unserialize((string) file_get_contents($this->getConfigPath()));
        $ssl = isset($config['server']['swoole']['ssl_key_file'], $config['server']['swoole']['ssl_cert_file']);
        $socketType = isset($config['server']['socket_type']) ? $config['server']['socket_type'] : SWOOLE_SOCK_TCP;
        if (in_array($socketType, [SWOOLE_SOCK_UNIX_DGRAM, SWOOLE_SOCK_UNIX_STREAM])) {
            $listenAt = $config['server']['listen_ip'];
        } else {
            $listenAt = sprintf('%s:%s', $config['server']['listen_ip'], $config['server']['listen_port']);
        }

        $tableRows = [
            [
                'Main HTTP',
                '<info>On</info>',
                Yii::$app->name,
                sprintf('%s://%s', $ssl ? 'https' : 'http', $listenAt),
            ],
        ];
        if (!empty($config['server']['websocket']['enable'])) {
            $tableRows [] = [
                'Main WebSocket',
                '<info>On</info>',
                $config['server']['websocket']['handler'],
                sprintf('%s://%s', $ssl ? 'wss' : 'ws', $listenAt),
            ];
        }

        $socketTypeNames = [
            SWOOLE_SOCK_TCP => 'TCP IPV4 Socket',
            SWOOLE_SOCK_TCP6 => 'TCP IPV6 Socket',
            SWOOLE_SOCK_UDP => 'UDP IPV4 Socket',
            SWOOLE_SOCK_UDP6 => 'TCP IPV6 Socket',
            SWOOLE_SOCK_UNIX_DGRAM => 'Unix Socket Dgram',
            SWOOLE_SOCK_UNIX_STREAM => 'Unix Socket Stream',
        ];
        $sockets = isset($config['server']['sockets']) ? $config['server']['sockets'] : [];
        foreach ($sockets as $key => $socket) {
            if (isset($socket['enable']) && !$socket['enable']) {
                continue;
            }

            $name = 'Port#' . $key . ' ';
            $name .= isset($socketTypeNames[$socket['type']]) ? $socketTypeNames[$socket['type']] : 'Unknown socket';
            $tableRows [] = [
                $name,
                '<info>On</info>',
                $socket['handler'],
                sprintf('%s:%s', $socket['host'], $socket['port']),
            ];
        }
        $this->table(['Protocol', 'Status', 'Handler', 'Listen At'], $tableRows);
    }

    /**
     * Format input to textual table.
     *
     * @param array $headers
     * @param \Illuminate\Contracts\Support\Arrayable|array $rows
     * @param string $tableStyle
     * @param array $columnStyles
     *
     * @return void
     */
    public function table($headers, array $rows, $tableStyle = 'default', array $columnStyles = [])
    {
        $table = new Table($this->output);

        $table->setHeaders((array) $headers)->setRows($rows)->setStyle($tableStyle);

        foreach ($columnStyles as $columnIndex => $columnStyle) {
            $table->setColumnStyle($columnIndex, $columnStyle);
        }

        $table->render();
    }

    public function actionPublish()
    {
        $configPath = $this->formatPath(env('YIIS_WORK_PATH') . '/config/yiis.php');
        $todoList = [
            [
                'from' => realpath(__DIR__ . '/../../config/yiis.php'),
                'to' => $configPath,
                'mode' => 0644,
            ],
            [
                'from' => realpath(__DIR__ . '/../../bin/yii2swoole'),
                'to' => $this->formatPath('bin/yii2swoole'),
                'mode' => 0755,
                'link' => true,
            ],
            [
                'from' => realpath(__DIR__ . '/../../bin/fswatch'),
                'to' => $this->formatPath('bin/fswatch'),
                'mode' => 0755,
                'link' => true,
            ],
            [
                'from' => realpath(__DIR__ . '/../../bin/inotify'),
                'to' => $this->formatPath('bin/inotify'),
                'mode' => 0755,
                'link' => true,
            ],
        ];
        if (file_exists($configPath)) {
            $choice = $this->anticipate($configPath . ' already exists, do you want to override it ? Y/N',
                ['Y', 'N'],
                'N'
            );
            if (!$choice || strtoupper($choice) !== 'Y') {
                array_shift($todoList);
            }
        }

        foreach ($todoList as $todo) {
            $toDir = dirname($todo['to']);
            if (!is_dir($toDir) && !mkdir($toDir, 0755, true) && !is_dir($toDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $toDir));
            }
            if (file_exists($todo['to'])) {
                unlink($todo['to']);
            }
            $operation = 'Copied';
            if (empty($todo['link'])) {
                copy($todo['from'], $todo['to']);
            } elseif (@link($todo['from'], $todo['to'])) {
                $operation = 'Linked';
            } else {
                copy($todo['from'], $todo['to']);
            }
            chmod($todo['to'], $todo['mode']);
            $this->line("<info>{$operation} file</info> <comment>[{$todo['from']}]</comment> <info>To</info> <comment>[{$todo['to']}]</comment>");
        }

        return 0;
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param string $question
     * @param array|callable $choices
     * @param string|null $default
     *
     * @return mixed
     */
    public function anticipate($question, $choices, $default = null)
    {
        return $this->askWithCompletion($question, $choices, $default);
    }

    /**
     * Prompt the user for input with auto completion.
     *
     * @param string $question
     * @param array|callable $choices
     * @param string|null $default
     *
     * @return mixed
     */
    public function askWithCompletion($question, $choices, $default = null)
    {
        $question = new Question($question, $default);

        is_callable($choices)
            ? $question->setAutocompleterCallback($choices)
            : $question->setAutocompleterValues($choices);

        return $this->output->askQuestion($question);
    }
}