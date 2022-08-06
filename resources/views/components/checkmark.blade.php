<div {{ $attributes->class('inline-block w-[18px] h-[18px] bg-gray-50 rounded border flex items-center justify-center') }} {{ $attributes->except('checked', 'class') }}>
    @if($checked)
    <svg viewBox="0 0 18 18" fill="currentColor" width="18" height="18"><path d="M11.9393398,6 C12.232233,5.70710678 12.7071068,5.70710678 13,6 C13.2928932,6.29289322 13.2928932,6.76776695 13,7.06066017 L7.5,12.5606602 L5,10.0606602 C4.70710678,9.76776695 4.70710678,9.29289322 5,9 C5.29289322,8.70710678 5.76776695,8.70710678 6.06066017,9 L7.5,10.4393398 L11.9393398,6 Z"></path></svg>
    @endif
</div>
