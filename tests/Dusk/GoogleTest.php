<?php

namespace Tests\Dusk;

use App\Helpers\DuskHelper;
use Illuminate\Support\Str;
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
        $helper->setPort(9012);
        $helper->setPaths(
            storage_path('dusk/screenshots'),
            storage_path('dusk/console'),
            storage_path('dusk/source'),
        );

        $helper->start();
        $helper->browse(function (Browser $browser) {
            $browser->visit('https://www.google.com')
                ->pause(100)
                ->screenshot(Str::random(4))
                ->assertSee('Google');
        });
    }
}
