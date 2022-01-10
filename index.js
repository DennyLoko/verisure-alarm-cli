const { Command } = require('commander');
const dotenv = require('dotenv');
const { table } = require('table');

const logger = require('./src/util/logger/cli');
const Verisure = require('./src/verisure');

const result = dotenv.config();

if (result.error) {
  logger.log('crit', result.error);
}

const program = new Command();

program
  .version('0.1.0')
  .description('Tiny CLI to activate/deactivate Verisure\'s alarm system');

program
  .command('activate')
  .description('activate the alarm')
  .action(() => {
    logger.info('Activating...')
  });

program
  .command('deactivate')
  .description('deactivate the alarm')
  .action(() => {
    logger.info('Deactivating...')
  });

program
  .command('status')
  .description('check alarm status')
  .argument('[numinst]', 'the numinst number', process.env.VERISURE_NUMINST)
  .argument('[panel]', 'the panel value', process.env.VERISURE_PANEL)
  .action(async (numinst, panel) => {
    logger.info('Checking status...');

    const verisure = new Verisure(logger);

    try {
      if (await verisure.auth(process.env.VERISURE_USER, process.env.VERISURE_PASSWORD)) {
        const status = await verisure.status(numinst, panel);

        logger.info(`Alarm status: ${status}`);
      }
    } catch (err) {
      logger.log('crit', err);
    }
  });

program
  .command('installations')
  .description('list installations')
  .action(async () => {
    logger.info('Checking installations...')

    const verisure = new Verisure(logger);

    try {
      if (await verisure.auth(process.env.VERISURE_USER, process.env.VERISURE_PASSWORD)) {
        const list = await verisure.listInstallations();
        const installations = [];

        installations.push(['Numinst', 'Alias', 'Panel', 'Address', 'City']);

        list.forEach(installation => {
          installations.push([
            installation.numinst,
            installation.alias,
            installation.panel,
            installation.address,
            installation.city,
          ])
        });

        console.log(table(installations));
      }
    } catch (err) {
      logger.log('crit', err);
    }
  });

program.parse();
