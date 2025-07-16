<?php

namespace ArchiElite\LogViewer\Readers;

use ArchiElite\LogViewer\Collections\LogFileCollection;
use ArchiElite\LogViewer\Direction;
use ArchiElite\LogViewer\Exceptions\CannotOpenFileException;
use ArchiElite\LogViewer\Facades\LogViewer;
use ArchiElite\LogViewer\LevelCount;
use ArchiElite\LogViewer\LogFile;
use ArchiElite\LogViewer\Logs\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

class MultipleLogReader
{
    protected LogFileCollection $fileCollection;

    protected ?int $limit = null;

    protected ?int $skip = null;

    protected ?string $query = null;

    protected string $direction;

    protected ?array $exceptLevels = null;

    public function __construct(mixed $files)
    {
        if ($files instanceof LogFile) {
            $this->fileCollection = new LogFileCollection([$files]);
        } elseif (is_array($files)) {
            $this->fileCollection = new LogFileCollection($files);
        } else {
            $this->fileCollection = $files;
        }

        $this->setDirection(Direction::Forward);
    }

    public function exceptLevels($levels = null): self
    {
        $this->exceptLevels = $levels;

        return $this;
    }

    public function allLevels(): self
    {
        $this->exceptLevels = null;

        return $this;
    }

    public function setDirection(string $direction = null): self
    {
        $this->direction = $direction === Direction::Backward
            ? Direction::Backward
            : Direction::Forward;

        if ($this->direction === Direction::Forward) {
            $this->fileCollection = $this->fileCollection->sortByEarliestFirst();
        } elseif ($this->direction === Direction::Backward) {
            $this->fileCollection = $this->fileCollection->sortByLatestFirst();
        }

        return $this;
    }

    public function forward(): self
    {
        return $this->setDirection(Direction::Forward);
    }

    public function reverse(): self
    {
        return $this->setDirection(Direction::Backward);
    }

    public function skip(int $number): self
    {
        $this->skip = $number;

        return $this;
    }

    public function limit(int $number): self
    {
        $this->limit = $number;

        return $this;
    }

    public function search(string $query = null): self
    {
        $this->query = $query;

        return $this;
    }

    public function getLevelCounts(): array
    {
        $totalCounts = [];

        /** @var LogFile $file */
        foreach ($this->fileCollection as $file) {
            foreach ($this->getLogQueryForFile($file)->getLevelCounts() as $levelCount) {
                $level = $levelCount->level->value;

                if (! isset($totalCounts[$level])) {
                    $totalCounts[$level] = new LevelCount($levelCount->level, 0, $levelCount->selected);
                }

                $totalCounts[$level]->count += $levelCount->count;
            }
        }

        return array_values($totalCounts);
    }

    public function total(): int
    {
        return $this->fileCollection->sum(fn (LogFile $file) => $this->getLogQueryForFile($file)->total());
    }

    public function paginate($perPage = 25, int $page = null): LengthAwarePaginator
    {
        $page = $page ?: Paginator::resolveCurrentPage('page');

        $this->skip(max(0, $page - 1) * $perPage);

        return new LengthAwarePaginator(
            $this->get($perPage),
            $this->total(),
            $perPage,
            $page
        );
    }

    /**
     * Get the logs from this file collection.
     *
     * @return array|Log[]
     */
    public function get(int $limit = null): array
    {
        $skip = $this->skip ?? null;
        $limit ??= $this->limit ?? null;
        $logs = [];

        /** @var LogFile $file */
        foreach ($this->fileCollection as $file) {
            $logQuery = $this->getLogQueryForFile($file);

            if (isset($skip)) {
                $logsToSkip = min($skip, $logQuery->total());
                $logQuery->reset()->skip($logsToSkip);
                $skip -= $logsToSkip;
            }

            while ($log = $logQuery->next()) {
                $logs[] = $log;

                if (isset($limit) && (--$limit <= 0)) {
                    break 2;
                }
            }

            if (isset($limit) && $limit <= 0) {
                // we've gotten the required amount of logs! exit early
                break;
            }
        }

        return $logs;
    }

    public function requiresScan(): bool
    {
        return $this->fileCollection->some(fn (LogFile $file) => $this->getLogQueryForFile($file)->requiresScan());
    }

    public function percentScanned(): int
    {
        $totalFileBytes = $this->fileCollection->sum->size();

        if ($totalFileBytes <= 0) {
            // empty files, so assume they've been fully scanned
            return 100;
        }

        $missingScansBytes = $this->fileCollection->sum(fn (LogFile $file) => $this->getLogQueryForFile($file)->numberOfNewBytes());

        return 100 - intval($missingScansBytes / $totalFileBytes * 100);
    }

    public function scan(int $maxBytesToScan = null, bool $force = false): void
    {
        $fileSizeScanned = 0;
        $stopScanningAfter = microtime(true) + LogViewer::lazyScanTimeout();

        /** @var LogFile $logFile */
        foreach ($this->fileCollection as $logFile) {
            $logQuery = $this->getLogQueryForFile($logFile);

            if (! $logQuery->requiresScan()) {
                continue;
            }

            $fileSizeScanned += $logQuery->numberOfNewBytes();

            try {
                $logQuery->scan($maxBytesToScan, $force);
            } catch (CannotOpenFileException) {
                continue;
            }

            if (isset($maxBytesToScan) && $fileSizeScanned >= $maxBytesToScan) {
                break;
            }

            if ($stopScanningAfter < microtime(true)) {
                break;
            }
        }
    }

    protected function getLogQueryForFile(LogFile $file): LogReaderInterface
    {
        return $file->logs()
            ->search($this->query)
            ->setDirection($this->direction)
            ->exceptLevels($this->exceptLevels)
            ->lazyScanning();
    }
}
