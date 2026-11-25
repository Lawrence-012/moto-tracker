<section class="space-y-6">
    <header>
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-4 h-4 text-red-600">
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
            </div>
            <div class="ml-2">
                <h2 class="text-base font-medium text-red-600">
                    {{ __('Delete Account') }}
                </h2>
            </div>
        </div>
        <p class="mt-1 text-xs text-gray-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button 
        x-data=""
        x-on:click.prevent="handleDeleteAccount()"
        type="button"
        class="inline-flex items-center px-3 py-1.5 bg-red-600 border border-transparent rounded-md font-medium text-white text-sm hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150"
    >
        {{ __('Delete Account') }}
    </button>

    <script>
    function handleDeleteAccount() {
        // Show confirmation dialog
        Swal.fire({
            title: 'Are you absolutely sure?',
            text: 'This action cannot be undone. You will lose all your account data permanently!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete my account!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading alert
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete your account.',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Create form data for the delete request
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('_method', 'DELETE');

                // Submit the delete request via AJAX
                fetch('{{ route('profile.destroy') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (response.ok) {
                        // Close loading and show success message
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Your account has been deleted successfully.',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '/'; // Redirect to home page
                        });
                    } else {
                        // Handle error response
                        return response.json().then(data => {
                            throw new Error(data.message || 'An error occurred while deleting your account.');
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: error.message || 'An error occurred while deleting your account.'
                    });
                });
            }
        });
    }
    </script>
</section>
