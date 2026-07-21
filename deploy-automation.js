const puppeteer = require('puppeteer-core');
const fs = require('fs');
const path = require('path');

const BRAVE_PATH = 'C:\\Users\\ADMIN\\AppData\\Local\\BraveSoftware\\Brave-Browser\\Application\\brave.exe';
const USER_DATA = 'C:\\Users\\ADMIN\\AppData\\Local\\BraveSoftware\\Brave-Browser\\User Data';
const CREDS_FILE = path.join(__dirname, '.brms-creds.json');

async function sleep(ms) { return new Promise(r => setTimeout(r, ms)); }

async function getPageText(page, selector) {
    try { return await page.$eval(selector, el => el.textContent.trim()); }
    catch { return null; }
}

async function waitAndClick(page, selector, timeout = 10000) {
    await page.waitForSelector(selector, { timeout });
    await page.click(selector);
}

async function main() {
    console.log('=== Dhanalakshmi Boating — Full Auto-Deploy ===\n');

    const browser = await puppeteer.launch({
        executablePath: BRAVE_PATH,
        userDataDir: USER_DATA,
        headless: false,
        args: ['--remote-debugging-port=0', '--no-first-run', '--window-size=1366,900']
    });

    const page = await browser.newPage();
    await page.setViewport({ width: 1366, height: 900 });

    // ─────────────────── PART 1: NEON ───────────────────
    console.log('[1/5] Setting up Neon PostgreSQL...');
    await page.goto('https://console.neon.tech', { waitUntil: 'networkidle0', timeout: 60000 });
    await sleep(3000);

    // Check if logged in
    let loggedIn = !page.url().includes('login') && !page.url().includes('signin');
    if (!loggedIn) {
        console.log('  ⚠ Please log into Neon in the browser window.');
        console.log('  Waiting...');
        try {
            await page.waitForFunction(() => !window.location.href.includes('login') && !window.location.href.includes('signin'), { timeout: 120000 });
            console.log('  ✅ Logged in');
        } catch { console.log('  ⚠ Continuing anyway...'); }
    } else console.log('  ✅ Already logged in');

    await sleep(2000);

    // Navigate to create project
    await page.goto('https://console.neon.tech/app/projects', { waitUntil: 'networkidle0', timeout: 30000 });
    await sleep(2000);

    // Check for existing projects
    const projectsExist = await page.evaluate(() => {
        return document.body.innerText.includes('brms') || document.body.innerText.includes('BRMS');
    });

    if (!projectsExist) {
        console.log('  Creating new project "brms-db"...');
        try {
            const newBtn = await page.$('button:has-text("New Project"), a:has-text("New Project"), button:has-text("Create"), span:has-text("New Project")');
            if (newBtn) await newBtn.click();
            await sleep(2000);
            
            // Fill project name
            const nameInput = await page.$('input[placeholder*="Project"], input[name*="name"], input[id*="name"]');
            if (nameInput) {
                await nameInput.click({ clickCount: 3 });
                await nameInput.type('brms-db');
            }
            
            // Click create/submit
            const createBtn = await page.$('button:has-text("Create"), button:has-text("Submit"), button:has-text("Create project")');
            if (createBtn) await createBtn.click();
            
            console.log('  ⏳ Waiting for project creation...');
            await sleep(10000);
        } catch (e) { console.log(`  ⚠ Project creation note: ${e.message}`); }
    } else console.log('  ✅ Project already exists');

    // Get connection string from dashboard
    console.log('  Retrieving connection details...');
    await page.goto('https://console.neon.tech/app/projects', { waitUntil: 'networkidle0', timeout: 30000 });
    await sleep(3000);
    
    // Click on first project
    try {
        const projectLink = await page.$('a[href*="/branches"]');
        if (projectLink) await projectLink.click();
        await sleep(4000);
    } catch {}

    // Try to fetch API key instead for programmatic access
    console.log('  Fetching connection URI via Neon API...');
    
    // Get API key from the page if available via localStorage
    const neonApiKey = await page.evaluate(() => {
        try {
            const data = JSON.parse(localStorage.getItem('neon_api_key') || '{}');
            return data.key || null;
        } catch { return null; }
    });

    if (neonApiKey) {
        console.log('  Found stored API key');
        const res = await fetch('https://api.neon.tech/v2/projects', {
            headers: { 'Authorization': `Bearer ${neonApiKey}`, 'Accept': 'application/json' }
        });
        if (res.ok) {
            const data = await res.json();
            if (data.projects?.length > 0) {
                const p = data.projects[0];
                const connRes = await fetch(`https://api.neon.tech/v2/projects/${p.id}/connection_uri`, {
                    headers: { 'Authorization': `Bearer ${neonApiKey}` }
                });
                if (connRes.ok) {
                    const connData = await connRes.json();
                    console.log(`  ✅ Database URL obtained`);
                    fs.writeFileSync(CREDS_FILE, JSON.stringify({
                        neonConnectionUri: connData.connection_uri,
                        neonProjectId: p.id
                    }, null, 2));
                }
            }
        }
    } else {
        console.log('  ⚠ No API key found. Please:');
        console.log('     1. Go to https://console.neon.tech/app/settings/api-keys');
        console.log('     2. Create an API key named "deploy"');
        console.log('     3. Copy the key');
        console.log('     4. Run: php artisan brms:save-neon-key <your-api-key>\n');
    }

    // ─────────────────── PART 2: RENDER ───────────────────
    console.log('\n[2/5] Setting up Render...');
    await page.goto('https://dashboard.render.com', { waitUntil: 'networkidle0', timeout: 30000 });
    await sleep(3000);

    loggedIn = !page.url().includes('login') && !page.url().includes('signin');
    if (!loggedIn) {
        console.log('  ⚠ Please log into Render.');
        console.log('  Waiting...');
        try {
            await page.waitForFunction(() => !window.location.href.includes('login'), { timeout: 120000 });
        } catch {}
    }
    console.log('  ✅ Logged into Render');

    // Check repo connectivity on Render
    await page.goto('https://dashboard.render.com/select-repo', { waitUntil: 'networkidle0', timeout: 30000 });
    await sleep(3000);

    console.log('\n[3/5] Render Web Service Configuration');
    console.log('  ℹ In the browser, please:');
    console.log('    1. Find and select your GitHub repo: 03Hari-Krishna-Kumar-div/boating');
    console.log('    2. Click "Connect"');
    console.log('');
    console.log('  ╔══════════════════════════════════════════════════════════╗');
    console.log('  ║  WEB SERVICE SETTINGS (copy these)                      ║');
    console.log('  ╠══════════════════════════════════════════════════════════╣');
    console.log('  ║  Name:            boating                               ║');
    console.log('  ║  Region:          Singapore                             ║');
    console.log('  ║  Branch:          master                                ║');
    console.log('  ║  Runtime:         PHP (detected automatically)          ║');
    console.log('  ║  Plan:            Free                                  ║');
    console.log('  ╟──────────────────────────────────────────────────────────╢');
    console.log('  ║  BUILD COMMAND:                                         ║');
    console.log('  ║  composer install --no-dev --optimize-autoloader &&     ║');
    console.log('  ║  npm ci && npm run build && php artisan config:cache && ║');
    console.log('  ║  php artisan route:cache && php artisan view:cache      ║');
    console.log('  ╟──────────────────────────────────────────────────────────╢');
    console.log('  ║  START COMMAND:                                        ║');
    console.log('  ║  php artisan serve --host=0.0.0.0 --port=$PORT          ║');
    console.log('  ╟──────────────────────────────────────────────────────────╢');
    console.log('  ║  HEALTH CHECK PATH: /up                                 ║');
    console.log('  ╚══════════════════════════════════════════════════════════╝');
    console.log('');

    // Read creds file if it exists
    let dbUrl = '';
    try {
        const creds = JSON.parse(fs.readFileSync(CREDS_FILE, 'utf8'));
        dbUrl = creds.neonConnectionUri || '';
    } catch {}

    if (!dbUrl) {
        console.log('  ⚠ No Neon connection URI available yet.');
        console.log('  You will need to manually create the Neon DB and paste the connection URI.');
        console.log('  If you have the connection string, enter it below.\n');
    } else {
        console.log('  ✅ Neon DB connection available');
        console.log(`  Connection URI: ${dbUrl}\n`);
        
        // Parse the connection URI for env vars
        try {
            const url = new URL(dbUrl);
            const envVars = {
                APP_ENV: 'production',
                APP_DEBUG: 'false',
                APP_KEY: 'base64:/fl24IJ8McO/Ueh0K8LIRK4oFJ7Cq2vPbCvflIZ07Qo=',
                APP_URL: 'https://boating.onrender.com',
                DB_CONNECTION: 'pgsql',
                DB_HOST: url.hostname,
                DB_PORT: url.port || '5432',
                DB_DATABASE: url.pathname.replace('/', ''),
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
            
            console.log('  ENVIRONMENT VARIABLES TO ADD ON RENDER:');
            console.log('  ┌──────────────────────────────────────────────────────┐');
            for (const [k, v] of Object.entries(envVars)) {
                console.log(`  │ ${k.padEnd(30)} = ${v}`);
            }
            console.log('  └──────────────────────────────────────────────────────┘');
            console.log('\n  Add these when Render prompts for environment variables.\n');
            
            // Save env vars for later use
            fs.writeFileSync(CREDS_FILE, JSON.stringify({ ...JSON.parse(fs.readFileSync(CREDS_FILE, 'utf8')), envVars }, null, 2));
        } catch (e) {
            console.log(`  ⚠ Could not parse connection URI: ${e.message}`);
        }
    }

    // ─────────────── PART 3: Wait for deployment ───────────
    console.log('\n[4/5] Deployment Monitoring');
    console.log('  After creating the Web Service:');
    console.log('    1. Render will automatically start building and deploying');
    console.log('    2. Watch the build logs in the browser');
    console.log('    3. Wait for the green checkmark ✅');
    console.log('');
    console.log('  First build takes 3-5 minutes (Composer + npm installs).');

    // ─────────── PART 4: Migrations ───────────
    console.log('\n[5/5] Database Migrations');
    console.log('  After successful deployment:');
    console.log('    1. Go to your Render dashboard → boating service');
    console.log('    2. Click "Shell" tab');
    console.log('    3. Run: php artisan migrate --force');
    console.log('    4. Verify: php artisan db:show');
    console.log('');
    console.log('  If shell is not available on free tier:');
    console.log('    Add this to the START command temporarily:');
    console.log('    php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT');
    console.log('    Then remove after first deploy.');
    console.log('\n=== Deployment automation script complete ===\n');

    // Keep browser open so user can follow along
    console.log('Browser will remain open. Press Ctrl+C in terminal to close.');
}

main().catch(err => {
    console.error('Error:', err.message);
    process.exit(1);
});
