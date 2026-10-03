<?php

namespace App\Livewire;

use App\Filament\Support\AdminTopBarCreateResolver;
use Livewire\Attributes\On;
use Livewire\Component;

class AdminTopBar extends Component
{
    public string $currentPath = '';

    public function mount(): void
    {
        $this->syncCurrentPath('/'.ltrim(request()->path(), '/'));
    }

    #[On('livewire:navigated')]
    public function refreshAfterNavigate(): void
    {
        // Path is updated from the browser via setCurrentPath().
    }

    public function setCurrentPath(string $path): void
    {
        $this->syncCurrentPath($path);
    }

    protected function syncCurrentPath(string $path): void
    {
        $this->currentPath = rtrim($path, '/') ?: '/';
    }

    public function render()
    {
        $path = $this->currentPath !== '' ? $this->currentPath : null;

        return view('filament.partials.admin-top-bar', [
            'adminTopBarCreate' => AdminTopBarCreateResolver::resolve($path),
        ]);
    }
}
