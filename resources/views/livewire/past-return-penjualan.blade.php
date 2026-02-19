<div>
    @if ($this->isNotaAvailable)
        <div style="margin-bottom: 1.5rem;" wire:key="wrapper-retur">
            {{-- Nothing in the world is as soft and yielding as water. --}}
            {{ $this->table }}
        </div>
    @endif
</div>
