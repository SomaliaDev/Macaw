<?php

namespace NoahBuscher\Macaw;
//命名空间可以随意修改,根据项目而定~>当然建议使用原名推广版权
/**
 * 以下静态方法是该类中包含的所有方法,均可根据需求来使用
 * @method static Macaw get(string $route, Callable $callback)
 * @method static Macaw post(string $route, Callable $callback)
 * @method static Macaw put(string $route, Callable $callback)
 * @method static Macaw delete(string $route, Callable $callback)
 * @method static Macaw options(string $route, Callable $callback)
 * @method static Macaw head(string $route, Callable $callback)
 */
class Macaw {
  public static $halts = false;
  public static $routes = array();
  public static $methods = array();
  public static $callbacks = array();
  public static $patterns = array(
      ':any' => '[^/]+',
      ':num' => '[0-9]+',
      ':all' => '.*'
  );
  public static $error_callback;

  /**
   * 初始化路由
   * @param $method
   * @param $params
   */
  public static function __callstatic($method, $params) {
    $uri = dirname($_SERVER['PHP_SELF']).'/'.$params[0];
    $callback = $params[1];

    array_push(self::$routes, $uri);
    array_push(self::$methods, strtoupper($method));
    array_push(self::$callbacks, $callback);
  }

  /**
   * 未找到路由
   * @param $callback
   */
  public static function error($callback) {
    self::$error_callback = $callback;
  }

  /**
   * 停止匹配路由
   * @param bool $flag
   */
  public static function haltOnMatch($flag = true) {
    self::$halts = $flag;
  }

  /**
   * 请求时调用回调函数
   */
  public static function dispatch(){
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $method = $_SERVER['REQUEST_METHOD'];

    $searches = array_keys(static::$patterns);
    $replaces = array_values(static::$patterns);

    $found_route = false;

    self::$routes = str_replace('//', '/', self::$routes);

    // Check if route is defined without regex
    if (in_array($uri, self::$routes)) {
      $route_pos = array_keys(self::$routes, $uri);
      foreach ($route_pos as $route) {
        // 判断是否使用ANY任意匹配请求
        if (self::$methods[$route] == $method || self::$methods[$route] == 'ANY') {
          $found_route = true;

          // 如果路由不是一个对象
          if (!is_object(self::$callbacks[$route])) {

            // 使用分隔符拆分开来
            $parts = explode('/',self::$callbacks[$route]);

            // 获取数组最后一个索引
            $last = end($parts);

            // 获取定义控制器的名称与方法名称
            $segments = explode('@',$last);

            // 实例化控制器
            $controller = new $segments[0]();

            // 动态调用方法
            $controller->{$segments[1]}();

            if (self::$halts) return;
          } else {
            // 使用闭包
            call_user_func(self::$callbacks[$route]);

            if (self::$halts) return;
          }
        }
      }
    } else {
      // Check if defined with regex
      $pos = 0;
      foreach (self::$routes as $route) {
        if (strpos($route, ':') !== false) {
          $route = str_replace($searches, $replaces, $route);
        }

        if (preg_match('#^' . $route . '$#', $uri, $matched)) {
          if (self::$methods[$pos] == $method || self::$methods[$pos] == 'ANY') {
            $found_route = true;

            // 删掉正则表达式所匹配到的内容
            array_shift($matched);

            if (!is_object(self::$callbacks[$pos])) {

              // 使用 / 分割
              $parts = explode('/',self::$callbacks[$pos]);

              // 获取数组最后一个索引
              $last = end($parts);

              // 获取定义控制器的名称与方法名称
              $segments = explode('@',$last);

              // 实例化控制器
              $controller = new $segments[0]();

              // 验证参数是否正确
              if(!method_exists($controller, $segments[1])) {
                echo "controller and action not found";
              } else {
                call_user_func_array(array($controller, $segments[1]), $matched);
              }

              if (self::$halts) return;
            } else {
              call_user_func_array(self::$callbacks[$pos], $matched);

              if (self::$halts) return;
            }
          }
        }
        $pos++;
      }
    }

    // 当路由没有找到时
    if ($found_route == false) {
      if (!self::$error_callback) {
        self::$error_callback = function() {
          header($_SERVER['SERVER_PROTOCOL']." 404 Not Found");
          echo '404';
        };
      } else {
        if (is_string(self::$error_callback)) {
          self::get($_SERVER['REQUEST_URI'], self::$error_callback);
          self::$error_callback = null;
          self::dispatch();
          return ;
        }
      }
      call_user_func(self::$error_callback);
    }
  }
}
