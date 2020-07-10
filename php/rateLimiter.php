<?php
//限流实现
interface RateLimiter
{
    public function access();
}

//固定时间窗口算法又叫计数器算法,对一段时间内的访问次数进行计数,如果计数超过设置的阈值,则拒绝访问
//通知这种算法存在的问题是在临近界限时间时,访问请求增加情况,导致固定时间内请求超过预设阈值
class FixedWindowRateLimiterAlg implements RateLimiter
{
    protected $prefix = "ratelimiter:";
    protected $key;
    protected $windowTime;
    protected $limit;
    private   $redis;

    public function __construct(string $key, int $windowTime, int $limit)
    {
        $this->key        = $this->prefix . $key; //key
        $this->windowTime = $windowTime; //窗口时间
        $this->limit      = $limit; //限制次数
        $this->redis      = (new RedisImpl())->initRedis();
    }

    public function access()
    {
        $cnt = $this->redis->get($this->key);
        if (!$cnt) {
            //首次访问
            //这里用的是管道,管道虽可打包一批命令,但是它是非原子的
            //这点很重要,根据你的项目进行优化(setnx 或者set加分布式锁)
            $this->redis->pipeline();

            $this->redis->incr($this->key);
            $this->redis->expire($this->key, $this->windowTime);

            $this->redis->exec();

            return true;
        }

        //是否超限
        if ($cnt >= $this->limit) {
            return false;
        }

        $this->redis->incr($this->key);

        return true;
    }
}

//redis实例
class RedisImpl
{
    public function initRedis()
    {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);

        return $redis;
    }
}

//client
$client = new FixedWindowRateLimiterAlg('test', 60, 10);
if (!$client->access()) {
    echo '超限';
}

echo '继续访问';


//滑动时间窗口,优化了固定时间窗口算法的临界值和区间太大的问题
//把固定时间比如 60秒,分隔几段,每次移动一段(比如20秒)
class SlidingWindowRateLimiterAlg implements RateLimiter
{
    protected $prefix = "ratelimiter:";
    protected $key;
    protected $windowTime;
    protected $limit;
    protected $block;
    private   $redis;

    public function __construct(string $key, int $windowTime, int $limit, int $block)
    {
        $this->key        = $this->prefix . $key; //key
        $this->windowTime = $windowTime; //窗口时间
        $this->limit      = $limit; //限制次数
        $this->block      = $block; //分隔几段

        $this->redis      = (new RedisImpl())->initRedis();
    }

    public function access()
    {
        $time = time();
        $lastBlockTime  = $time - $time % (ceil($this->windowTime / $this->block)); //分段结束时间 余数为一个周期
        $firstBlockTime = $lastBlockTime - $this->windowTime; //分段开始时间

        $this->redis->zremrangebylex($this->key, '[0', '['.$firstBlockTime); //删除过期时间数据

        //每段时间和时间段内访问次数
        $block = $this->redis->zrange($this->key, 0, -1, 'WITHSCORES');
        $scores = array_sum($block); //时间段内总访问次数
        if ($this->limit - $scores <= 0) {
            //超限
            return false;
        }

        $this->redis->zincrby($this->key, 1, $lastBlockTime);
        return true;
    }
}

//client
$client = new SlidingWindowRateLimiterAlg('test', 60, 10, 3);
if (!$client->access()) {
    echo '超限';exit;
}

echo '继续访问';