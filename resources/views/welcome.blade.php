@extends('layouts.app')

@section('title', 'KejaPad Dashboard')
@section('body_class', 'dashboard-page')

@section('content')
<main class="page">
  <section class="hero-copy">
    <div>
      <div class="eyebrow">Landlord and tenant command center</div>
      <h1>KejaPad</h1>
      <p class="lead">{{ $property->name ?? 'Your property' }} brings rent, repairs, disputes, roommates, and building feedback into one accountable workspace.</p>
    </div>
    <div class="trust-strip">
      <div><strong>{{ $score->score ?? 650 }}</strong><span>Rent score</span></div>
      <div><strong>{{ $ticket->eta_minutes ?? 0 }}m</strong><span>Repair ETA</span></div>
      <div><strong>{{ $paidPercent }}%</strong><span>Rent pot funded</span></div>
    </div>
  </section>

  <section class="page-panel">
    <div class="panel-head">
      <div><h2>{{ auth()->user()->role === 'landlord' ? 'Landlord cockpit' : 'Tenant cockpit' }}</h2><p>{{ $unit->label ?? 'Unit' }}, {{ $property->name ?? 'Property' }} - {{ $rentPot->month ?? now()->format('F Y') }}</p></div>
      <div class="status-pill">{{ auth()->user()->role }} mode</div>
    </div>
    <div class="content-grid">
      <article class="module wide">
        <div class="module-head"><div><h3>Good Tenant Score</h3><p>On-time rent becomes visible rental reputation.</p></div><div class="status-pill">{{ $score->on_time_payments ?? 0 }} on-time payments</div></div>
        <div class="score-wrap">
          <div class="score-ring"><div><strong>{{ $score->score ?? 650 }}</strong><span>{{ ($score->score ?? 0) >= 800 ? 'Excellent' : 'Building' }}</span></div></div>
          <div class="list">
            @forelse ($rewards as $reward)
              <div class="item"><b>{{ $reward }}</b><span>{{ $loop->first ? 'Unlocked' : 'Active' }}</span></div>
            @empty
              <div class="item"><b>Certified reference</b><span>Start paying rent to unlock</span></div>
            @endforelse
          </div>
        </div>
      </article>
      <article class="module"><div class="module-head"><div><h3>Next rent</h3><p>{{ $rentPot->month ?? 'Current cycle' }}</p></div><div class="status-pill">{{ $paidPercent }}% funded</div></div><div class="list"><div class="item"><b>KSh {{ number_format($paidTotal) }} collected</b><span>of {{ number_format($rentTotal) }}</span></div><div class="item"><b>{{ $pendingPayments }} tenant pending</b><span>No house penalty</span></div></div></article>
      <article class="module"><div class="module-head"><div><h3>Open repair</h3><p>{{ $ticket->title ?? 'No open repair' }}</p></div><div class="status-pill">{{ $ticket->status ?? 'clear' }}</div></div><div class="list"><div class="item"><b>{{ $ticket->contractor_name ?? 'No contractor assigned' }}</b><span>{{ $ticket->eta_minutes ?? 0 }} min ETA</span></div><div class="item"><b>Contractor rating</b><span>{{ $ticket->contractor_rating ?? 0 }} stars</span></div></div></article>
    </div>
  </section>
</main>
@endsection
