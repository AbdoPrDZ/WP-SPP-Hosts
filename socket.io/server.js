const { Command } = require('commander');
const http = require('http');
const jwt = require('jsonwebtoken')
const socketIo = require('socket.io');
const redis = require('redis');
const { exit } = require('process');
const express = require('express');
const cors = require('cors');

const program = new Command();
program.name('WP-SPP-Hosts Socket.IO Server')
       .description('The Socket.IO server for WP-SPP-Hosts')
       .version('1.0.0')
       .argument('<jwt_secret>', 'The jwt secret hash code')
       .option('-h, --host <value>', 'The server host', 'localhost')
       .option('-p, --port <value>', 'The server port', 3000)
       .option('-r, --redis-url <value>', 'The redis host url', 'redis://127.0.0.1:6379')
       .option('-d, --debug', 'output extra debugging')
       .option('--log-file <value>', 'The log file path')
       .option('--redis-prefix-key <value>', 'The redis prefix key', 'wp-spp-hosts')
       .parse(process.argv);

const options = program.opts();
const app = express();
app.use(cors());
const server = http.createServer(app);
const io = socketIo(server);

function log(level, ...messages) {
  if (!options.debug) return;

  if (!messages || messages.length == 0)
    messages = [level],
    level = 'info';

  switch (level.toLowerCase()) {
    case 'err':
    case 'error':
      console.error('err', `[${new Date().toISOString()}]:`, ...messages);
      break;
    case 'warn':
    case 'warning':
      console.warn(`[${new Date().toISOString()}]:`, ...messages);
      break
    case 'info':
    case 'inf':
      console.info(`[${new Date().toISOString()}]:`, ...messages);
      break;
    default:
      console.log(`[${new Date().toISOString()}] - [${level}]:`, ...messages);
      break;
  }

  if (options.logFile)
    messages = messages.map(m => typeof m === 'object' ? JSON.stringify(m, null, 2) : m),
    require('fs').appendFileSync(options.logFile, `[${new Date().toISOString()}] - [${level}]: ${messages.join(' ')}\n`);
}

async function main() {
  log('info', 'Starting...');
  log('info', 'Options', options);
  log('info', 'Arguments', program.args);

  log('info', 'Connecting to Redis...');
  const redisClient = redis.createClient({ url: options.redisUrl })
  redisClient.on('error', err => {
    log('err', 'Redis Client Error', err)
    exit(1)
  })
  redisClient.connect()
  log('info', 'Redis connected');

  log('info', 'Creating Socket.IO server...');
  io.use(async (socket, next) => {
    const remoteAddress = socket.handshake.address
    log('info', 'Authentication', socket.id)
    log('info', 'Authentication Remote Address', socket.id, remoteAddress)
    log('info', 'Authentication auth token', socket.id, socket.handshake.auth.token)
    const token = socket.handshake.auth.token;

    if (!token) {
      log('warn', 'Authentication error, no token provided', socket.id, socket.handshake.auth);
      return next(new Error('Authentication error, no token provided'));
    }

    try {
      const payload = jwt.verify(token, program.args[0]) // ex: { uid: 1, uip: '127.0.0.1', time: 1724699165 }

      log('info', 'Authentication payload', socket.id, payload)

      if (payload.uip === '127.0.0.1' && '::1' || payload.uip === remoteAddress) {
        // Token is valid, attach the user info to the socket object
        socket.user = payload;

        await redisClient.hSet(`${options.redisPrefixKey}:${payload.uid}`, 'uid', payload.uid);
        await redisClient.hSet(`${options.redisPrefixKey}:${payload.uid}`, 'uip', payload.uip);
        await redisClient.hSet(`${options.redisPrefixKey}:${payload.uid}`, 'connected', 1);
        await redisClient.hSet(`${options.redisPrefixKey}:${payload.uid}`, 'socketId', socket.id);

        next();
      } else return next(new Error('Invalid remote address'))
    } catch (err) {
      log('err', 'Authentication error', socket.id, err);

      next(new Error('Authentication error'));
    }
  });

  io.on('connection', (socket) => {
    log('info', 'a user connected', socket.id, socket.user);

    socket.on('select_token', async (token, jwtToken) => {
      log('info', 'Select token', token, jwtToken);

      await redisClient.hSet(`${options.redisPrefixKey}:${socket.user.uid}`, 'selected_token', jwtToken || '');

      log('info', 'Select token', await redisClient.hGetAll(`${options.redisPrefixKey}:${socket.user.uid}`))

      socket.emit('selected_token', token);
    });

    socket.on('disconnect', async () => {
      log('warn', 'user disconnected', socket.id, socket.user);

      await redisClient.hSet(`${options.redisPrefixKey}:${socket.user.uid}`, 'connected', 0);
      await redisClient.hSet(`${options.redisPrefixKey}:${socket.user.uid}`, 'selected_token', '');
    });
  });

  app.get('/', (req, res) => {
    const status = {
      running: true,
      port: options.port,
      host: options.host,
      redisConnected: redisClient.isOpen,
      connections: io.engine.clientsCount,
    };
    res.json(status);
  });

  server.listen(options.port, options.host, () => {
    log(`Socket.IO server running on ${options.host}:${options.port}`);
  });

  async function onShutdown() {
    log('warn', "Shutting down...");

    try {
      log('info', 'Clearing redis keys...');
      const keys = await redisClient.keys(`${options.redisPrefixKey}:*`);
      if (keys.length > 0) await redisClient.del(keys);
      log('info', `Redis ${keys.length} keys cleared`);
    } catch (err) {
      log('err', 'Failed to clear redis:', err);
    }

    try {
      log('info', 'Disconnecting redis...');
      await redisClient.disconnect()
      log('info', 'Redis disconnected');

      log('info', 'Closing server...');
      server.close();
      log('info', 'Server closed');

      process.exit(0);
    } catch (error) {
      log('err', error);
      process.exit(1)
    }
  }

  process.on('SIGINT', onShutdown);  // On Ctrl+C
  process.on('SIGTERM', onShutdown); // On termination signal
}

main()
