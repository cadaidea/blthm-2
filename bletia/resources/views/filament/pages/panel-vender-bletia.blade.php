<x-filament-panels::page>
    @php($a = $this->accesos())

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:14px">
        @foreach($a['principales'] as $b)
            <a href="{{ $b['u'] }}" style="text-decoration:none;display:block;background:var(--gray-50,#f9fafb);border:1px solid var(--gray-200,#e5e7eb);border-left:5px solid {{ $b['c'] }};border-radius:14px;padding:18px;transition:.15s" onmouseover="this.style.boxShadow='0 6px 18px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow='none'">
                <x-filament::icon :icon="$b['i']" style="width:30px;height:30px;color:{{ $b['c'] }}" />
                <div style="font-weight:700;font-size:16px;margin-top:8px;color:var(--gray-900,#111827)">{{ $b['t'] }}</div>
                <div style="font-size:13px;color:var(--gray-500,#6b7280)">{{ $b['d'] }}</div>
            </a>
        @endforeach
    </div>

    <div style="font-weight:700;font-size:14px;color:var(--gray-500,#6b7280);margin:22px 0 8px">Listas</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px">
        @foreach($a['listas'] as $b)
            <a href="{{ $b['u'] }}" style="text-decoration:none;display:flex;align-items:center;gap:10px;background:var(--gray-50,#f9fafb);border:1px solid var(--gray-200,#e5e7eb);border-radius:12px;padding:14px;color:var(--gray-900,#111827)" onmouseover="this.style.background='var(--gray-100,#f3f4f6)'" onmouseout="this.style.background='var(--gray-50,#f9fafb)'">
                <x-filament::icon :icon="$b['i']" style="width:22px;height:22px;color:#161921" />
                <span style="font-weight:600;font-size:14px">{{ $b['t'] }}</span>
            </a>
        @endforeach
    </div>
</x-filament-panels::page>
