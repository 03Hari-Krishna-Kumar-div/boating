const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BRAVE = 'C:\\Users\\ADMIN\\AppData\\Local\\BraveSoftware\\Brave-Browser\\Application\\brave.exe';
const USER_DATA = 'C:\\Users\\ADMIN\\AppData\\Local\\BraveSoftware\\Brave-Browser\\User Data';
const OUT = path.join(__dirname, '.brms-creds.json');

function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

(async () => {
    const browser = await puppeteer.launch({
        executablePath: BRAVE,
        userDataDir: USER_DATA,
        headless: false,
        args: ['--no-first-run', '--window-size=1280,900']
    });
    const [page] = await browser.pages();
    await page.setViewport({ width: 1280, height: 900 });

    // ─── NEON: Get connection string from project dashboard ───
    console.log('=== NEON: Getting database connection string ===');
    await page.goto('https://console.neon.tech/app/projects', { waitUntil: 'networkidle0', timeout: 60000 });
    await sleep(4000);
    console.log('URL:', page.url());

    // Click first project link
    const projectLink = await page.$('a[href*="/branches"]');
    if (projectLink) {
        await projectLink.click();
        await sleep(5000);
        console.log('Project page URL:', page.url());
    }

    // Look for connection string in the page
    let connString = await page.evaluate(() => {
        const body = document.body.innerText;
        // Look for PostgreSQL connection string pattern
        const match = body.match(/postgresql:\/\/[^\s'"]+/);
        return match ? match[0] : null;
    });

    if (!connString) {
        // Try clicking connection details / show
        console.log('Looking for connection details button...');
        const btns = await page.$$('button, a, span');
        for (const btn of btns) {
            const text = await btn.evaluate(el => el.textContent.toLowerCase());
            if (text.includes('connect') || text.includes('connection') || text.includes('string') || text.includes('show')) {
                await btn.click();
                await sleep(2000);
                break;
            }
        }

        connString = await page.evaluate(() => {
            const body = document.body.innerText;
            const match = body.match(/postgresql:\/\/[^\s'"]+/);
            return match ? match[0] : null;
        });
    }

    if (connString) {
        console.log('✅ Found connection string');
        fs.writeFileSync(OUT, JSON.stringify({ connectionUri: connString }, null, 2));

        // Parse and show env vars
        const url = new URL(connString);
        console.log('\n=== ENVIRONMENT VARIABLES FOR RENDER ===');
        const envVars = {
            APP_ENV: 'production',
            APP_DEBUG: 'false',
            APP_KEY: 'base64:/fl24IJ8McO/Ueh0K8LIRK4oFJ7Cq2vPbCvflIZ07Qo=',
            APP_URL: 'https://boating.onrender.com',
            DB_CONNECTION: 'pgsql',
            DB_HOST: url.hostname,
            DB_PORT: url.port || '5432',
            DB_DATABASE: url.pathname.replace('/', '').split('?')[0],
            DB_USERNAME: url.username,
            DB_PASSWORD: url.password,
            DB_SSLMODE: 'require',
            QUEUE_CONNECTION: 'sync',
            SESSION_DRIVER: 'cookie',
            SESSION_SECURE_COOKIE: 'true',
            SESSION_DOMAIN: '.onrender.com',
            CACHE_STORE: 'file',
            SANCTUM_STATEFUL_DOMAINS: 'boating.onrender.com',
            MAIL_MAILER: 'log',
            NODE_VERSION: '20',
            APP_MAINTENANCE_DRIVER: 'cache',
            LOG_LEVEL: 'error'
        };
        for (const [k, v] of Object.entries(envVars)) {
            console.log(`${k}=${v}`);
        }

        // Save env vars too
        const data = JSON.parse(fs.readFileSync(OUT, 'utf8'));
        data.envVars = envVars;
        fs.writeFileSync(OUT, JSON.stringify(data, null, 2));
    } else {
        console.log('❌ Could not find connection string');
        console.log('Please copy the connection string from the Neon dashboard manually.');
        console.log('The browser is open - navigate to your project and copy the connection details.');
    }

    // ─── RENDER: Navigate to dashboard ───
    console.log('\n=== RENDER: Setting up service ===');
    await page.goto('https://dashboard.render.com', { waitUntil: 'networkidle0', timeout: 60000 });
    await sleep(4000);
    console.log('Render URL:', page.url());

    console.log('\n=== INSTRUCTIONS FOR YOU ===');
    if (connString) {
        console.log('✅ Neon connection string captured and saved.');
        console.log('');
        console.log('Now go to Render dashboard in the browser:');
        console.log('  1. Click "New +" → "Web Service"');
        console.log('  2. Select your GitHub repo: 03Hari-Krishna-Kumar-div/boating');
        console.log('  3. Use these settings:');
        console.log('     Name: boating');
        console.log('     Region: Singapore');
        console.log('     Branch: master');
        console.log('     Plan: Free');
        console.log('     Build Command:');
        console.log('       composer install --no-dev --optimize-autoloader && npm ci && npm run build && php artisan config:cache && php artisan route:cache && php artisan view:cache');
        console.log('     Start Command:');
        console.log('       php artisan serve --host=0.0.0.0 --port=$PORT');
        console.log('     Health Check Path: /up');
        console.log('');
        console.log('  4. Add ALL the environment variables shown above');
        console.log('  5. Click "Create Web Service"');
        console.log('');
        console.log('  6. After deployment completes, go to Shell tab and run:');
        console.log('     php artisan migrate --force');
    } else {
        console.log('❌ No Neon connection string available.');
        console.log('Get it from the browser window and paste here.');
    }

    console.log('\nBrowser remains open. Close this terminal when done.');
})().catch(err => { console.error('FATAL:', err); process.exit(1); });
