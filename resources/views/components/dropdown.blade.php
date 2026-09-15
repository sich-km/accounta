@props(['align' => 'right', 'width' => '48', 'contentClasses' => 'py-1 bg-white', 'dropdownClasses' => '', 'teleport' => false])

@php
$alignmentClasses = match ($align) {
    'left' => 'ltr:origin-top-left rtl:origin-top-right start-0',
    'top' => 'origin-top',
    'none', 'false' => '',
    default => 'ltr:origin-top-right rtl:origin-top-left end-0',
};

$width = match ($width) {
    '48' => 'w-48',
    '60' => 'w-60',
    default => 'w-48',
};
@endphp

<div
    class="relative"
    x-data="{
        open: false,
        menuPositioned: false,
        menuTop: 0,
        menuLeft: 0,
        toggle() {
            if (this.open) {
                this.close();

                return;
            }

            this.open = true;

            if ({{ $teleport ? 'true' : 'false' }}) {
                this.menuPositioned = false;
                this.$nextTick(() => {
                    if (! this.open) {
                        return;
                    }

                    this.positionMenu();
                    this.menuPositioned = true;
                });
            }
        },
        close() {
            this.open = false;
            this.menuPositioned = false;
        },
        positionMenu() {
            const trigger = this.$refs.trigger.getBoundingClientRect();
            const menu = this.$refs.menu;
            const gap = 8;
            let top = trigger.bottom + gap;

            if (top + menu.offsetHeight > window.innerHeight - gap) {
                top = trigger.top - menu.offsetHeight - gap;
            }

            this.menuTop = Math.max(gap, top);
            this.menuLeft = Math.min(
                Math.max(gap, trigger.right - menu.offsetWidth),
                window.innerWidth - menu.offsetWidth - gap,
            );
        },
    }"
    @click.away="close()"
    @close.stop="close()"
    @keydown.escape.window="close()"
    @scroll.window="close()"
    @resize.window="close()"
>
    <div x-ref="trigger" @click="toggle()">
        {{ $trigger }}
    </div>

    @if ($teleport)
        <template x-teleport="body">
            <div x-show="open" class="fixed inset-0 z-50" style="display: none;" @click.self="close()">
                <div
                    x-ref="menu"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute {{ $width }} rounded-md shadow-lg {{ $dropdownClasses }}"
                    :style="menuPositioned
                        ? `top: ${menuTop}px; left: ${menuLeft}px;`
                        : 'visibility: hidden;'"
                    @click="close()"
                >
                    <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
                        {{ $content }}
                    </div>
                </div>
            </div>
        </template>
    @else
        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute z-50 mt-2 {{ $width }} rounded-md shadow-lg {{ $alignmentClasses }} {{ $dropdownClasses }}"
            style="display: none;"
            @click="close()"
        >
            <div class="rounded-md ring-1 ring-black ring-opacity-5 {{ $contentClasses }}">
                {{ $content }}
            </div>
        </div>
    @endif
</div>
