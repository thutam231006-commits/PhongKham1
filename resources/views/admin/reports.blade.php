@extends('layouts.app')
@section('title', 'Báo cáo vận hành')
@section('content')
<div class="mx-auto max-w-screen-xl space-y-6 px-4 py-8 sm:px-6 lg:px-8">
    <div><h1 class="text-3xl font-black text-[#2D82C4]">Báo cáo vận hành</h1><p class="mt-2 text-sm text-[#4A5568]">Số liệu tổng hợp, không chứa thông tin định danh hoặc nội dung tư vấn.</p></div>
    <form method="GET" class="flex flex-wrap gap-3 rounded-xl border border-gray-200 bg-white p-4"><input type="date" name="from" value="{{ $from->toDateString() }}" class="rounded-lg border-gray-300 text-sm"><input type="date" name="to" value="{{ $to->toDateString() }}" class="rounded-lg border-gray-300 text-sm"><button class="rounded-lg bg-[#2D82C4] px-4 py-2 text-sm font-bold text-white">Lọc báo cáo</button><a href="{{ route('admin.reports.export', ['from' => $from->toDateString(), 'to' => $to->toDateString()]) }}" class="rounded-lg border border-[#2D82C4] px-4 py-2 text-sm font-bold text-[#2D82C4]">Xuất CSV</a></form>
    <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach([['Tiền thực thu',$financial['collected']],['Tiền đã hoàn',$financial['refunded']],['Doanh thu thuần',$financial['net_revenue']],['Tiền chờ hoàn',$financial['pending_refunds']]] as $card)<div class="rounded-xl border border-gray-200 bg-white p-5"><p class="text-xs text-[#4A5568]">{{ $card[0] }}</p><p class="mt-2 text-xl font-black text-[#2D82C4]">{{ number_format($card[1],0,',','.') }} VNĐ</p></div>@endforeach</section>
    <div class="grid gap-6 lg:grid-cols-3"><section class="rounded-xl border border-gray-200 bg-white p-5"><h2 class="font-bold">Theo trạng thái</h2><dl class="mt-4 space-y-2 text-sm">@forelse($byStatus as $status=>$total)<div class="flex justify-between"><dt>{{ \App\Support\AppointmentStatus::label($status) }}</dt><dd class="font-bold">{{ $total }}</dd></div>@empty<p class="text-[#4A5568]">Không có dữ liệu.</p>@endforelse</dl></section><section class="rounded-xl border border-gray-200 bg-white p-5"><h2 class="font-bold">Theo dịch vụ</h2><dl class="mt-4 space-y-2 text-sm">@forelse($byService as $row)<div class="flex justify-between gap-3"><dt class="truncate">{{ $row->name }}</dt><dd class="font-bold">{{ $row->total }}</dd></div>@empty<p class="text-[#4A5568]">Không có dữ liệu.</p>@endforelse</dl></section><section class="rounded-xl border border-gray-200 bg-white p-5"><h2 class="font-bold">Theo ngày</h2><dl class="mt-4 space-y-2 text-sm">@forelse($byDay as $row)<div class="flex justify-between"><dt>{{ $row->day }}</dt><dd class="font-bold">{{ $row->total }}</dd></div>@empty<p class="text-[#4A5568]">Không có dữ liệu.</p>@endforelse</dl></section></div>

    {{-- ── LƯU LƯỢNG THEO THỜI GIAN (Yêu cầu 1) ── --}}
    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-bold text-[#102A43]">Lưu lượng bệnh nhân theo ngày</h2>
        <p class="mt-1 text-xs text-[#4A5568]">Số bệnh nhân khác nhau, tổng số lịch và số lịch bị hủy trong từng ngày của kỳ lọc.</p>

        @php($peakDay = $patientsByDay->sortByDesc('total')->first())
        <div class="mt-5 overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-[#F7FBFE] text-xs uppercase text-[#52637A]">
                    <tr>
                        <th class="px-4 py-3">Ngày</th>
                        <th class="px-4 py-3">Bệnh nhân</th>
                        <th class="px-4 py-3">Tổng lịch</th>
                        <th class="px-4 py-3">Đã hủy</th>
                        <th class="px-4 py-3">Mức tải</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#EEF4F8]">
                    @foreach($patientsByDay as $row)
                        @php($percent = $peakDay && $peakDay['total'] > 0 ? (int) round($row['total'] / $peakDay['total'] * 100) : 0)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 font-semibold">{{ \Carbon\CarbonImmutable::parse($row['day'])->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 font-bold text-[#2D82C4]">{{ $row['new_patients'] }}</td>
                            <td class="px-4 py-3">{{ $row['total'] }}</td>
                            <td class="px-4 py-3 {{ $row['cancelled'] > 0 ? 'font-bold text-red-700' : 'text-[#718096]' }}">{{ $row['cancelled'] }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-28 rounded-full bg-gray-100">
                                        <div class="h-2 rounded-full bg-[#2D82C4]" style="width: {{ $percent }}%"></div>
                                    </div>
                                    <span class="text-xs text-[#718096]">{{ $percent }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    <section class="rounded-xl border border-gray-200 bg-white p-6">
        <h2 class="text-lg font-bold text-[#102A43]">Lưu lượng theo khung giờ trong ngày {{ \Carbon\CarbonImmutable::parse($to)->format('d/m/Y') }}</h2>
        <p class="mt-1 text-xs text-[#4A5568]">
            Giờ nào khách đến nhiều nhất — phục vụ điều phối nhân sự.
            @if($peakHour && $peakHour['total'] > 0)
                Giờ cao điểm: <strong class="text-[#2D82C4]">{{ str_pad($peakHour['hour'], 2, '0', STR_PAD_LEFT) }}:00</strong> với {{ $peakHour['total'] }} lịch.
            @endif
        </p>
        <div class="mt-5 flex flex-wrap items-end gap-2">
            @foreach($hourlyTraffic as $hour)
                @php($barHeight = $peakHour && $peakHour['total'] > 0 ? max(6, (int) round($hour['total'] / $peakHour['total'] * 100)) : 0)
                <div class="flex w-10 flex-col items-center gap-1.5">
                    <span class="text-xs font-bold text-[#102A43]">{{ $hour['total'] }}</span>
                    <div class="w-full rounded-t bg-[#2D82C4] {{ $barHeight === 0 ? 'bg-gray-100' : '' }}" style="height: {{ max(6, $barHeight) }}px" title="{{ str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) }}:00 — {{ $hour['total'] }} lịch"></div>
                    <span class="text-[10px] text-[#718096]">{{ str_pad($hour['hour'], 2, '0', STR_PAD_LEFT) }}h</span>
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
