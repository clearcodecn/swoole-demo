# swoole 服务器案例demo 

## 目录结构 

- server/  服务器server端实现
- client/  服务器对应文件client简单实现 
- server/http_object_pool 对象池技术 [介绍](https://github.com/clearcodecn/swoole-demo/blob/master/server/http_object_pool/README.md)
- 基准server端代码 server/tcp_oop.php 

### 服务器都是纯异步的. 
- 在swoole_server->start()启动之前 io 操作可以使用同步阻塞的形式 .
- 在worker进程内如果使用同步阻塞 io 函数 进程将会被阻塞住，
- 因此，采用worker+task异步回调方式，将网络io 变为纯异步方式 . 

