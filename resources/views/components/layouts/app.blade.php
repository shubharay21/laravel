<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Livewire SweetAlert Example</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

    <!-- SweetAlert2 -->


</head>

<body>

    {{ $slot }}

    @livewireScripts

    <script>
        document.addEventListener('livewire:init', () => {

            Livewire.on('showDeleteAlert', (data) => {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you really want to delete this user?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Livewire.dispatch('deleteConfirmed', {
                            id: data.id
                        });
                    }
                });
            });

            Livewire.on('showSuccess', (data) => {
                Swal.fire({
                    title: 'Success!',
                    text: data.message,
                    icon: 'success'
                });
            });

        });
    </script>

</body>

</html>