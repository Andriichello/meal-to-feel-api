<?php

namespace Tests\Dusk;

use App\Helpers\DuskHelper;
use Facebook\WebDriver\Interactions\Internal\WebDriverCoordinates;
use Facebook\WebDriver\WebDriver;
use Facebook\WebDriver\WebDriverPoint;
use Laravel\Dusk\Browser;
use Tests\TestCase;
use Throwable;

/**
 * Class GoogleTest.
 */
class GoogleTest extends TestCase
{
    /**
     * Test Google search page.
     *
     * @return void
     * @throws Throwable
     */
    public function testPage()
    {
        /** @var DuskHelper $helper */
        $helper = app(DuskHelper::class);

        $helper->setHeadless(true);
        $helper->setPort(9014);
        $helper->setPaths(
            storage_path('dusk/screenshots'),
            storage_path('dusk/console'),
            storage_path('dusk/source'),
        );

        $helper->start();
        $helper->browse(function (Browser $browser) {
            $browser->visit('https://chatgpt.com')
                ->pause(2000);

            $browser->driver
                ->executeScript("Object.defineProperty(navigator, 'language', {get: () => 'en-US'});");

            $browser->screenshot('before-press')
                ->storeSource('before-press')
                ->press('Log in')
                ->pause(3000)
                ->screenshot('after-press')
                ->storeSource('after-press');

            $browser->scrollIntoView('.container');

            $browser->resize(1920, 1080);
            $browser->moveMouse(-1920, -1080);

            $coordinates = $browser->driver->executeScript('
                var coords = {x: 0, y: 0};
                document.addEventListener("mousemove", function(event) {
                    coords.x = event.clientX;
                    coords.y = event.clientY;
                });
                return coords;
            ');

            $browser->moveMouse(1, 1);

            $coordinates = $browser->driver->executeScript('
                return coords;
            ');

//            $browser->clickAtPoint(936, 382);
//            for ($y = 400; $y < 700; $y += 10) {
//                for ($x = 960; $x > 800; $x -= 10) {
//                    try {
//                        $browser->click
//                        $browser->clickAtPoint($x, $y);
//                    } catch (Throwable) {
//                        //
//                    }
//                }
//            }

            $browser->pause(3000)
                ->screenshot('after-click')
                ->storeSource('after-click');
        });
    }
}
