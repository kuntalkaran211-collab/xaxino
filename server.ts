import express from 'express';
import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
// @ts-ignore
import fastcgi from 'fastcgi-client';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const PORT = parseInt(process.env.PORT || '3000', 10);
const SOCKET_PATH = '/tmp/php-fpm.sock';
const TMP_CONF = '/tmp/php-fpm.conf';
const FPM_BIN = path.join(__dirname, 'server', 'php');
const FPM_TXT = path.join(__dirname, 'server', 'php.txt');

// 1. Detect active project root folder
function getAppRoot(): string {
  if (fs.existsSync(path.join(__dirname, 'xaxino-3.8', 'index.php'))) {
    return path.join(__dirname, 'xaxino-3.8');
  }
  if (fs.existsSync(path.join(__dirname, 'Xaxino', 'index.php'))) {
    return path.join(__dirname, 'Xaxino');
  }
  if (fs.existsSync(path.join(__dirname, 'xaxino', 'index.php'))) {
    return path.join(__dirname, 'xaxino');
  }
  return __dirname;
}

const APP_ROOT = getAppRoot();

// 2. Ensure PHP executable exists
function ensurePhpBinary() {
  if (!fs.existsSync(FPM_BIN) && fs.existsSync(FPM_TXT)) {
    fs.copyFileSync(FPM_TXT, FPM_BIN);
    fs.chmodSync(FPM_BIN, 0o755);
  } else if (fs.existsSync(FPM_BIN)) {
    fs.chmodSync(FPM_BIN, 0o755);
  }
}

// 3. Start PHP 8.3.36 on Unix Socket with 25 Workers
function startPhp() {
  try {
    ensurePhpBinary();

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

    if (fs.existsSync(SOCKET_PATH)) {
      try { fs.unlinkSync(SOCKET_PATH); } catch {}
    }

    try { execSync('pkill -9 -f "php: master process" 2>/dev/null || true'); } catch {}

    execSync(`${FPM_BIN} -y ${TMP_CONF} -R`, { stdio: 'inherit' });
    console.log('[PHP] PHP 8.3.36 running on Unix Socket with 25 workers.');
  } catch (err: any) {
    console.error('[PHP] Startup error:', err?.message || err);
  }
}

// 4. Update Nginx configuration if present
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
        location ~ /\\.env {
            deny all;
            return 404;
        }

        location / {
            access_by_lua_file /etc/nginx/user_auth_verification.lua;
            root ${APP_ROOT};
            index index.php;
            try_files $uri $uri/ /index.php?$query_string;

            sub_filter "</head>" "<script src=\\"/_aistudio-iframe.js?v=1f94f900\\"></script></head>";
            sub_filter_once on;
            sub_filter_types text/html;

            add_header Content-Security-Policy "frame-ancestors '\''self'\'' https://*.google.com https://localhost.corp.google.com:26001;";
        }

        location ~ \\.php$ {
            access_by_lua_file /etc/nginx/user_auth_verification.lua;
            root ${APP_ROOT};
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
        console.log('[Nginx] Configured and reloaded successfully.');
      }
    }
  } catch (err: any) {
    console.error('[Nginx] Notice:', err?.message || err);
  }
}

// Start PHP and configure Nginx
startPhp();
applyNginxConfig();

// Initialize FastCGI Client for Node.js
const fcgiClient = fastcgi({
  sockFile: SOCKET_PATH,
  maxConns: 25,
  maxReqs: 1000,
});

const app = express();

// Disable x-powered-by to allow PHP header
app.disable('x-powered-by');

// Block access to .env files (Exact .htaccess rule)
app.use((req, res, next) => {
  if (req.path.includes('.env')) {
    return res.status(404).send('Not Found');
  }
  next();
});

// Serve static assets directly for ultra-fast performance
app.use('/assets', express.static(path.join(APP_ROOT, 'assets'), {
  maxAge: '30d',
  immutable: true,
}));

// Fallback for installer assets
if (fs.existsSync(path.join(APP_ROOT, 'install', 'assets'))) {
  app.use('/install/assets', express.static(path.join(APP_ROOT, 'install', 'assets'), {
    maxAge: '30d',
    immutable: true,
  }));
}

