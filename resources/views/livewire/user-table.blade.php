<div class="p-4">
    <h2 class="text-xl mb-4">User List</h2>

    <table border="1" cellpadding="10" cellspacing="0">
        @foreach ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>
                    <button wire:click="confirmDelete({{ $user->id }})"
                        style="background:red;color:white;padding:6px 12px;border:none;border-radius:3px;">
                        Delete
                    </button>
                </td>
            </tr>
        @endforeach
    </table>
</div>
