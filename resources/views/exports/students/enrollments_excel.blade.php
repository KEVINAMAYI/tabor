<table>
    <thead>
        <tr class="header-item">
            <th style="font-weight: bold;">ENROLLMENT ID</th>
            <th style="font-weight: bold;">NAME</th>
            <th style="font-weight: bold;">COURSE</th>
            <th style="font-weight: bold;">INTAKE</th>
            {{-- <th style="font-weight: bold;">CHARGES</th>
            <th style="font-weight: bold;">DISCOUNTS / CREDITS</th>
            <th style="font-weight: bold;">PAID AMOUNT</th>
            <th style="font-weight: bold;">BALANCE</th>
            <th style="font-weight: bold;">APPROVED ON</th> --}}
        </tr>
    </thead>

    <tbody>
        @php
            $totalCharges = 0;
            $totalCredits = 0;
            $totalPaid = 0;
            $totalBalance = 0;
        @endphp

        @foreach ($enrollments as $enrollment)
            @php
                $feeItems = $enrollment->studentFeeItems ?? collect();

                $charges = (float) $feeItems
                    ->where('amount', '>', 0)
                    ->sum('amount');

                $credits = abs((float) $feeItems
                    ->where('amount', '<', 0)
                    ->sum('amount'));

                $paid = (float) $feeItems
                    ->where('amount', '>', 0)
                    ->sum('amount_paid');

                $balance = (float) $feeItems->sum('balance');

                $totalCharges += $charges;
                $totalCredits += $credits;
                $totalPaid += $paid;
                $totalBalance += $balance;
            @endphp

            <tr>
                <td>
                    {{ 'TTI/' . $enrollment->student?->admission_number . '/' . $enrollment->course?->code . '/' . $enrollment->created_at->format('Y') }}
                </td>

                <td>
                    {{ $enrollment->student?->first_name }} {{ $enrollment->student?->last_name }}
                </td>

                <td>
                    {{ $enrollment->course?->title }}
                </td>

                <td>
                    {{  $enrollment->assignedStartTrimester?->name ?? '—' }} {{ $enrollment->assignedStartTrimester?->academicYear?->name ?? '—' }}
                </td>

                {{-- <td>
                    {{ number_format($charges, 2) }}
                </td>

                <td>
                    {{ number_format($credits, 2) }}
                </td>

                <td>
                    {{ number_format($paid, 2) }}
                </td>

                <td>
                    {{ number_format($balance, 2) }}
                </td>

                <td>
                    {{ $enrollment->created_at->format('d-m-Y') }}
                </td> --}}
            </tr>
        @endforeach

        <tr>
            <td colspan="4"></td>
            <td><strong>{{ number_format($totalCharges, 2) }}</strong></td>
            <td><strong>{{ number_format($totalCredits, 2) }}</strong></td>
            <td><strong>{{ number_format($totalPaid, 2) }}</strong></td>
            <td><strong>{{ number_format($totalBalance, 2) }}</strong></td>
            <td></td>
        </tr>
    </tbody>
</table>
