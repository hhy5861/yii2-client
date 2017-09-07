## 安装

1. 修改 `composer.json` 文件


```
"repositories": [
    {
        "type": "vcs",
        "url": "git@github.com:hhy5861/yii2-client.git"
    }
]
```

2. 执行 `composer require "mike/client:dev-master"`

3. 修改项目配置

```
'components' => [
    'client' => [
        'class' => 'mike\client\Client',
        'remotes' => [
            'union' => [
                'http://union-service.cheyian.com',
            ],
            // ...
        ],
    ],
]
```
