const {gql} = require("graphql-request");

const login = gql`
  mutation LoginToken($user: String!, $password: String!, $id: String!, $country: String!, $lang: String!, $callby: String!) {
    xSLoginToken(user: $user, password: $password, id: $id, country: $country, lang: $lang, callby: $callby) {
      res
      msg
      hash
      lang
      legals
      mainUser
      changePassword
      needDeviceAuthorization
    }
  }
`

const installationList = gql`
  query InstallationList {
    xSInstallations {
      installations {
        numinst
        alias
        panel
        type
        name
        surname
        address
        city
        postcode
        province
        email
        phone
      }
    }
  }
`

const checkAlarm = gql`
  query CheckAlarm($numinst: String!, $panel: String!) {
    xSCheckAlarm(numinst: $numinst, panel: $panel) {
      res
      msg
      referenceId
    }
  }
`

const checkAlarmStatus = gql`
  query CheckAlarmStatus($numinst: String!, $idService: String!, $panel: String!, $referenceId: String!) {
    xSCheckAlarmStatus(numinst: $numinst, idService: $idService, panel: $panel, referenceId: $referenceId) {
      res
      msg
      status
      numinst
      protomResponse
      protomResponseDate
    }
  }
`

module.exports = {
  login,
  installationList,
  checkAlarm,
  checkAlarmStatus,
};
