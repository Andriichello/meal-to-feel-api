import puppeteer from 'puppeteer';
import PuppeteerExtra from "puppeteer-extra";
import Stealth from "puppeteer-extra-plugin-stealth";
import * as http from "node:http";

async function getWebSocketURL(port = 9222) {
    return new Promise((resolve, reject) => {
        const options = {
            hostname: '127.0.0.1',
            port: port,
            path: '/json/version',
            method: 'GET',
        };

        const req = http.request(options, (res) => {
            let data = '';
            res.on('data', (chunk) => (data += chunk));
            res.on('end', () => {
                try {
                    const parsed = JSON.parse(data);

                    resolve(parsed?.WebSocketDebuggerUrl ?? parsed?.webSocketDebuggerUrl)
                } catch (error) {
                    reject('Error parsing response: ' + error.message);
                }
            });
        });

        req.on('error', (error) => {
            reject('Request error: ' + error.message);
        });

        req.end();
    });
}

// Function to send the callback request
async function postCallback(protocol, host, port, path, json) {
    const options = {
        protocol: 'http:',
        hostname: host,
        port: port,
        path: path, // Include query string if present
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
    };

    if (!json['tried_at']) {
        json['tried_at'] = (new Date()).toISOString();
    }

    const data = JSON.stringify(json);


    // Set Content-Length header
    options.headers['Content-Length'] = Buffer.byteLength(data);

    console.log('options', options);
    console.log('data', data);

    // Make the request
    const req = http.request(options, (res) => {
        console.log(`Status: ${res.statusCode}`);
        res.setEncoding('utf8');
        res.on('end', () => {
            console.log('No more data in response.');
        });
    });

    // Handle errors
    req.on('error', (e) => {
        console.error(`Problem with request: ${e.message}`);
    });

    // Write data to request body
    req.write(data);

    // End the request
    req.end();
}

// Get arguments from process.argv (ignoring the first two default entries)
const args = process.argv.slice(2);

const options = {};

for (let i = 0; i < args.length; i++) {
    if (args[i].startsWith('--')) {
        const argName = args[i].slice(2); // Remove the '--' prefix
        options[argName] = args[i + 1] && !args[i + 1].startsWith('--') ? args[i + 1] : true;
    }
}

console.log({options})

const username = options['username'] ?? null;
const password = options['password'] ?? null;
const language = options['language'] ?? 'uk';
const fileId = options['file-id'] ?? null;
const filePath = options['file-path'] ?? null;
const debuggerPort = options['debugger-port'] ?? 9222;
const protocol = options['protocol'] ?? 'http';
const host = options['host'] ?? '127.0.0.1';
const hostPort = options['port'] ?? 8000;
const callbackPath = options['callback-path'] ?? '/api/puppeteer/callback';

if (!username || !password || !language || !fileId || !filePath || !debuggerPort || !callbackPath) {
    if (callbackPath) {
        const response = await postCallback(protocol, host, hostPort, callbackPath, {
            'status': 'Missing Args',
            'file_id': fileId,
            'username': username,
            'language': language,
        })

        console.log(response);
    }

    process.exit(-15);
}

const wsUrl = await getWebSocketURL(debuggerPort);

if (!wsUrl) {
    await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
        'status': 'No Browser',
        'file_id': Number.parseInt(fileId),
        'username': username,
        'language': language,
    })

    process.exit(-20);
}

PuppeteerExtra.use(Stealth());

// Launch the browser and open a new blank page
const browser = await puppeteer.connect({browserWSEndpoint: wsUrl});
const page = await browser.newPage();
await page.setViewport({width: 1080, height: 1024});

// Navigate the page to a URL.
await page.goto('https://chatgpt.com');

// Wait until everything loads.
await page.waitForNetworkIdle();

const timezone = await page.evaluate(() => {
    try {
        return Intl.DateTimeFormat().resolvedOptions().timeZone;
    } catch (error) {
        //
    }

    return null;
});

const now = await page.evaluate(() => {
    try {
        return (new Date()).toISOString();
    } catch (error) {
        //
    }

    return null;
});

