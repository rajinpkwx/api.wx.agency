@extends('layouts.dashboard')

@section('page-title', 'Dashboard')

@push('styles')
<style>
    .dash-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 28px;
    }

    @media (max-width: 1100px) { .dash-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 560px)  { .dash-grid { grid-template-columns: 1fr; } }

    .stat-card {
        background: #0f0f1a;
        border: 1px solid rgba(255,255,255,0.07);
        border-radius: 14px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: border-color 0.2s, transform 0.2s;
    }

    .stat-card:hover {
        border-color: rgba(99,102,241,0.3);
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .stat-icon svg { width: 22px; height: 22px; }

    .stat-icon.indigo  { background: rgba(99,102,241,0.15); color: #818cf8; }
    .stat-icon.rose    { background: rgba(244,63,94,0.15);  color: #fb7185; }
    .stat-icon.amber   { background: rgba(245,158,11,0.15); color: #fbbf24; }
    .stat-icon.emerald { background: rgba(16,185,129,0.15); color: #34d399; }

    .stat-body {}

    .stat-label {
        font-size: 0.75rem;
        font-weight: 500;
        color: #64748b;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        margin-bottom: 4px;
    }

    .stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #fff;
        letter-spacing: -0.02em;
        line-height: 1;
    }

    /* Section heading */
    .section-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
    }

    .section-head h2 {
        font-size: 0.9rem;
        font-weight: 600;
        color: #fff;
    }

    /* Empty state */
    .empty-state {
        background: #0f0f1a;
        border: 1px dashed rgba(255,255,255,0.1);
        border-radius: 14px;
        padding: 56px 24px;
        text-align: center;
        color: #475569;
        font-size: 0.85rem;
    }

    .empty-state svg {
        width: 36px; height: 36px;
        margin: 0 auto 12px;
        color: #334155;
        display: block;
    }
</style>
@endpush

@section('content')

<div class="dash-grid">
    <div class="stat-card">
        <div class="stat-icon indigo">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-label">Invoices</div>
            <div class="stat-value">0</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon rose">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2M9 11a4 4 0 100-8 4 4 0 000 8zM23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-label">Users</div>
            <div class="stat-value">0</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon amber">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-label">Transactions</div>
            <div class="stat-value">0</div>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-icon emerald">
            <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/>
            </svg>
        </div>
        <div class="stat-body">
            <div class="stat-label">Total Payments</div>
            <div class="stat-value">$0</div>
        </div>
    </div>
</div>

<div class="section-head">
    <h2>Recent Activity</h2>
</div>

<div class="empty-state">
    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
        <path d="M9 17H5a2 2 0 00-2 2v0a2 2 0 002 2h14a2 2 0 002-2v0a2 2 0 00-2-2h-4M12 3v14M9 6l3-3 3 3"/>
    </svg>
    No recent activity to display.
</div>

@endsection
