@extends('layouts.app')

@section('page-title', 'My Work History')

@section('content')
    <div class="kp-page-header">
        <div>
            <h1 class="kp-page-title">My Work History</h1>
            <p class="kp-page-subtitle">
                An evidence-based summary of your maintenance work: {{ $summary['period_label'] }}.
            </p>
        </div>
        <span class="kp-badge kp-badge-purple">Sample size: {{ $summary['sample_size'] }} tasks</span>
    </div>

    <!-- Period filter -->
    <div class="kp-card mb-4">
        <form method="GET" action="{{ route('caretaker.history.index') }}" class="row g-3 align-items-end">
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">From</label>
                <input type="date" name="from" class="form-control" value="{{ request('from') }}">
            </div>
            <div class="col-6 col-md-4">
                <label class="form-label fw-semibold">To</label>
                <input type="date" name="to" class="form-control" value="{{ request('to') }}">
            </div>
            <div class="col-12 col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-outline-primary flex-fill"><i class="bi bi-funnel"></i> Apply</button>
                <a href="{{ route('caretaker.history.index') }}" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
            </div>
        </form>
    </div>

    <!-- Metrics -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold mb-0">{{ $summary['assigned'] }}</h4>
                <small class="text-muted">Assigned</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-warning mb-0">{{ $summary['open'] }}</h4>
                <small class="text-muted">Open</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-danger mb-0">{{ $summary['overdue_open'] }}</h4>
                <small class="text-muted">Overdue (open)</small>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-center">
                <h4 class="fw-bold text-success mb-0">{{ $summary['completed'] }}</h4>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Quality Signals</h6>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">Completed on time</span>
                    <span class="fw-semibold">{{ $summary['completed_on_time'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">Completed late</span>
                    <span class="fw-semibold">{{ $summary['completed_late'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">On-time rate</span>
                    <span class="fw-semibold">{{ $summary['on_time_rate'] !== null ? $summary['on_time_rate'].'%' : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">First-time right</span>
                    <span class="fw-semibold">{{ $summary['first_time_right_rate'] !== null ? $summary['first_time_right_rate'].'%' : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Returned for rework</span>
                    <span class="fw-semibold">{{ $summary['tasks_returned_for_rework'] }}</span>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-6">
            <div class="stat-card h-100">
                <h6 class="fw-bold mb-3 pb-2 border-bottom">Timing</h6>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">Avg response time</span>
                    <span class="fw-semibold">{{ $summary['avg_response_hours'] !== null ? $summary['avg_response_hours'].' h' : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">Avg resolution time</span>
                    <span class="fw-semibold">{{ $summary['avg_resolution_hours'] !== null ? $summary['avg_resolution_hours'].' h' : '—' }}</span>
                </div>
                <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid var(--kp-border);">
                    <span class="text-muted">Awaiting verification</span>
                    <span class="fw-semibold">{{ $summary['awaiting_verification'] }}</span>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Verified</span>
                    <span class="fw-semibold">{{ $summary['verified'] }}</span>
                </div>
                <p class="text-muted mb-0 mt-2" style="font-size:0.78rem;">
                    A deadline that was legitimately extended counts against the current deadline only.
                </p>
            </div>
        </div>
    </div>

    @if(!empty($summary['by_property']))
        <div class="stat-card mb-4">
            <h6 class="fw-bold mb-3 pb-2 border-bottom">By Property</h6>
            <div class="table-responsive">
                <table class="table align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Property</th>
                            <th>Assigned</th>
                            <th>Open</th>
                            <th>Overdue</th>
                            <th>Completed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($summary['by_property'] as $row)
                            <tr>
                                <td>{{ $row['property'] }}</td>
                                <td>{{ $row['assigned'] }}</td>
                                <td>{{ $row['open'] }}</td>
                                <td>{{ $row['overdue'] }}</td>
                                <td>{{ $row['completed'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Task list -->
        <div class="kp-card">
        <h6 class="fw-bold mb-3 pb-2 border-bottom">Tasks</h6>
        @if($tasks->isEmpty())
            <div class="text-center py-5">
                <i class="bi bi-clipboard-data" style="font-size:3rem; color:#dee2e6;"></i>
                <p class="mt-3 text-muted">No tasks in this period.</p>
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Title</th>
                            <th>Property / Unit</th>
                            <th>Assigned</th>
                            <th>Due</th>
                            <th>Status</th>
                            <th>Outcome</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tasks as $task)
                            <tr>
                                <td>{{ $task->title }}</td>
                                <td>
                                    <div>{{ $task->property?->name }}</small></div>
                                    <small class="text-muted">{{ $task->unit?->unit_number }}</small>
                                </td>
                                <td>{{ optional($task->assigned_at ?? $task->created_at)->format('d M Y') }}</td>
                                <td>{{ $task->due_date?->format('d M Y') ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $task->statusColor() }}">{{ ucfirst(str_replace('_', ' ', $task->status)) }}</span>
                                </td>
                                <td>
                                    @if($task->isVerified())
                                        <span class="kp-badge kp-badge-success">Verified</span>
                                    @elseif($task->isAwaitingVerification())
                                        <span class="kp-badge kp-badge-warning">Awaiting</span>
                                    @elseif($task->isOverdue())
                                        <span class="kp-badge kp-badge-danger">Overdue</span>
                                    @else
                                        <span class="kp-badge kp-badge-muted">Open</span>
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('caretaker.maintenance.show', $task) }}" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    @if($tasks->hasPages())
        <div class="mt-3">{{ $tasks->links() }}</div>
    @endif
@endsection
