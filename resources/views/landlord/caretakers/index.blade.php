@extends('layouts.app')

@section('page-title', 'Caretaker Accountability')

@section('content')
    <div class="mb-4">
        <h5 class="fw-bold mb-1">Caretaker Accountability</h5>
        <p class="text-muted mb-0" style="font-size:0.85rem;">
            Evidence-based work signals across your caretakers. These are informational and never used to rank, penalise, suspend or dismiss a caretaker.
        </p>
    </div>

    <!-- Period filter -->
    <div class="stat-card mb-4">
        <form method="GET" action="{{ route('landlord.caretakers.index') }}" class="row g-3 align-items-end">
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" class="form-control" value="{{ $from }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" class="form-control" value="{{ $to }}">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-funnel"></i> Apply</button>
                <a href="{{ route('landlord.caretakers.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>

    <div class="stat-card">
        @if($summaries->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-person-check" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No caretakers linked to your properties yet.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Caretaker</th>
                            <th>Assigned</th>
                            <th>Open</th>
                            <th>Overdue</th>
                            <th>Completed</th>
                            <th>On-time</th>
                            <th>First-time right</th>
                            <th>Rework</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summaries as $summary)
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $summary['caretaker']->name }}</div>
                                    <small class="text-muted">
                                        {{ $summary['caretaker']->email ?? '' }}
                                        @if($summary['sample_size'] === 0) &bull; no tasks in period @endif
                                    </small>
                                </td>
                                <td>{{ $summary['sample_size'] }}</td>
                                <td>{{ $summary['open'] }}</td>
                                <td class="{{ $summary['overdue_open'] > 0 ? 'text-danger fw-semibold' : '' }}">{{ $summary['overdue_open'] }}</td>
                                <td>{{ $summary['completed'] }}</td>
                                <td>{{ $summary['on_time_rate'] !== null ? $summary['on_time_rate'].'%' : '—' }}</td>
                                <td>{{ $summary['first_time_right_rate'] !== null ? $summary['first_time_right_rate'].'%' : '—' }}</td>
                                <td>{{ $summary['tasks_returned_for_rework'] }}</td>
                                <td>
                                    <a href="{{ route('landlord.caretakers.show', [$summary['caretaker'], 'from' => $from, 'to' => $to]) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-muted mb-0 mt-3" style="font-size:0.78rem;">
                Period: {{ $summaries->first()['period_label'] }}. Percentages are shown only alongside their sample size.
            </p>
        @endif
    </div>
@endsection
