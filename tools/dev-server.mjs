import http from 'node:http';
import { spawn } from 'node:child_process';

const phpBinary = process.env.PHP_BINARY || '/opt/homebrew/bin/php';
const phpHost = '127.0.0.1';
const phpPort = Number(process.env.JX_PHP_PORT || 8011);
const publicAddress = '127.0.0.1';
const publicPort = Number(process.env.PORT || 8000);
const publicPorts = Array.from(new Set([publicPort, 8010]));

const php = spawn(phpBinary, ['-S', `${phpHost}:${phpPort}`, '-t', 'public', 'public/index.php'], {
  cwd: new URL('..', import.meta.url),
  stdio: ['ignore', 'pipe', 'pipe'],
});

php.stdout.on('data', (chunk) => process.stdout.write(chunk));
php.stderr.on('data', (chunk) => {
  const text = chunk.toString();
  if (!text.includes('Development Server') && !text.includes('Document root')) {
    process.stderr.write(chunk);
  }
});
php.on('exit', (code) => {
  if (code !== 0 && code !== null) {
    console.error(`PHP server exited with code ${code}`);
  }
  process.exit(code ?? 0);
});

const proxies = [];
const createProxy = (port) => http.createServer((req, res) => {
  const upstream = http.request({
    host: phpHost,
    port: phpPort,
    method: req.method,
    path: req.url,
    headers: req.headers,
  }, (upstreamRes) => {
    res.writeHead(upstreamRes.statusCode || 500, upstreamRes.headers);
    upstreamRes.pipe(res);
  });

  upstream.on('error', (error) => {
    res.writeHead(502, { 'Content-Type': 'text/plain; charset=utf-8' });
    res.end(`PHP preview server is not ready: ${error.message}`);
  });

  req.pipe(upstream);
});

for (const port of publicPorts) {
  const proxy = createProxy(port);
  proxy.on('error', (error) => {
    if (error.code === 'EADDRINUSE') {
      console.warn(`Preview port ${port} is already in use, skipped.`);
      return;
    }
    console.error(`Preview proxy error on port ${port}: ${error.message}`);
  });
  proxy.listen(port, publicAddress, () => {
    console.log(`Preview server running at http://${publicAddress}:${port}`);
  });
  proxies.push(proxy);
}

if (publicPorts.length > 1) {
  console.log(`Admin aliases: http://${publicAddress}:8000/jxdjadmin and http://${publicAddress}:8010/jxdjadmin`);
}

let shuttingDown = false;
const shutdown = () => {
  if (shuttingDown) {
    return;
  }
  shuttingDown = true;
  proxies.forEach((proxy) => proxy.close());
  php.kill('SIGTERM');
};

process.on('SIGINT', shutdown);
process.on('SIGTERM', shutdown);
