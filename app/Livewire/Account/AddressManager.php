<?php

namespace App\Livewire\Account;

use App\Models\UserAddress;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.shop')]
#[Title('آدرس‌ها')]
class AddressManager extends Component
{
    public string $receiver_name = '';

    public string $receiver_phone = '';

    public string $province = '';

    public string $city = '';

    public string $address = '';

    public string $postal_code = '';

    public bool $is_default = false;

    public ?int $editingId = null;

    public function save(): void
    {
        $validated = $this->validate([
            'receiver_name' => ['required', 'string', 'max:255'],
            'receiver_phone' => ['required', 'string', 'max:15'],
            'province' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string'],
            'postal_code' => ['required', 'string', 'max:10'],
            'is_default' => ['boolean'],
        ]);

        if ($this->is_default) {
            auth()->user()->addresses()->update(['is_default' => false]);
        }

        if ($this->editingId) {
            $address = auth()->user()->addresses()->findOrFail($this->editingId);
            $address->update($validated);
            session()->flash('success', 'آدرس به‌روزرسانی شد.');
        } else {
            auth()->user()->addresses()->create($validated);
            session()->flash('success', 'آدرس ذخیره شد.');
        }

        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $address = auth()->user()->addresses()->findOrFail($id);
        $this->editingId = $address->id;
        $this->fill($address->only([
            'receiver_name', 'receiver_phone', 'province', 'city', 'address', 'postal_code', 'is_default',
        ]));
    }

    public function delete(int $id): void
    {
        auth()->user()->addresses()->findOrFail($id)->delete();
        session()->flash('success', 'آدرس حذف شد.');
    }

    public function resetForm(): void
    {
        $this->reset(['receiver_name', 'receiver_phone', 'province', 'city', 'address', 'postal_code', 'is_default', 'editingId']);
    }

    public function render()
    {
        return view('livewire.account.address-manager', [
            'addresses' => auth()->user()->addresses()->latest()->get(),
        ]);
    }
}
