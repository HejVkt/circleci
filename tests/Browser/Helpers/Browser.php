<?php namespace Tests\Browser\Helpers;

use App\Models\User;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromePage;
use Symfony\Component\Process\Process;

class Browser
{
    /** @var Session */
    public $session;

    public $baseUrl = 'http://localhost:8001';

    /**
     * @var ChromeTestCase
     */
    private $test;

    /**
     * Browser constructor.
     * @param ChromeTestCase $test
     */
    public function __construct(ChromeTestCase $test)
    {
        $this->test = $test;
    }

    public function actingAs(User $user): Browser
    {
        $this->session->visit($this->baseUrl . '/_dusk/login/' . $user->id);

        return $this;
    }

    public function visit($url): Browser
    {
        $this->session->visit($this->baseUrl . $url);

        return $this;
    }

    public function fillField($string, $data): Browser
    {
        $this->session->getPage()->fillField($string, $data);

        return $this;
    }

    public function pressButton($locator): Browser
    {
        $this->session->getPage()->pressButton($locator);

        return $this;
    }

    public function clickLink($string): Browser
    {
        $this->session->getPage()->clickLink($string);

        return $this;
    }

    public function waitForText($text, $timeout = 3000): Browser
    {
        $wait = $this->session->wait($timeout, 'document.body.innerText.includes(' . json_encode($text) . ')');
        if ($wait != 1) {
            print $this->session->getPage()->getContent();
        }
        $this->test->assertEquals(1, $wait, "Wait for text '$text' failed");

        return $this;
    }

    public function waitForSelector($selector, $timeout = 3000): Browser
    {
        $wait = $this->session->wait($timeout, 'document.querySelectorAll(' . json_encode($selector) . ').length');
        $this->test->assertEquals(1, $wait, "Wait for selector '$selector' failed");

        return $this;
    }

    public function assertSee($text): Browser
    {
        $evaluateScript = $this->session->evaluateScript('document.body.innerText.includes(' . json_encode($text) . ')');
        if (!$evaluateScript) {
            dump($this->session->getPage()->getContent());
        }
        $this->test->assertTrue($evaluateScript, "Do not see '$text'");

        return $this;
    }

    public function assertNotSee($text): Browser
    {
        $evaluateScript = $this->session->evaluateScript('document.body.innerText.includes(' . json_encode($text) . ')');
        $this->test->assertFalse($evaluateScript, "I see '$text', where I should not see it");

        return $this;
    }

    public function tearDown()
    {
        $this->session->stop();
    }

    public function setUp()
    {
        $servers = new StartServers($this->test->headless);
        $this->session = $servers->start();
    }

    public function waitAndPressButton($locator): Browser
    {
        return $this
            ->waitForText($locator)
            ->pressButton($locator);

    }

    public function waitAndClickLink($name): Browser
    {
        return $this
            ->waitForText($name)
            ->clickLink($name);
    }

    public function evalScript($string): Browser
    {
        $this->session->evaluateScript($string);

        return $this;
    }

    public function sleep($time = 30.0): Browser
    {
        usleep((int)($time * 1000000));

        return $this;
    }

    public function longSleep($time = 3600.0): Browser
    {
        usleep((int)($time * 1000000));

        return $this;
    }

    public function clickCss($css): Browser
    {
        $this->session->getPage()->find('css', $css)->click();

        return $this;
    }

    public function clickText($string, $tag = '*'): Browser
    {
        $this->session->getPage()->find('xpath',
            '//' . $tag . '[text()[contains(.,"' . addslashes($string) . '")]]')->click();

        return $this;
    }

    public function setValueAtCss($cssSelector, $value): Browser
    {
        $this->session->getPage()->find('css', $cssSelector)->setValue($value);
        return $this;
    }


    public function clearLocalStorage(): Browser
    {
        return $this->evalScript('localStorage.clear();');
    }

    public function setValueAfterLabel($labelPart, $value): Browser
    {
        $escapedLabel = json_encode($labelPart);

        $lastChar = mb_substr($value, -1);
        $escapedValue = json_encode(mb_substr($value, 0, -1));

        $this->evalScript("
            document.querySelectorAll('label').forEach(e => {
                if(e.textContent.indexOf($escapedLabel) !== -1) {
                    let elt = e.nextElementSibling;
                    if(elt.tagName != 'INPUT' && elt.tagName != 'TEXTAREA') {
                        elt = elt.querySelector('input') ? elt.querySelector('input') : elt.querySelector('textarea');
                    }
                    elt.value = $escapedValue;
                    elt.focus();
                    document.activeElement.dispatchEvent(new Event('change', { bubbles: true, cancelable: false }));
                }
            });
        ");

        $this->type($lastChar);
        return $this;
    }

    /**
     * copied with minor changes from
     * @see \DMore\ChromeDriver\ChromeDriver::setValue
     */
    public function type($value, $fireChangeEvent = true): Browser
    {
        $page = $this->getChromePage();

        $mbStrlen = mb_strlen($value);
        for ($i = 0; $i < $mbStrlen; $i++) {
            $char = mb_substr($value, $i, 1);
            if ($char === "\n") {
                $page->send('Input.dispatchKeyEvent', ['type' => 'keyDown', 'text' => chr(13)]);
            }
            $page->send('Input.dispatchKeyEvent', ['type' => 'keyDown', 'text' => $char]);
            $page->send('Input.dispatchKeyEvent', ['type' => 'keyUp']);
        }
        usleep(5000);
        if ($fireChangeEvent) {
            $this->session->executeScript("document.activeElement.dispatchEvent(new Event('change'))");
        }

        return $this;
    }

    public function displayScreenshot()
    {
        file_put_contents('/tmp/screenshot.png', $this->session->getScreenshot());
        switch (PHP_OS) {
            case 'Darwin':
                return (new Process('open /tmp/screenshot.png'))->start();
            case 'WINNT':
                throw new \RuntimeException("Don't what to do");
            default:
                return (new Process('xdg-open /tmp/screenshot.png'))->start();
        }
    }

    public function pressButtonOrLink($name): Browser
    {
        try {
            $this->clickLink($name);
        } catch (ElementNotFoundException $e) {
            $this->pressButton($name);
        }
        return $this;
    }

    public function swal2Confirm(): Browser
    {
        try {
            $this->clickCss('.swal2-confirm');
        } catch (\Exception $e) {
            try {
                $this->sleep(0.1);
                $this->clickCss('.swal2-confirm');
            } catch (\Exception $e) {
                $this->sleep(0.5);
                $this->clickCss('.swal2-confirm');
            }
        }
        return $this;
    }

    /**
     * ChromePage is private, so we need this hack to access it
     */
    protected function getChromePage(): ChromePage
    {
        $object = $this->session->getDriver();
        $getter = function () {
            /** @noinspection PhpUndefinedFieldInspection */
            return $this->page;
        };
        /** @noinspection ImplicitMagicMethodCallInspection */
        return \Closure::bind($getter, $object, get_class($object))->__invoke();
    }

}