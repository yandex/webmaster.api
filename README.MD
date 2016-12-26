# Yandex Webmaster Api v3 Library

- [Installation](#installation)
    - [Composer](#composer)
    - [Manual](#manual)
- [Usage](#usage)

## Installation

### Composer

add into composer.json
```js
{
    "require": {
        ...
        "yandex/webmaster.api": "dev-master"
        ...
    }
}
```

or

```
php composer.phar require yandex/webmaster.api "dev-master"
```


Also required prior to placing the package on packagist.org
```js
    ...
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/yandex/webmaster.api"
        }
    ]
    ...
```

### Manual

Clone repository and require webmasterApi.php

## Usage

###How to use example:

1. Before use this code go to https://oauth.yandex.ru/client/new and add new client

2. Create file "example/config.php" by copy "config.example.php". Add client ID and secret code here to use example


###How to use webmasterApi class:

1. Add client on https://oauth.yandex.ru/client/new page. Don't forget to set up correct return url

2. For test you can get temporary Access Token (read comments to method webmasterApi::getAccessToken )

3. If you want to get correct Access Token, you must create authorization code.
  2.1 Read doc on https://tech.yandex.ru/oauth/doc/dg/reference/web-client-docpage/
  2.2 Locate your client to https://oauth.yandex.ru/authorize?response_type=code&client_id=[yourclient_id]
  2.3 Use static method getAccessToken to get access token
  2.4 Create webmasterApi object with static method webmasterApi::initApi

You can see example/.auth.php to see how it working

4. Enjoy!



Methods
addHost, addOriginalText, addSitemap, checkVerification, curlOpts, dataToString,
delete, deleteHost, deleteOriginalText, deleteSitemap, errorCritical, get, getAccessToken, getApiUrl,
getHostInfo, getHostOwners, getHostSitemaps, getHostSummary, getHostUserSitemaps, getHosts, getIndexingHistory,
getOriginalTexts, getPopularQueries, getTicHistory, getUserID, initApi, post, verifyHost