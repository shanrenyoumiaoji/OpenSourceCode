<?php

//栈实现队列 两个栈实现队列
class Myqueue
{
    public $stack1;
    public $stack2;

    public function __construct()
    {
        $this->stack1 = new SplStack();
        $this->stack2 = new SplStack();
    }

    public function push(int $x) //例子只插入了整型
    {
        $this->stack1->push($x);
    }

    //弹出队列头
    public function pop()
    {
        $this->stack1ToStack2();

        return $this->stack2->pop();
    }

    //返回队头
    public function top()
    {
        $this->stack1ToStack2();

        //statck2的头元素
        return $this->stack2->top();
    }

    //statck1=>stack2
    public function stack1ToStack2()
    {
        if ($this->stack2->isEmpty()) {
            //stack1栈数据压入stack2
            while (!$this->stack1->isEmpty()) {
                $this->stack2->push($this->stack1->pop());
            }
        }
    }

    public function isEmpty()
    {
        return $this->stack1 && $this->stack2;
    }
}

$queue = new Myqueue;

for ($i=1; $i <= 5; $i++) {
    $queue->push($i);
}
$queue->pop();
var_dump($queue, $queue->top());


//队列实现栈
class MyStack
{
    private $stack;
    private $top; //栈顶元素

    public function __construct()
    {
        $this->stack = new SplQueue;
    }

    //入栈
    public function push(int $x)
    {
        $this->stack->enqueue($x);
        $this->top = $x;
    }

    //弹出栈顶 即:删除队头
    public function pop()
    {
        $n = $this->stack->count();
        while ($n > 1) { //1<-2<-3 => 3<-1<-2 队列头就是最后一个入队列的值
            $this->stack->push($this->stack->shift());
            $n--;
        }

        //更新最后队尾 即:栈顶
        $this->top = $this->stack->top();

        return $this->stack->shift();
    }

// q.offer(q.poll());
    //返回栈顶元素 队尾
    public function top()
    {
        return $this->top;
    }

    public function isEmtpy()
    {
        return $this->isEmpty();
    }
}

$stack = new MyStack;

for ($i=1; $i <= 6; $i++) {
    $stack->push($i);
}

$stack->pop();
var_dump($stack);
