# 说明
整个框架就是抄的 workerman，我是从头开始抄，从最基本的 echo 服务开始抄

这只是一个学习项目

# TODO 待完成 [13/21]
- [X] telnet echo 交互
  [2018-06-01] 完成

- [X] libevent
  [2018-06-01] 完成

- [X] 支持 protocol text
  [2018-06-03] 完成

- [X] 支持 protocol json, jsonInt
  [2018-06-06] 完成

- [X] daemon, `php index.php start -d`, `php index.php stop`
  [2018-06-05] 完成

- [X] http 基本协议解析 (这部分现在直接抄的 workerman)
  [2018-06-09] 完成

- [X] http keep alive
  [2018-06-09] 完成

- [X] http session
  [2018-06-12] 完成

- [X] JsonRPC
  [2018-06-13] 完成

- [X] libevent write event
  [2018-06-20] 完成

- [X] Timer（通过 libevent timer 实现）
  [2018-06-23] 完成

- [X] http 协议的 close 有问题，会有 time-wait，主要是 keep-alive 实现得有问题 (暂时先这样，能跑起来就好了)
  [2018-06-24] time wait 问题是正常的，因为测试的时候 client 也是本机，tcp 协议中先发送 close 的一方会主动进入 time wait 状态

- [X] 多 worker 支持
  [2018-06-24] (不支持 mac [mac 上的 event 扩展有 bug])
  [2018-06-25] 该 bug 是 libevent 在多进程环境下的问题，需要了解多进程怎么搞 reactor
  [2018-06-26] 解决的方法是：libevent 的 event_base 在 fork 之后创建，这样每个 worker 进程维护一个 libevent event_base 实例

- [ ] signal
- [ ] 异步任务
- [ ] log
- [X] x 协程 (这里没法实现啊 - -)
- [X] x ssl 协议 (暂时没用)
- [ ] protocol 解析有问题，解析不完整的协议包的时候，连接还是保持着，这里应该有个超时断开，workerman 的 http keep-alive 没有 timeout 的机制
- [ ] redis 协议
- [ ] buffer 优化，buffer 满，buffer 空

