<?php

declare(strict_types=1);

namespace Yiisoft\Yii\Queue\Drivers;

use InvalidArgumentException;
use Yiisoft\Yii\Queue\Cli\LoopInterface;
use Yiisoft\Yii\Queue\DriverInterface;
use Yiisoft\Yii\Queue\QueueDependentInterface;
use Yiisoft\Yii\Queue\Enum\JobStatus;
use Yiisoft\Yii\Queue\Jobs\DelayableJobInterface;
use Yiisoft\Yii\Queue\Jobs\JobInterface;
use Yiisoft\Yii\Queue\Jobs\PrioritisedJobInterface;
use Yiisoft\Yii\Queue\Message;
use Yiisoft\Yii\Queue\MessageInterface;
use Yiisoft\Yii\Queue\Queue;
use Yiisoft\Yii\Queue\Workers\WorkerInterface;

final class SynchronousDriver implements DriverInterface, QueueDependentInterface
{
    private array $messages = [];
    private Queue $queue;
    private LoopInterface $loop;
    private WorkerInterface $worker;
    private int $current = 0;

    public function __construct(LoopInterface $loop, WorkerInterface $worker)
    {
        $this->loop = $loop;
        $this->worker = $worker;
    }

    public function __destruct()
    {
        $this->run([$this->worker, 'process']);
    }

    /**
     * @inheritDoc
     */
    public function nextMessage(): ?MessageInterface
    {
        $message = null;

        if (isset($this->messages[$this->current])) {
            $message = $this->messages[$this->current];
            unset($this->messages[$this->current]);
            $this->current++;
        }

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function status(string $id): int
    {
        $id = (int) $id;

        if ($id < 0) {
            throw new InvalidArgumentException('This driver ids starts with 0');
        }

        if ($id < $this->current) {
            return JobStatus::DONE;
        }

        if (isset($this->messages[$id])) {
            return JobStatus::WAITING;
        }

        return JobStatus::INVALID;
    }

    /**
     * @inheritDoc
     */
    public function push(JobInterface $job): MessageInterface
    {
        $key = max(array_keys($this->messages));
        $message = new Message((string) ++$key, $job);
        $this->messages[] = $message;

        return $message;
    }

    /**
     * @inheritDoc
     */
    public function subscribe(callable $handler): void
    {
        $this->run($handler);
    }

    /**
     * @inheritDoc
     */
    public function canPush(JobInterface $job): bool
    {
        return !($job instanceof DelayableJobInterface || $job instanceof PrioritisedJobInterface);
    }

    public function setQueue(Queue $queue): void
    {
        $this->queue = $queue;
    }

    private function run(callable $handler): void
    {
        while ($this->loop->canContinue() && $message = $this->nextMessage()) {
            $handler($message, $this->queue);
        }
    }
}
