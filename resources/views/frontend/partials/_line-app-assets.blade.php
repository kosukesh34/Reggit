    @php
        // LINE and tunnel browsers cannot reach a Vite server bound to this computer.
        $lineVite = clone app(\Illuminate\Foundation\Vite::class);
        $localHosts = ['localhost', '127.0.0.1', '::1', '[::1]', '0.0.0.0'];
        if ($lineVite->isRunningHot() && ! in_array(request()->getHost(), $localHosts, true)) {
            $hotHost = parse_url(trim(file_get_contents($lineVite->hotFile())), PHP_URL_HOST);
            if (in_array($hotHost, $localHosts, true)) {
                $lineVite->useHotFile('');
            }
        }
    @endphp
    {{ $lineVite->reactRefresh() }}
    {{ $lineVite(['resources/js/frontend/main.tsx']) }}
