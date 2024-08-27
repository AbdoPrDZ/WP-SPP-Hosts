const http = require('http');
const jwt = require('jsonwebtoken')
const socketIo = require('socket.io');
const redis = require('redis');
const { exit } = require('process');

async function main() {
  const redisClient = redis.createClient()
  redisClient.on('error', err => {
    console.log('Redis Client Error', err)
    exit(1)
  })
  redisClient.connect()

  console.log(await redisClient.hGetAll(`wp-spp-hosts`))

  // await redisClient.del('wp-spp-hosts')

  const server = http.createServer();
  const io = socketIo(server);

  io.use(async (socket, next) => {
    const remoteAddress = socket.handshake.address
    console.log('Remote Address', remoteAddress)
    // console.log('Socket', socket)
    console.log('Middleware', socket.handshake.auth.token)
    const token = socket.handshake.auth.token;

    if (!token)
      return next(new Error('Authentication error'));

    // Use the same secret key as in your WordPress config
    const secretKey = 'FAA200F027797FD16C7A134D150F2E60C4A0C68FAAF65B03A3B892DC9DCAE0C6';

    try {
      const payload = jwt.verify(token, secretKey) // ex: { uid: 3, uip: '127.0.0.1', time: 1724699165 }

      console.log('payload', payload)

      if (payload.uip === '127.0.0.1' && '::1' || payload.uip === remoteAddress) {
        // Token is valid, attach the user info to the socket object
        socket.user = payload;

        await redisClient.hSet(`wp-spp-hosts:${payload.uid}`, 'uid', payload.uid);
        await redisClient.hSet(`wp-spp-hosts:${payload.uid}`, 'uip', payload.uip);
        await redisClient.hSet(`wp-spp-hosts:${payload.uid}`, 'connected', 1);
        await redisClient.hSet(`wp-spp-hosts:${payload.uid}`, 'socketId', socket.id);

        next();
      } else return next(new Error('Invalid remote address'))
    } catch (err) {
      console.error(err);

      next(new Error('Authentication error'));
    }
  });

  io.on('connection', (socket) => {
    console.log('a user connected', socket.id, socket.user);

    redisClient.hGetAll(`wp-spp-hosts:${socket.user.uid}`).then(v => console.log(v))

    socket.on('select_token', async (token, jwtToken) => {
      console.log('Select token', token, jwtToken);

      await redisClient.hSet(`wp-spp-hosts:${socket.user.uid}`, 'selected_token', jwtToken || '');

      console.log('Select token', await redisClient.hGetAll(`wp-spp-hosts:${socket.user.uid}`))

      socket.emit('selected_token', token);
    });

    socket.on('disconnect', async () => {
      console.log('user disconnected');

      await redisClient.hSet(`wp-spp-hosts:${socket.user.uid}`, 'connected', 0);
      await redisClient.hSet(`wp-spp-hosts:${socket.user.uid}`, 'selected_token', '');

      console.log('user disconnected', await redisClient.hGetAll(`wp-spp-hosts:${socket.user.uid}`))
    });
  });

  server.listen(3000, () => {
    console.log('Socket.IO server running on port 3000');
  });

  async function onShutdown() {
    console.log("Shutting down...");
    try {
      try {
        const keys = await redisClient.keys('wp-spp-hosts:*'); // Get all matching keys
        if (keys.length > 0) {
          await redisClient.del(keys); // Delete the specific keys
          console.log('Specific Redis keys cleared on server shutdown');
        }
      } catch (err) {
        console.error('Failed to clear specific Redis keys:', err);
      }
      await redisClient.disconnect()
      process.exit(0);
    } catch (error) {
      console.error(error);
      process.exit(1)
    }
  }

  process.on('SIGINT', onShutdown);  // On Ctrl+C
  process.on('SIGTERM', onShutdown); // On termination signal
}

main()
