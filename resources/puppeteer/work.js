import puppeteer from 'puppeteer';
import PuppeteerExtra from "puppeteer-extra";
import Stealth from "puppeteer-extra-plugin-stealth";

PuppeteerExtra.use(Stealth());

// Launch the browser and open a new blank page
const browser = await puppeteer.connect({
    browserWSEndpoint: 'ws://127.0.0.1:9222/devtools/browser/a193c7da-457a-40f4-885d-f22ade09962b'
});
const page = await browser.newPage();

// Navigate the page to a URL.
await page.goto('https://chatgpt.com/');
await page.setViewport({width: 1080, height: 1024});

// Wait for network resources to fully load
await page.waitForNetworkIdle();
// Capture screenshot
await page.screenshot({
    path: '/var/www/meal-to-feel-api/resources/puppeteer/screenshots/before.png',
});

console.log('looking for...')

let profileButton = await page.$('[data-testid="profile-button"]');

if (profileButton) {
    console.log('There is a profile button');
    // await profileButton.click();
    profileButton = null;
} else {
    console.log('there is no profile button');

    let logIn = await page.$('[data-testid="login-button"]');

    if (logIn) {
        console.log('there is a log in button');
        await logIn.click();
        logIn = null;
    } else {
        console.log('there is none');
    }

    let welcomeLogIn = await page.$('[data-testid="welcome-login-button"]');

    if (welcomeLogIn) {
        console.log('there is a welcome log in button');
        await welcomeLogIn.click();
        welcomeLogIn = null;
    } else {
        console.log('there is none');
    }

    // Wait for network resources to fully load
    await page.waitForNetworkIdle();
    // Capture screenshot
    await page.screenshot({
        path: '/var/www/meal-to-feel-api/resources/puppeteer/screenshots/after.png',
    });

    console.log('looking for an email');

    let emailInput = await page.$('#email-input');

    if (emailInput) {
        console.log('there is an email input');
        await emailInput.type('mealtofeel@gmail.com');
        emailInput = null;
    } else {
        console.log('there is no email input');
    }

    await page.waitForNetworkIdle();

    let continueButton = await page.$('.continue-btn');

    if (continueButton) {
        console.log('there is a continue button');
        try {
            await continueButton.click();
        } catch (e) {
            //
        }
        continueButton = null;
    } else {
        console.log('there is no continue button');
    }

    await page.waitForNetworkIdle();

    let passwordInput = await page.$('input[type="password"]');

    if (passwordInput) {
        console.log('there is an password input');
        passwordInput.value = 'xzymaNjaVeV2GaP';
        await passwordInput.type('');

        passwordInput = null;
    } else {
        console.log('there is no password input');
    }

    await page.waitForNetworkIdle();

    let logInButton = await page.$('button[type="submit"]');

    if (logInButton) {
        console.log('there is a log in button');
        await logInButton.click();
        logInButton = null;
    } else {
        console.log('there is no log in button');
    }

    await page.waitForNetworkIdle();
}

profileButton = await page.$('[data-testid="profile-button"]');

if (!profileButton) {
    console.log('Failed to log in');
}

// <div className="gap-2 flex items-center pr-1 leading-[0]">
//     <button aria-label="Open Profile Menu" data-testid="profile-button"
//             className="flex h-10 w-10 items-center justify-center rounded-full hover:bg-token-main-surface-secondary focus-visible:bg-token-main-surface-secondary focus-visible:outline-0"
//             type="button" id="radix-:r7:" aria-haspopup="menu" aria-expanded="false" data-state="closed">
//         <div className="flex items-center justify-center overflow-hidden rounded-full">
//             <div className="relative flex"><img alt="User" width="32" height="32" className="rounded-sm"
//                                                 referrerPolicy="no-referrer"
//                                                 src="https://s.gravatar.com/avatar/2c53a55abe6aeca51c739f4a0234b926?s=480&amp;r=pg&amp;d=https%3A%2F%2Fcdn.auth0.com%2Favatars%2Fme.png"/>
//             </div>
//         </div>
//     </button>
// </div>

await page.waitForNetworkIdle();

let fileInput = await page.$('input[type=file]');

if (fileInput) {
    console.log('there is a file input');

    // Get all attributes of the button
    const attributes = await page.evaluate(button => {
        const attrs = {};
        // Iterate through all attributes of the button and store them in an object
        for (let i = 0; i < button.attributes.length; i++) {
            const attr = button.attributes[i];
            attrs[attr.name] = attr.value;
        }
        return attrs;
    }, fileInput);

    console.log('file input attributes:', attributes);

    // Check if the button is enabled
    const isEnabled = await page.evaluate(button => {
        return !button.disabled;  // Button is enabled if it's not disabled
    }, fileInput);

    if (isEnabled) {
        console.log('The file input is enabled');
    } else {
        console.log('The file input is disabled');
    }

    // await fileInput.uploadFile('/var/www/meal-to-feel-api/resources/puppeteer/screenshots/after.png');
} else {
    console.log('there is no file input');
}

console.log('looking for attach button');

const attachDisabled = await page.evaluate(() => {
    let result = null;

    [...document.querySelectorAll('button')]
        .filter(b => b.hasAttribute('aria-label'))
        .forEach(function (b) {
            if (b.getAttribute('aria-label').startsWith('Attach files')) {
                result = b.disabled;
            }
        })

    return result;
})

if (attachDisabled) {
    console.log('attach is disabled');
} else {
    console.log('attach is enabled');
}

// await browser.close();
