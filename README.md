Macaw
=====


### 安装

使用Composer加载到自己的项目里
或者下载Zip包解压到自己的项目里

```
require: {
    "noahbuscher/macaw": "dev-master"
}
```

### 例子

首先需要引入Macaw的命名空间

```PHP
use \NoahBuscher\Macaw\Macaw;
```

Macaw 并非一个实例化的对象
再你引入命名空间后可以直接通过静态方式调用:

```PHP
Macaw::get('/', function() {
  echo 'Hello world!';
});

Macaw::dispatch();
```

支持调用匿名函数:

```PHP
Macaw::get('/(:any)', function($slug) {
  echo 'The slug is: ' . $slug;
});

Macaw::dispatch();
```

支持多种http请求的方法:

```PHP
Macaw::get('/', function() {
  echo 'I <3 GET commands!';
});

Macaw::post('/', function() {
  echo 'I <3 POST commands!';
});

Macaw::dispatch();
```

如果没有定义路由, 那么Macaw会调用回调函数:

```PHP
Macaw::error(function() {
  echo '404 :: Not Found';
});
```

如果不指定一个错误就会运行回调函数中的404错误

<hr>

例子(https://github.com/noahbuscher/Macaw/blob/master/config).