let logIn = await page.$('[data-testid="welcome-login-button"]')
    ?? await page.$('[data-testid="login-button"]');

if (logIn) {
    console.log('there is a log in button');
    await logIn.click();
    logIn = null;

    console.log('waiting for an email input');
    await page.waitForSelector(
        '#email-input, [name="username"], [type="email"]',
        {timeout: 10000},
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
        {timeout: 2000},
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
        {timeout: 5000},
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
            await page.waitForNetworkIdle({timeout: 10000});
        } catch (Error) {
            //
        }
    } else {
        console.log('there is no log in button');
    }
}

let gettingStarted = await page.$('[data-testid="getting-started-button"]')

if (gettingStarted) {
    console.log('closing getting started modal');

    await gettingStarted.click();
    gettingStarted = null;

    await page.keyboard.down('Enter');
    await page.keyboard.up('Enter');
}

// Select the section element
const section = await page.$('section[data-testid="screen-sidebar"]');

// Retrieve the value of the --sidebar-leading-height custom property
const sidebarLeadingHeight = await page.evaluate((element) => {
    try {
        return getComputedStyle(element).getPropertyValue('--sidebar-leading-height').trim();
    } catch (error) {
        //
    }

    return null;
}, section);

// Check if the --sidebar-leading-height is 0px
if (sidebarLeadingHeight === '0px') {
    console.log('sidebar is closed');
    await page.mouse.click(30, 30, {delay: 100});

    try {
        await page.waitForNetworkIdle({timeout: 2000});
    } catch (Error) {
        //
    }
} else if (sidebarLeadingHeight !== null) {
    console.log('sidebar is opened');
}

let profileButton = await page.waitForSelector('[data-testid="profile-button"], [data-testid="accounts-profile-button"]');
let email = await page.evaluate(() => {
    let result = null;
    // Get all script tags
    const scripts = Array.from(document.querySelectorAll('script'));

    for (const script of scripts) {
        const content = script.textContent || script.innerHTML;

        // Check if the script content contains "email":
        if (content.includes('"email":')) {
            try {
                // Extract the JSON part of the script content
                const match = content.match(/"email":\s*"([^"]+)"/);

                if (match) {
                    result = match[1];
                }
            } catch (err) {
                console.error('Error parsing script content:', err);
            }
        }
    }

    return result; // Return null if no email found
});

console.log('email', email);

if (email) {
    if (email !== username) {
        profileButton = null;
        await page.goto('https://chatgpt.com/auth/logout');

        // Wait until everything loads.
        await page.waitForNetworkIdle();
    }
} else if (profileButton) {
    console.log('is logged in');
    await profileButton.click();

    email = await page.$eval(
        'div.popover div.ml-3.mr-2.py-2.text-sm.text-token-text-secondary',
        (element) => element.textContent.trim()
    );

    console.log(email);

    await profileButton.click();

    if (email !== username) {
        profileButton = null;
        await page.goto('https://chatgpt.com/auth/logout');

        // Wait until everything loads.
        await page.waitForNetworkIdle();
    }
}

if (!profileButton) {
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
        {timeout: 10000},
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
        {timeout: 2000},
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
        {timeout: 5000},
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
            await page.waitForNetworkIdle({timeout: 10000});
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

gettingStarted = await page.$('[data-testid="getting-started-button"]')

if (gettingStarted) {
    console.log('closing getting started modal');

    await gettingStarted.click();
    gettingStarted = null;

    await page.keyboard.down('Enter');
    await page.keyboard.up('Enter');
}

console.log('looking for try after');

const attachButton = await page.$('button[aria-label="Attach files is unavailable"]');

if (attachButton) {
    const isDisabled = await attachButton
        .evaluate(button => button.disabled);

    if (isDisabled) {
        await attachButton.hover();

        try {
            await page.waitForNetworkIdle({timeout: 1000});
        } catch (Error) {
            //
        }
    }

    const tryAfter = await page.evaluate(() => {
        let result = null;

        [...document.querySelectorAll('div div.text-token-text-primary div')]
            .forEach(function (div) {
                console.log(div.textContent);

                const match = (div.textContent ?? '')
                    .match(/try again after\s*(\d{1,2}:\d{2}\s?[APap][Mm])/)

                if (match) {
                    result = match[1];
                }
            });

        return result;
    });

    if (tryAfter) {
        console.log('now: ' + now);
        console.log('try after: ' + tryAfter);

        await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
            'status': 'Try After',
            'file_id': Number.parseInt(fileId),
            'username': username,
            'language': language,
            'timezone': timezone,
            'tried_at': now,
            'try_after': tryAfter,
        })

        await page.close();
        await browser.disconnect();

        // postponed
        process.exit(-5);
    }
}

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
});

