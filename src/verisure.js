const { GraphQLClient } = require('graphql-request');

const queries = require('./graphql/queries');
const sleep = require('./util/sleep');

class Verisure {
  endpoint = 'https://customers.verisure.com.br/owa-api/graphql';

  constructor(logger) {
    this.logger = logger;

    this.client = new GraphQLClient(this.endpoint);
    this.client.setHeader('app', '{"appVersion":"n/a","origin":"web"}');
  }

  getNowString() {
    const now = new Date();

    return now.getUTCFullYear().toString() +
      (now.getUTCMonth() + 1) +
      now.getUTCDate() +
      now.getUTCHours() +
      now.getUTCMinutes() +
      now.getUTCSeconds();
  }

  async auth(user, password) {
    const authObject = {
      user,
      id: `OWP_______________${user}_______________${this.getNowString()}`,
      country: 'BR',
      lang: 'pt',
      callby: 'OWP_10'
    };

    try {
      const result = await this.client.request(queries.login, {...authObject, password});

      this.client.setHeader('auth', JSON.stringify({...authObject, hash: result.xSLoginToken.hash}));

      return true;
    } catch (err) {
      this.logger.error('An error occurred while trying to authenticate');
      this.logger.debug(err);
    }

    return false;
  }

  async listInstallations() {
    try {
      const result = await this.client.request(queries.installationList);

      return result.xSInstallations.installations;
    } catch (err) {
      this.logger.error('An error occurred while trying to list installations');
      this.logger.debug(err);
    }

    return false;
  }

  async checkStatus(numinst, panel, referenceId) {
    return this.client.request(queries.checkAlarmStatus, {
      numinst,
      panel,
      idService: '11',
      referenceId,
    });
  }

  async status(numinst, panel) {
    try {
      const queryCheckRes = await this.client.request(queries.checkAlarm, {
        numinst,
        panel,
      });

      if (queryCheckRes.xSCheckAlarm.res === "OK") {
        const referenceId = queryCheckRes.xSCheckAlarm.referenceId;
        let queryStatusRes;
        let counter = 0;

        do {
          if (counter++ > 0) {
            await sleep(500);
          }

          queryStatusRes = await this.checkStatus(numinst, panel, referenceId)
        } while(queryStatusRes.xSCheckAlarmStatus.res === "WAIT");

        return queryStatusRes.xSCheckAlarmStatus.protomResponse === "D" ? "Deactivated" : "Activated";
      }
    } catch (err) {
      this.logger.error('An error occurred while trying to get alarm status');
      this.logger.debug(err);
    }

    return "Unknown";
  }
}

module.exports = Verisure;
