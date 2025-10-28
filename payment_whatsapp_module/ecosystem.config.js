module.exports = {
  apps: [
    {
      name: 'wa-gateway',
      script: 'server.js',
      cwd: __dirname,
      env: { PORT: 3001 },
      watch: false,
      autorestart: true,
      max_restarts: 10
    }
  ]
};
