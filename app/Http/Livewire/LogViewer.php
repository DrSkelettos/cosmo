<?php

namespace App\Http\Livewire;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Log Viewer')]
class LogViewer extends Component
{
    public string $selectedLog = '';
    public string $logContent = '';
    public int $linesPerPage = 100;
    public int $currentPage = 1;
    public string $search = '';
    public string $level = 'all';

    public function mount(): void
    {
        $logs = $this->logFiles;
        $this->selectedLog = $logs->first() ?? '';
        if ($this->selectedLog) {
            $this->loadLogContent();
        }
    }

    public function updatedSelectedLog(): void
    {
        $this->currentPage = 1;
        $this->search = '';
        $this->level = 'all';
        $this->loadLogContent();
    }

    public function loadLogContent(): void
    {
        if (!$this->selectedLog) {
            $this->logContent = '';
            return;
        }

        $path = storage_path('logs/' . $this->selectedLog);
        if (!file_exists($path)) {
            $this->logContent = 'Log file not found.';
            return;
        }

        $this->logContent = file_get_contents($path);
    }

    public function getLogFilesProperty(): \Illuminate\Support\Collection
    {
        $logPath = storage_path('logs');
        if (!is_dir($logPath)) {
            return collect();
        }

        return collect(scandir($logPath))
            ->filter(fn ($file) => $file !== '.' && $file !== '..' && str_ends_with($file, '.log'))
            ->sortDesc()
            ->values();
    }

    public function getFilteredLinesProperty(): array
    {
        if (empty($this->logContent)) {
            return [];
        }

        $lines = explode("\n", $this->logContent);

        if ($this->search !== '') {
            $lines = array_filter($lines, fn ($line) => stripos($line, $this->search) !== false);
        }

        if ($this->level !== 'all') {
            $levels = ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug'];
            if (in_array($this->level, $levels)) {
                $lines = array_filter($lines, fn ($line) => stripos($line, strtoupper($this->level)) !== false);
            }
        }

        return array_values($lines);
    }

    public function getPaginatedLinesProperty(): array
    {
        $lines = $this->filteredLines;
        $totalLines = count($lines);
        $totalPages = max(1, ceil($totalLines / $this->linesPerPage));

        $this->currentPage = min(max(1, $this->currentPage), $totalPages);

        $offset = ($this->currentPage - 1) * $this->linesPerPage;
        return array_slice($lines, $offset, $this->linesPerPage);
    }

    public function nextPage(): void
    {
        $this->currentPage++;
    }

    public function previousPage(): void
    {
        $this->currentPage--;
    }

    public function refreshLog(): void
    {
        $this->loadLogContent();
    }

    public function render()
    {
        return view('livewire.log-viewer');
    }
}
