<?php

namespace App\Livewire\Concerns;

trait PromptsLoginModal
{
    protected function promptLoginModal(?string $redirect = null): void
    {
        $target = $redirect ?: url()->current();

        session(['url.intended' => $target]);
        $this->dispatch('open-login-modal', redirect: $target);
    }
}
