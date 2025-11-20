<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\User;
use Livewire\Attributes\On;

class UserTable extends Component
{
    public $users;

    public function mount()
    {
        $this->users = User::all();
    }

    public function confirmDelete($id)
    {
        $this->dispatch('showDeleteAlert', id: $id);
    }

    #[On('deleteConfirmed')]
    public function deleteUser($id)
    {
        User::find($id)?->delete();
        $this->users = User::all();

        $this->dispatch('showSuccess', message: 'User deleted successfully!');
    }

    public function render()
    {
        return view('livewire.user-table');
    }
}
