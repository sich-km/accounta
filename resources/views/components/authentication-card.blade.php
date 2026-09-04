<div class="min-h-screen flex flex-col items-center bg-gradient-to-br from-sky-50 via-white to-cyan-50 pt-6 sm:justify-center sm:pt-0">
    <div>
        {{ $logo }}
    </div>

    <div class="mt-6 w-full overflow-hidden border border-sky-100 bg-white px-6 py-4 shadow-lg shadow-sky-100/60 sm:max-w-md sm:rounded-xl">
        {{ $slot }}
    </div>
</div>
