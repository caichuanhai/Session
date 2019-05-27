<?php
namespace caichuanhai;

use Phpfastcache\CacheManager;
use Phpfastcache\Core\phpFastCache;

/**
 * 自定义SESSION类
 * 实例化后自动启动SESSION
 */
class Session
{

	/*SESSION的配置，一维数组*/
	private $_config = array(
		'session_name' => 'CCHSESSION',
		'session_path' => '/',
		'session_match_ip' => false,
		'session_expire' => 3600*24
	);

	private $_sessionId = null; /*当前session id*/

	private $_data = array('sys' => array(), 'user' => array()); /*保存当前session数据，sys中保存系统数据，user保存用户SESSION数据*/

	private $_driver = null; /*PhpFastCache实例*/

	function __construct($config = array())
	{
		$this->_config = array_merge($this->_config, $config);
	}

	/**
	 * 获取session数据
	 * @param  mixed $item 要获取的数据名
	 * @return mixed 没找到返回null
	 */
	function item($item = '')
	{
		if(empty($item)) return $this->_data['user'];

		return isset($this->_data['user'][$item]) ? $this->_data['user'][$item] : null;
	}

	/**
	 * 设置session数据
	 * @param string $item  要设置的键名
	 * @param mixed $value 要设置的值
	 */
	function set($item, $value)
	{
		$this->_data['user'][$item] = $value;
	}

	/**
	 * 删除session数据
	 * @param  mixed $item 可以是单个键名也可以是键名数组
	 * @return
	 */
	function unset($item)
	{
		if(!is_array($item)) $item = array($item);

		 foreach($item as $v)
		 {
		 	unset($this->_data['user'][$v]);
		 }
	}

	/**
	 * 清除所有session数据
	 * @return
	 */
	function destroy()
	{
		$this->_data['user'] = array();
	}

	/**
	 * 设置session的配置
	 * @param String $name  配置名
	 * @param String $value 配置值
	 */
	function setConfig($name, $value)
	{
		$this->_config[$name] = $value;
	}

	/**
	 * 获取配置值
	 * @param  string $name 配置名
	 * @return [type]       [description]
	 */
	function getConfig($name = '')
	{
		if(empty($name)) return $this->_config;
		return isset($this->_config[$name]) ? $this->_config[$name] : null;
	}

	/**
	 * 设置驱动实例
	 * @param string $driver 驱动类型，支持类型 files,redis,predis,memcache,memcached,mongodb,xcache,apc,cookie
	 * @param Array $config 该驱动配置
	 */
	function setDriver($driver = 'files', $config = array())
	{
		switch ($driver)
		{
			case 'redis':
				$this->_driver = CacheManager::getInstance('redis', new \Phpfastcache\Drivers\Redis\Config($config));
				break;

			case 'predis':
				$this->_driver = CacheManager::getInstance('Predis', new \Phpfastcache\Drivers\Predis\Config($config));
				break;

			case 'memcache':
				$this->_driver = CacheManager::getInstance('memcache',new \Phpfastcache\Drivers\Memcache\Config($config));
				break;

			case 'memcached':
				$this->_driver = CacheManager::getInstance('memcached', new \Phpfastcache\Drivers\Memcached\Config($config));
				break;

			case 'mongodb':
				$this->_driver = CacheManager::getInstance('mongodb', new \Phpfastcache\Drivers\Mongodb\Config($config));
				break;

			case 'xcache':
				$this->_driver = CacheManager::getInstance('xcache');
				break;

			case 'apc':
				$this->_driver = CacheManager::getInstance('apc');
				break;

			case 'cookie':
				$this->_driver = CacheManager::getInstance('cookie');
				break;
			
			default:
				CacheManager::setDefaultConfig(new \Phpfastcache\Config\Config([
					"path" => sys_get_temp_dir(),
					"itemDetailedDate" => false
				]));
				$this->_driver = CacheManager::getInstance('files');
				break;
		}

		/*设置好驱动，下面启动SESSION*/
		$this->_setSessionId();
	}

	/**
	 * 设置SESSION ID
	 */
	private function _setSessionId()
	{
		/*判断客户端是否有session_id*/
		$sessionId = $this->_getCookie($this->getConfig('session_name'));
		echo $sessionId;
		if($sessionId)
		{
			/*客户端已存在session_name，直接获取SESSION ID并验证正确性*/
			$sessionData = $this->_getDataViaSessionId($sessionId);

			$match = $this->_match_ip($sessionData);
			if($match)
			{
				$this->_data = $sessionData;
				$this->_sessionId = $sessionId;
			}
		}

		if($this->_sessionId == null)
		{
			/*新生成sessionId*/
			$this->_sessionId = $this->_randomSessionId();
			$this->_data['sys'] = array('ip' => $this->_getIp());
		}

		/*生成cookie*/
		$this->_setCookie($this->getConfig('session_name'), $this->_sessionId, $this->getConfig('session_expire')+time(), $this->getConfig('session_path'));
	}

	/**
	 * 随机生成sessionId
	 * @return string sessionId
	 */
	private function _randomSessionId()
	{
		return 'CCH_session_'.md5(uniqid(microtime(true),true));
	}

	/**
	 * 通过sessionId获取session数据
	 * @param  string $sessionId sessionId
	 * @return [type]            [description]
	 */
	private function _getDataViaSessionId($sessionId)
	{
		$cache = $this->_driver->getItem($sessionId);

		if(is_null($cache->get()))
		{
			return array();
		}

		return $cache->get();
	}

	/**
	 * 通过IP验证SESSION正确性
	 * @param  Array $sessionData session数据
	 * @return bool
	 */
	private function _match_ip(& $sessionData)
	{
		if($this->getConfig('session_match_ip'))
		{
			$ip = $this->_getIp();
			if(isset($sessionData['sys']['ip']) AND $sessionData['sys']['ip'] == $ip) return true;
			else return false;
		}

		return true;
	}

	/**
	 * 获取客户端cookie数据
	 * @param string $name
	 * @return mixed
	 */
	public function _getCookie($name = '')
	{
		$path = explode('.', $name);
		$value = $_COOKIE;
		foreach ($path as $item)
		{
			if($item == '') break;

			$value = isset($value[$item]) ? $value[$item] : null;
		}
		return $value;
	}

	/**
	 * 设置cookie
	 * @param $name
	 * @param string $value
	 * @param int $expire
	 * @param string $path
	 * @param string $domain
	 * @param bool $secure
	 * @param bool $httponly
	 * @return bool
	 */
	private function _setCookie($name, $value = "", $expire = 0, $path = "", $domain = "", $secure = false, $httponly = false)
	{
		return setcookie(...func_get_args());
	}

	private function _getIp()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if(isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '')
		{
			$ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
			$ip = $ip[0];
		}
		return $ip;
	}

	/**
	 * 将_data中的数据保存进缓存中
	 * @return
	 */
	private function _save()
	{
		$cache = $this->_driver->getItem($this->_sessionId);

		$cache->set($this->_data)->expiresAfter($this->getConfig('session_expire'));

		$this->_driver->save($cache);
	}

	function __destruct()
	{
		$this->_save();
	}
}