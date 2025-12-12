<x-layouts.app>

    <style>
        .blink-text {
            animation: blink 1s infinite;
            color: green;
        }

        @keyframes blink {
            0% {
                opacity: 1;
            }

            50% {
                opacity: 0;
            }

            100% {
                opacity: 1;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                html: '<span class="blink-text" style="font-size:22px; font-weight:bold;">You are Online</span>',
                showConfirmButton: false,
                background: '#fff',
                allowOutsideClick: false,
            });
        });
    </script>

</x-layouts.app>