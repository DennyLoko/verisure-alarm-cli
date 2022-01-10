const { createLogger, format, transports } = require('winston');
const winston = require('winston');

const { combine, timestamp, printf } = format;

const cliFormat = printf(({ level, message, timestamp }) => {
  return `[${level}] ${timestamp} ${message}`;
});

module.exports = createLogger({
  level: 'debug',
  levels: winston.config.syslog.levels,
  format: combine(
    timestamp(),
    cliFormat
  ),
  transports: [
    new transports.Console(),
  ],
});
