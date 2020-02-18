<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Drivers;

use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\DriverInterface;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Workers\WorkerInterface;

class SynchronousDriver implements DriverInterface
{
    protected array $messages = [];
    /**
     * @var LoopInterface
     */
    private LoopInterface $loop;
    /**
     * @var WorkerInterface
     */
    private WorkerInterface $worker;

    public function __construct(LoopInterface $loop, WorkerInterface $worker)
    {
        $this->loop = $loop;
        $this->worker = $worker;
    }

    public function __destruct()
    {
        while ($this->loop->canContinue() && $message = $this->nextMessage()) {
            $this->worker->process($message, $this);
        }
    }

    /**
     * @inheritDoc
     */
    public function nextMessage(): ?MessageInterface
    {
        $message = array_shift($this->messages);
    }

    /**
     * @inheritDoc
     */
    public function status(string $id): int
    {
        // TODO: Implement status() method.
    }

    /**
     * @inheritDoc
     */
    public function push(JobInterface $job): MessageInterface
    {
        $this->messages[] = $job;
    }

    /**
     * @inheritDoc
     */
    public function subscribe(callable $handler): void
    {
        // TODO: Implement subscribe() method.
    }

    /**
     * @inheritDoc
     */
    public function canPush(JobInterface $job): bool
    {

    }
}
