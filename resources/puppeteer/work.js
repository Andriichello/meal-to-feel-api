import puppeteer from 'puppeteer';
import PuppeteerExtra from "puppeteer-extra";
import Stealth from "puppeteer-extra-plugin-stealth";

const args = process.argv.slice(2);

const username = args[0];
const password = args[1];
const filePath = args[2];

PuppeteerExtra.use(Stealth());

// Launch the browser and open a new blank page
const browser = await puppeteer.connect({
    browserWSEndpoint: 'ws://127.0.0.1:9222/devtools/browser/12aabb57-5a57-45a0-8224-92243e88fa8e'
});
const page = await browser.newPage();

// logout: https://chatgpt.com/auth/logout

// Navigate the page to a URL.
await page.goto('https://chatgpt.com');
await page.setViewport({width: 1080, height: 1024});

// Wait until everything loads.
await page.waitForNetworkIdle();

let profileButton = await page.$('[data-testid="profile-button"], [data-testid="accounts-profile-button"]');

if (profileButton) {
    console.log('is logged in');
    await profileButton.click();

    const loggedInAs = await page.$eval(
        'div.popover div.ml-3.mr-2.py-2.text-sm.text-token-text-secondary',
        (element) => element.textContent.trim()
    );

    console.log(loggedInAs);

    if (loggedInAs !== username) {
        profileButton = null;
        await page.goto('https://chatgpt.com/auth/logout');

        // Wait until everything loads.
        await page.waitForNetworkIdle();
    }
}

if (!profileButton){
    console.log('is not logged in');

    let logIn = await page.$('[data-testid="welcome-login-button"]')
        ?? await page.$('[data-testid="login-button"]');

    if (logIn) {
        console.log('there is a log in button');
        await logIn.click();
        logIn = null;
    }

    console.log('waiting for an email input');
    await page.waitForSelector(
        '#email-input, [name="username"], [type="email"]',
        { timeout: 10000 },
    )

    console.log('looking for an email input');
    let emailInput = await page.$('#email-input, [name="username"], [type="email"]');

    if (emailInput) {
        await emailInput.type(username);
        emailInput = null;
    }

    console.log('waiting for a continue button');
    await page.waitForSelector(
        '.continue-btn',
        { timeout: 2000 },
    )

    console.log('looking for a continue button');
    let continueButton = await page.$('.continue-btn');

    if (continueButton) {
        console.log('there is a continue button');
        await continueButton.click();
        continueButton = null;
    }

    console.log('waiting for a password input');
    await page.waitForSelector(
        '#password, [name="password"], [type="password"]',
        { timeout: 5000 },
    )

    console.log('looking for password input');
    let passwordInput = await page.$('#password, [name="password"], [type="password"]');

    if (passwordInput) {
        console.log('there is a password input');
        await passwordInput.type(password);
        passwordInput = null;
    }

    console.log('waiting for a login button');


    console.log('looking for a login button');
    let logInButton = await page.locator('button ._button-login-password, [type="submit"], [name="action"]');

    if (logInButton) {
        console.log('there is a log in button');

        await logInButton.click();
        logInButton = null;

        console.log('clicked log in button');

        try {
            await page.waitForNetworkIdle({ timeout: 10000 });
        } catch (Error) {
            //
        }
    } else {
        console.log('there is no log in button');
    }
}

profileButton = await page.$('[data-testid="profile-button"]');

if (profileButton) {
    console.log('is logged in');
    profileButton = null;
} else {
    console.log('is not logged in');
}

let gettingStarted = await page.$('[data-testid="getting-started-button"]')

if (gettingStarted) {
    console.log('closing getting started modal');

    await gettingStarted.click();
    gettingStarted = null;

    await page.keyboard.down('Enter');
    await page.keyboard.up('Enter');
}

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

    await fileInput.uploadFile(filePath);
} else {
    console.log('there is no file input');
}

console.log('looking for attach button');

const attachDisabled = await page.evaluate(() => {
    let result = null;

    [...document.querySelectorAll('button')]
        .filter(b => b.hasAttribute('aria-label'))
        .forEach(function (b) {
            if (b.getAttribute('aria-label').toLowerCase().startsWith('attach files')) {
                result = b.disabled;
            }
        })

    return result;
})

if (attachDisabled) {
    await page.close();
    await browser.disconnect();

    // Postponed (Attaching files is not available)
    process.exit(-1);
}
//
// let textarea = await page.$('textarea');
//
// if (textarea) {
//     const prompt = 'Here is a photo of the dish. Please estimate calories, nutrients.'
//         + ' Please respond in JSON format (weight in grams): {"meal": "Name the meal","description":"Describe if meal is healthy or not.", "error": "Describe the error (might be no food on photo).","ingredients":[{"name":"Ingredient","serving_size":"1 medium sized","weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}],"total":{"weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}}.'
//         + ' If there is no photo then for now just return the example JSON. Please respond in language with code: uk.'
//         // + ' Please respond in JSON format (weight in grams): {"meal": "Name the meal","description":"Describe if meal is healthy or not.","ingredients":[{"name":"Ingredient","serving_size":"1 medium sized","weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}],"total":{"weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}}. For now just return the JSON example I provided.'
//         + '\n';
//
//     await textarea.type(prompt);
// }
//
// try {
//     console.log('Waiting for the stop streaming button to disappear...');
//     await page.waitForFunction(
//         (sel) => !document.querySelector(sel),
//         { timeout: 60000 }, // Timeout in milliseconds
//         'button[data-testid="stop-button"]'
//     );
//     console.log('Button disappeared');
// } catch (error) {
//     console.error('Button did not disappear within the timeout', error);
// }
//
// try {
//     await page.waitForNetworkIdle({ timeout: 10000 });
// } catch (Error) {
//     //
// }
//
// const jsonElement = await page.$('code.hljs.language-json');
//
// if (jsonElement) {
//     console.log('Found the JSON element');
//
//     // Get the inner text of the element
//     const jsonString = await page.evaluate(element => element.innerText, jsonElement);
//
//     try {
//         // Parse the JSON string into an object
//         const jsonData = JSON.parse(jsonString);
//         console.log('Parsed JSON:', jsonData);
//     } catch (error) {
//         console.error('Failed to parse JSON:', error);
//
//         await page.close();
//         await browser.disconnect();
//
//         process.exit(-2);
//     }
// } else {
//     console.log('JSON element not found');
//
//     process.exit(-3);
// }

// await page.close();
await browser.disconnect();
