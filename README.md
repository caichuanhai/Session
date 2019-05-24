# Session

## 关于

一个普通的php session类库，依赖于`phpfastcache/phpfastcache`，所以驱动支持files,redis,predis,memcache,memcached,mongodb,xcache,apc,cookie。

> 强烈不建议使用cookie，非常不安全

## 安装
```
composer require caichuanhai/session
```

## 使用
```php
require_once path/to/vendor/autoload.php;
use caichuanhai\session;
$session = new session([$config]);
```

$config为SESSION配置数组，可不传则使用默认值，默认值如下
```php
array(
		'session_name' => 'CCHSESSION',
		'session_path' => '/',
		'session_match_ip' => false,
		'session_expire' => 3600*24
	)
```

#### 设置单个SESSION配置的值
```php
$session->setConfig($name, $value);
```

#### 获取配置值
```php
$session->getConfig([$name]);
```
若`$name`不传则获取所有配置，若获取配置不存在则返回`NULL`

#### 设置驱动

1. 使用redis驱动，需要安装redis扩展才能使用
```php
$session->setDriver('redis', $config);
//$config配置为
$config = array(
	'host' => '127.0.0.1',
	'port' => 6379,
	'password' => null,
	'database' => null
)
```

2. 使用predis驱动，此驱动不需安装redis扩展，直接使用predis类库
```php
$session->setDriver('predis', $config);
//$config配置为
$config = array(
		'host' => '127.0.0.1',
		'port' => 6379,
		'password' => null,
		'database' => null
)
```

3. 使用memcache驱动，需要安装memcache扩展才能使用
```php
$session->setDriver('memcache', $config);
//$config配置为
$config = array(
		'host' => '127.0.0.1',
		'port' => 11211,
		//'sasl_user' => false,
		//'sasl_password' => false
)
```

4. 使用memcached驱动，此驱动不需安装memcached扩展，直接使用memcached类库
```php
$session->setDriver('memcached', $config);
//$config配置为
$config = array(
		'host' => '127.0.0.1',
		'port' => 11211,
		//'sasl_user' => false,
		//'sasl_password' => false
)
```

5. 使用mongodb驱动
```php
$session->setDriver('mongodb', $config);
//$config配置为
$config = array(
		'host' => '127.0.0.1',
		'port' => 27017,
		'username' => '',
		'password' => '',
		'timeout' => 1,
		'collectionName' => 'Cache',
		'databaseName' => 'database'
)
```

6. 使用files,xcache,apc,cookie驱动
```php
$session->setDriver('files', $config);
```

#### 获取SESSION数据
```php
$session->item([$item]);
```
`$item`为要获取的键名，若不存在则返回`NULL`,若不传，则返回所有SESSION数据

#### 设置SESSION数据
```php
$session->set($item, $value);
```

#### 删除SESSION数据
```php
$session->unset($item, $value);
```

#### 清除SESSION数据
```php
$session->destroy();
```