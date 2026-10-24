@props(['priority'])
<span class="inline-flex h-[23px] items-center whitespace-nowrap rounded-full px-2 text-[10px] font-semibold {{ $priority->badge() }}">{{ $priority->label() }}</span>
