@php
    $i = (int) ($index ?? 0) % 6;
    $tone = $tone ?? 'light';
    $stroke = $tone === 'inverse' ? 'text-white' : 'text-red-600';
@endphp

@if($i === 0)
    <svg class="h-7 w-7 {{ $stroke }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503-8.25 5.718-1.5m-11.436 0L9 6.75m6 3.75v8.25m0-8.25L3.75 6.75m6 3.75 5.436 0m-11.436 0L15 6.75" /></svg>
@elseif($i === 1)
    <svg class="h-7 w-7 {{ $stroke }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008H17.25v-.008zm0 3.75h.008v.008H17.25v-.008zm0 3.75h.008v.008H17.25v-.008z" /></svg>
@elseif($i === 2)
    <svg class="h-7 w-7 {{ $stroke }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
@elseif($i === 3)
    <svg class="h-7 w-7 {{ $stroke }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
@elseif($i === 4)
    <svg class="h-7 w-7 {{ $stroke }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.125 1.125 0 01-1.125-1.125v-2.25a1.125 1.125 0 011.125-1.125h2.25a1.125 1.125 0 011.125 1.125v2.25a1.125 1.125 0 01-1.125 1.125h-2.25zM15.75 18.75a1.125 1.125 0 01-1.125-1.125v-2.25a1.125 1.125 0 011.125-1.125h2.25a1.125 1.125 0 011.125 1.125v2.25a1.125 1.125 0 01-1.125 1.125h-2.25zM8.25 9.75a1.125 1.125 0 01-1.125-1.125V6.375a1.125 1.125 0 011.125-1.125h2.25A1.125 1.125 0 0111.25 6.375V8.625A1.125 1.125 0 019.375 9.75h-2.25zM15.75 9.75a1.125 1.125 0 01-1.125-1.125V6.375a1.125 1.125 0 011.125-1.125h2.25A1.125 1.125 0 0119.875 6.375V8.625A1.125 1.125 0 0118.75 9.75h-2.25z" /></svg>
@else
    <svg class="h-7 w-7 {{ $stroke }}" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
@endif
