<table>
    <thead>
        <tr class="header-item">
            <th class="" style="font-weight: bold;">ENROLLMENT ID</th>
            <th class="" style="font-weight: bold;">NAME</th>
            <th class="" style="font-weight: bold;">COURSE</th>
            <th class="" style="font-weight: bold;">INTAKE</th>
            <th class="" style="font-weight: bold;">PAID AMOUNT</th>
            <th class="" style="font-weight: bold;">BALANCE</th>
            <th class="" style="font-weight: bold;">APPROVED ON</th>
        </tr>
    </thead>

    <tbody>
        @php
            $totalBal=0;
        @endphp
        @foreach ($enrollments as $enrollment)
            <tr>
                <td>{{ 'TTI/' . $enrollment->student?->admission_number . '/' . $enrollment->course?->code . '/' . $enrollment->created_at->format('Y') }}</td>
                <td>{{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}</td>
                <td>{{ $enrollment->course?->title }}</td>
                <td>{{ $enrollment->intake?->name }}</td>
                <td>{{ $enrollment->payments?->sum('amount') }}</td>
                <td>{{ $enrollment->course?->price - $enrollment->payments?->sum('amount') }}</td>
                <td>{{ $enrollment->created_at->format('d-m-Y') }}</td>
            </tr>
            @php
                $totalBal += $enrollment->course?->price - $enrollment->payments?->sum('amount');
            @endphp
        @endforeach
        <tr></tr>
        <tr>
            <td colspan="4"></td>
            <td>Total</td>
            <td>{{ $totalBal }}</td>
            <td></td>
        </tr>
    </tbody>
</table>
