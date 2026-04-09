<div>
    @if(session()->has('registration_success'))
        <script>
            window.dispatchEvent(new CustomEvent('show-notification', {
                detail: {
                    title: 'Регистрация успешна!',
                    body: 'Письмо с подтверждением отправлено на {{ session('registration_email') }}. Пожалуйста, проверьте почту.',
                    status: 'success'
                }
            }));
        </script>
    @endif

    @if(session()->has('filament.notifications'))
        @foreach(session()->get('filament.notifications') as $notification)
            <script>
                window.dispatchEvent(new CustomEvent('show-notification', {
                    detail: @json($notification)
                }));
            </script>
        @endforeach
    @endif
</div>