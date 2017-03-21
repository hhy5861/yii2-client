## 安装

1. 修改 `composer.json` 文件


```
"repositories": [
    {
        "type": "vcs",
        "url": "git@gitlab.oapol.com:yii/yii2-cy-client.git"
    }
]
```

2. 执行 `php composer.phar --prefer-dist require cy/client`

3. 修改项目配置

```
'components' => [
    'client' => [
        'class' => 'cy\client\Client',
        'remotes' => [
            'union' => [
                'http://union-service.cheyian.com',
            ],
            // ...
        ],
    ],
]
```
