<h1 align="center"> yii-counter </h1>

yii-counter [![Build Status](https://travis-ci.com/hughcube/yii-counter.svg?branch=master)](https://travis-ci.com/hughcube/yii-counter)


## Installing

```shell
$ composer require hughcube/yii-counter -vvv
```

## Usage

## 配置

```php
'counter' => [
    'class' => '\Hughcube\YiiCounter\Counter',
    'keyPrefix' => 'prefix',
    'storage' => [
        'class' => '\Hughcube\YiiCounter\RedisStorage',
        'redis' => 'redis'
    ]
]
```

## License

MIT
