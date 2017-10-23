<?php namespace Tests\Browser\Helpers;

use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;
use GuzzleHttp\Client;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ProcessUtils;

class StartServers
{
    /**
     * @var Mink
     */
    protected static $mink;

    /**
     * @var bool
     */
    private $headless;

    /**
     * StartServers constructor.
     */
    public function __construct($headless = true)
    {
        $this->headless = $headless;
    }

    public function start(): Session
    {
        if (!self::$mink) {
            self::$mink = new Mink(['chrome' => new Session(new ChromeDriver('http://localhost:9222', null, ''))]);

            $this->startChrome();
            $this->startPhpServer();
        }

        return $this->waitUntilChromeIsReadyAndConnect();
    }

    protected function waitUntilChromeIsReadyAndConnect(): Session
    {
        foreach (range(1, 1000) as $_) {
            try {
                return self::$mink->getSession('chrome');
            } catch (\Exception $e) {
                usleep(50000);
            }
        }
        throw new \RuntimeException('Cannot start Chrome');
    }

    protected function startPhpServer(): void
    {
        $this->oldEnvTesting = file_get_contents(base_path('.env.testing'));

        $c = new Client();

        try {
            $c->get('http://localhost:8001/');
            throw new \RuntimeException('Something is already running on port 8001');
        } catch (\Exception $e) {
        }

        $command = sprintf('%s -S %s:%s %s/server.php',
            ProcessUtils::escapeArgument((new PhpExecutableFinder)->find(false)),
            'localhost',
            '8001',
            ProcessUtils::escapeArgument(base_path())
        );

        $env = [
            'SESSION_DRIVER' => 'file',
            'APP_ENV' => 'testing',
        ];

        if (env('DATABASE_URL')) {
            $env['DATABASE_URL'] = env('DATABASE_URL');
        }

        if(PHP_OS === "WINNT"){
            $process = new Process($command, public_path());
        } else {
            $process = new Process($command, public_path(), $env);
        }

        $process->start();

        // wait until server responds
        foreach (range(1, 20) as $value) {
            try {
                $c->get('http://localhost:8001/');
                return;
            } catch (\GuzzleHttp\Exception\RequestException $e) {
                usleep(50000);
            }
        }

        throw new \RuntimeException('Cannot start PHP Server');
    }

    protected function startChrome(): void
    {

        $flags = implode(' ', [
            ($this->headless || env('CIRCLECI')) ? '--headless' : '',
            '--user-data-dir="' . $this->dirPath() . '"',
            '--disable-gpu',
            '--disable-translate',
            '--disable-extensions',
            '--remote-debugging-port=9222'
        ]);

        if (!file_exists($this->appPath())) {
            throw new \RuntimeException('Chrome not found at location: ' . $this->appPath());
        }

        with(new Process(escapeshellarg($this->appPath()) . " {$flags}"))->start();
    }

    private function dirPath()
    {
        switch (PHP_OS) {
            case 'WINNT':
                return env('CHROME_TEMP_WIN_PATH');
            default:
                return '/tmp/test';
        }
    }

    private function appPath()
    {
        switch (PHP_OS) {
            case 'Darwin':
                return '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome';
            case 'WINNT':
                if(env('CHROME_BROWSER_WIN_PATH'))
                    return env('CHROME_BROWSER_WIN_PATH');
                throw new \RuntimeException("Don't know where it is");
            default:
                return '/usr/bin/google-chrome';
        }
    }
}