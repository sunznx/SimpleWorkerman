<?php

namespace Sunznx\SimpleWorkerman\Timer;

use Event;
use Sunznx\SimpleWorkerman\EventLoop\LibeventEventLoop;
use Sunznx\SimpleWorkerman\Worker;

class Timer implements TimerInterface
{
    /**
     * @var LibeventEventLoop
     */
    public static $event_loop;

    /**
     * @var Event[]
     */
    public static $timers = [];

    public static function add($sec, callable $cb, $persist = true)
    {
        $event = self::$event_loop->addTimer($sec, $cb, $persist);
        $timer_id = spl_object_hash($event);
        self::$timers[$timer_id] = $event;
        return $timer_id;
    }

    public static function del($timer_id)
    {
        $event = self::$timers[$timer_id];
        $event->delTimer();
        unset(self::$timers[$timer_id]);
    }

    public static function delAll()
    {
        foreach (self::$timers as $timer_id => $timer) {
            self::del($timer_id);
        }
    }
}