if (attachDisabled) {
    await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
        'status': 'No Upload',
        'file_id': Number.parseInt(fileId),
        'username': username,
        'language': language,
        'timezone': timezone,
        'tried_at': now,
    })

    await page.close();
    await browser.disconnect();

    // Postponed (Attaching files is not available)
    process.exit(-1);
}

let fileInput = await page.$('input[type=file]');

if (fileInput) {
    console.log('there is a file input');
    await fileInput.uploadFile(filePath);
} else {
    console.log('there is no file input');
}

let textarea = await page.$('textarea');

if (textarea) {
    const prompt = 'Here is a photo of the dish. Please estimate calories, nutrients.'
        + ' Please respond in JSON format (weight in grams): {"meal": "Name the meal","description":"Describe if meal is healthy or not.", "error": "Describe the error (might be no food on photo).","ingredients":[{"name":"Ingredient","serving_size":"1 medium sized","weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}],"total":{"weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}}.'
        + ` Please respond in language with code: ${language ?? 'en'}. If there is food always estimate it and return JSON (even if there are no ingredients), don't ask for details.`
        // + ' Please respond in JSON format (weight in grams): {"meal": "Name the meal","description":"Describe if meal is healthy or not.","ingredients":[{"name":"Ingredient","serving_size":"1 medium sized","weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}],"total":{"weight":130.5,"calories":62,"carbohydrates":15.4,"fiber":3.1,"sugar":12.2,"protein":1.2,"fat":0.2}}. For now just return the JSON example I provided.'
        + '\n'; // Newline submits the form

    await textarea.type(prompt);
}

try {
    console.log('Waiting for the stop streaming button to disappear...');
    await page.waitForFunction(
        (sel) => !document.querySelector(sel),
        {timeout: 60000}, // Timeout in milliseconds
        'button[data-testid="stop-button"]'
    );
    console.log('Button disappeared');
} catch (error) {
    console.error('Button did not disappear within the timeout', error);

    await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
        'status': 'Timed Out',
        'file_id': Number.parseInt(fileId),
        'username': username,
        'language': language,
        'timezone': timezone,
        'tried_at': now,
    })
}

try {
    await page.waitForNetworkIdle({timeout: 10000});
} catch (Error) {
    //
}

const jsonElement = await page.$('code.hljs.language-json');

if (jsonElement) {
    console.log('Found the JSON element');

    // Get the inner text of the element
    const jsonString = await page.evaluate(element => element.innerText, jsonElement);

    try {
        // Parse the JSON string into an object
        const jsonData = JSON.parse(jsonString);
        console.log('Parsed JSON:', jsonData);

        await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
            'status': 'Success',
            'file_id': Number.parseInt(fileId),
            'username': username,
            'language': language,
            'payload': jsonData,
            'timezone': timezone,
            'tried_at': now,
        })
    } catch (error) {
        console.error('Failed to parse JSON:', error);

        await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
            'status': 'Parsing Fail',
            'file_id': Number.parseInt(fileId),
            'username': username,
            'language': language,
            'timezone': timezone,
            'tried_at': now,
        })

        await page.close();
        await browser.disconnect();

        process.exit(-2);
    }
} else {
    console.log('JSON element not found');

    await postCallback(protocol, host, Number.parseInt(hostPort), callbackPath, {
        'status': 'No JSON',
        'file_id': Number.parseInt(fileId),
        'username': username,
        'language': language,
        'timezone': timezone,
        'tried_at': now,
    })

    process.exit(-3);
}

await page.close();
await browser.disconnect();
