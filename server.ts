import http from 'http';
import fs from 'fs';
import { execSync } from 'child_process';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PORT = process.env.PORT || 3000;
const SOCKET_PATH = '/tmp/php-fpm.sock';
const TMP_CONF = '/tmp/php-fpm.conf';
const FPM_BIN = path.join(__dirname, 'server', 'php');
const FPM_TXT = path.join(__dirname, 'server', 'php.txt');

// 1. Ensure PHP executable exists (restore from php.txt if needed)
function ensurePhpBinary() {
  if (!fs.existsSync(FPM_BIN) && fs.existsSync(FPM_TXT)) {
    fs.copyFileSync(FPM_TXT, FPM_BIN);
    fs.chmodSync(FPM_BIN, 0o755);
  } else if (fs.existsSync(FPM_BIN)) {
    fs.chmodSync(FPM_BIN, 0o755);
  }
}

// 2. Start PHP 8.3.36 on Unix Socket with 25 Workers (inlined config)
function startPhp() {
  try {
    ensurePhpBinary();

    // Inline FPM Config with 25 workers in RAM (/tmp)
    const fpmConfig = `[global]
pid = /tmp/php-fpm.pid
error_log = /tmp/php-fpm.log
daemonize = yes

[www]
user = root
group = root
listen = ${SOCKET_PATH}
listen.owner = root
listen.group = root
listen.mode = 0666

pm = static
pm.max_children = 25
pm.max_requests = 1000

catch_workers_output = yes
clear_env = no
`;
    fs.writeFileSync(TMP_CONF, fpmConfig, 'utf8');

    // Remove any stale socket
    if (fs.existsSync(SOCKET_PATH)) {
      try { fs.unlinkSync(SOCKET_PATH); } catch {}
    }

    // Kill any existing php instance before launching
    try { execSync('pkill -9 -f "php: master process" 2>/dev/null || true'); } catch {}

    execSync(`${FPM_BIN} -y ${TMP_CONF} -R`, { stdio: 'inherit' });
    console.log('[PHP] PHP 8.3.36 running on Unix Socket with 25 workers.');
  } catch (err: any) {
    console.error('[PHP] Startup error:', err?.message || err);
  }
}

// 3. Inject .htaccess Front Controller & Unix Socket logic into Nginx
function applyNginxConfig() {
  const confPath = '/etc/nginx/nginx.conf';
  try {
    if (!fs.existsSync(confPath)) return;

    let content = fs.readFileSync(confPath, 'utf8');
    const tagStart = '# [XAXINO_CUSTOM_START]';
    const tagEnd = '# [XAXINO_CUSTOM_END]';

    if (content.includes(tagStart)) {
      console.log('[Nginx] Xaxino configuration already active.');
      return;
    }

    const customBlock = `${tagStart}
        # Exact .htaccess protection: Deny access to .env
        location ~ /\\.env {
            deny all;
            return 404;
        }

        # Exact .htaccess logic: Send requests to front controller
        location / {
            access_by_lua_file /etc/nginx/user_auth_verification.lua;
            root /app/applet/Xaxino;
            index index.php;
            try_files $uri $uri/ /index.php?$query_string;

            sub_filter "</head>" "<script src=\\"/_aistudio-iframe.js?v=1f94f900\\"></script></head>";
            sub_filter_once on;
            sub_filter_types text/html;

            add_header Content-Security-Policy "frame-ancestors '\''self'\'' https://*.google.com https://localhost.corp.google.com:26001;";
        }

        # PHP-FPM FastCGI Unix Domain Socket
        location ~ \\.php$ {
            access_by_lua_file /etc/nginx/user_auth_verification.lua;
            root /app/applet/Xaxino;
            fastcgi_pass unix:${SOCKET_PATH};
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;

            sub_filter "</head>" "<script src=\\"/_aistudio-iframe.js?v=1f94f900\\"></script></head>";
            sub_filter_once on;
            sub_filter_types text/html;

            add_header Content-Security-Policy "frame-ancestors '\''self'\'' https://*.google.com https://localhost.corp.google.com:26001;";
        }
        ${tagEnd}`;

    const locStart = content.indexOf('location / {');
    if (locStart !== -1) {
      let depth = 0;
      let locEnd = -1;
      for (let i = locStart; i < content.length; i++) {
        if (content[i] === '{') depth++;
        else if (content[i] === '}') {
          depth--;
          if (depth === 0) {
            locEnd = i + 1;
            break;
          }
        }
      }

      if (locEnd !== -1) {
        content = content.substring(0, locStart) + customBlock + content.substring(locEnd);
        fs.writeFileSync(confPath, content, 'utf8');
        execSync('nginx -t', { stdio: 'pipe' });
        execSync('nginx -s reload', { stdio: 'pipe' });
        console.log('[Nginx] Configured and reloaded with .htaccess and Unix socket.');
      }
    }
  } catch (err: any) {
    console.error('[Nginx] Notice:', err?.message || err);
  }
}

// Boot services
startPhp();
applyNginxConfig();

// Keep port 3000 alive and listening for container health
const server = http.createServer((req, res) => {
  res.writeHead(200, { 'Content-Type': 'application/json' });
  res.end(JSON.stringify({ status: 'running', php: '8.3.36-fpm', workers: 25, socket: SOCKET_PATH }));
});

server.listen(PORT, () => {
  console.log(`Server listening on port ${PORT}`);
});