// FastCGI Handler for dynamic requests
app.all('*', (req, res) => {
  // Check if requested URL points directly to an existing static file
  const staticFilePath = path.join(APP_ROOT, req.path);
  if (fs.existsSync(staticFilePath) && fs.statSync(staticFilePath).isFile() && !req.path.endsWith('.php')) {
    return res.sendFile(staticFilePath);
  }

  // Determine script filename and name
  let scriptFilename = path.join(APP_ROOT, 'index.php');
  let scriptName = '/index.php';

  if (req.path.startsWith('/install') && fs.existsSync(path.join(APP_ROOT, 'install', 'index.php'))) {
    scriptFilename = path.join(APP_ROOT, 'install', 'index.php');
    scriptName = '/install/index.php';
  }

  const host = req.headers.host || 'localhost';
  const queryStr = req.url.includes('?') ? req.url.split('?')[1] : '';

  const params: Record<string, string> = {
    REQUEST_METHOD: req.method,
    SCRIPT_FILENAME: scriptFilename,
    SCRIPT_NAME: scriptName,
    REQUEST_URI: req.originalUrl || req.url,
    DOCUMENT_ROOT: APP_ROOT,
    SERVER_SOFTWARE: 'Node-FastCGI-Bridge',
    REMOTE_ADDR: req.socket.remoteAddress || '127.0.0.1',
    SERVER_PROTOCOL: `HTTP/${req.httpVersion}`,
    SERVER_NAME: host.split(':')[0],
    SERVER_PORT: (host.split(':')[1] || '80'),
    HTTP_HOST: host,
    QUERY_STRING: queryStr,
    CONTENT_TYPE: req.headers['content-type'] || '',
    CONTENT_LENGTH: req.headers['content-length'] || '',
  };

  // Pass incoming HTTP headers as FastCGI HTTP_* params
  for (const [key, value] of Object.entries(req.headers)) {
    if (typeof value === 'string') {
      const headerKey = `HTTP_${key.toUpperCase().replace(/-/g, '_')}`;
      if (!params[headerKey]) {
        params[headerKey] = value;
      }
    }
  }

  fcgiClient.request(params, (err: any, fpmReq: any) => {
    if (err) {
      console.error('[FastCGI Bridge] Error:', err);
      return res.status(502).send('Bad Gateway (FastCGI Bridge Error)');
    }

    const chunks: Buffer[] = [];
    fpmReq.stdout.on('data', (chunk: Buffer) => {
      chunks.push(chunk);
    });

    fpmReq.stderr.on('data', (chunk: Buffer) => {
      console.warn('[PHP-FPM Log]', chunk.toString().trim());
    });

    fpmReq.stdout.on('end', () => {
      const fullBuffer = Buffer.concat(chunks);
      const headerEndIndex = fullBuffer.indexOf('\r\n\r\n');

      if (headerEndIndex === -1) {
        return res.status(500).send('Invalid FastCGI response');
      }

      const rawHeaders = fullBuffer.subarray(0, headerEndIndex).toString('utf8');
      let body = fullBuffer.subarray(headerEndIndex + 4);

      let statusCode = 200;
      const headerLines = rawHeaders.split('\r\n');

      for (const line of headerLines) {
        const colonIndex = line.indexOf(':');
        if (colonIndex !== -1) {
          const key = line.substring(0, colonIndex).trim();
          const val = line.substring(colonIndex + 1).trim();

          if (key.toLowerCase() === 'status') {
            statusCode = parseInt(val.split(' ')[0], 10) || 200;
          } else {
            res.setHeader(key, val);
          }
        }
      }

      // Ensure AI Studio CSP header
      res.setHeader(
        'Content-Security-Policy',
        "frame-ancestors 'self' https://*.google.com https://localhost.corp.google.com:26001;"
      );

      // Inject AI Studio iframe helper into HTML pages if present
      const contentType = res.getHeader('content-type') || '';
      if (String(contentType).includes('text/html')) {
        let htmlStr = body.toString('utf8');
        if (htmlStr.includes('</head>')) {
          htmlStr = htmlStr.replace(
            '</head>',
            '<script src="/_aistudio-iframe.js?v=1f94f900"></script></head>'
          );
          body = Buffer.from(htmlStr, 'utf8');
        }
      }

      res.status(statusCode).send(body);
    });

    // Pipe request body into FastCGI stdin
    if (req.method === 'POST' || req.method === 'PUT' || req.method === 'PATCH') {
      req.pipe(fpmReq.stdin);
    } else {
      fpmReq.stdin.end();
    }
  });
});

app.listen(PORT, '0.0.0.0', () => {
  console.log(`[FastCGI Bridge] Server listening on http://0.0.0.0:${PORT}`);
  console.log(`[FastCGI Bridge] Forwarding all traffic to PHP-FPM Unix Socket at ${SOCKET_PATH}`);
});
