@extends('admin.layouts.app')

@section('content')

    <div class="d-flex justify-content-between mb-3">
        <h3>Attendance Management</h3>
    </div>

    <h5 class="mb-4">Total Entry Days: <strong>{{ $totalEntryDays }}</strong></h5>

    <div class="table-responsive">
        <table class="table table-bordered table-hover">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Seller Name</th>
                    <th>Present Days</th>
                    <th colspan="{{ count($activeMonths) }}" class="text-center">Attendance Details (Year 2026)</th>
                </tr>
                <tr>
                    <th colspan="3"></th>
                    @foreach ($activeMonths as $month)
                        <th class="text-center">{{ substr($months[$month], 0, 3) }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($attendanceData as $index => $data)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $data['seller']->name }}</td>
                        <td class="text-center"><strong>{{ $data['present_days'] }}</strong></td>
                        @foreach ($activeMonths as $month)
                            <td class="text-center">
                                <span class="badge {{ $data['monthly'][$month] > 0 ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $data['monthly'][$month] }}
                                </span>
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ 3 + count($activeMonths) }}" class="text-center">No sellers found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection
