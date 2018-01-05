# 该demo是一个简单的对象池复用demo. 

# 原理: 

- swoole_http_server 启动以后 对 request 事件进行监听, 请求来了之后所做的事情如下：

* 1. 根据路由推算出控制器 namespace::className . 
* 2. 根据控制器执行ControllerFactory::getController($controllerName) 可以得到控制器 .
* 3. ControllerFactory 会初始化一个 $pool 对象，该对象解剖如下：
```
 ControllerFactory::$pool = [
    [$controllerName => \SplQueue() => [
            $controllerObject ,
            $controllerObject,
            $controllerObject,
        ],
    ],
 ];
```
* 4. 每一个请求会从对象池获取( shift 操作 )一个 $controllerObject.
* 5. 然后设置请求的上下文 当然该 Context也可以做为一个对象池存储起来 .
```angularjs
    $controllerObject->context = new Context($request ,$response, $controllerObject);
    $controllerObject->{$method}(); // 执行方法 . 
```
* 6. 执行方法完了需要发送请求. 
``` 
$this->context->send( jsonstring );
Context::send 方法会将该controller初始化并且归还到pool中去.
```
* 7.下一次请求来了就直接从对象池获取对象，而不是new Controller了，

### 对象复用可以很大的提升服务器性能。以下是需要注意的地方: 

- 对象属性: 对象归还的时候需要重置对象属性为默认值 .该操作可以由开发人员自己设置
- 由于请求可能过多，或者对象池的对象太多且没有复用，需要定时清理对象池,以免内存溢出. 
- worker进程是隔离的，所以每个进程内都有一份controller对象池，该池子是不共享的.







