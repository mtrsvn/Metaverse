@extends('layouts.app')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
  <h2>Purchase Records</h2>
  <form class="d-flex align-items-center gap-2" method="get" action="">
    <label for="status" class="form-label mb-0">Status</label>
    <select name="status" id="status" class="form-select" style="width: 180px;" onchange="this.form.submit()">
      <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
      <option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pending</option>
      <option value="approved" {{ $status === 'approved' ? 'selected' : '' }}>Approved</option>
      <option value="rejected" {{ $status === 'rejected' ? 'selected' : '' }}>Rejected</option>
    </select>
  </form>
</div>

@if ($records->isEmpty())
  <div class="card">
    <div class="card-body text-center py-4">No purchase records found for this filter.</div>
  </div>
@else
  <div class="table-responsive">
    <table class="table align-middle">
      <thead>
        <tr>
          <th style="min-width: 150px;">Order Time</th>
          <th>User</th>
          <th>Email</th>
          <th>Items</th>
          <th class="text-center">Qty</th>
          <th class="text-end">Total</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($records as $row)
        <tr>
          <td>{{ $row->order_time ?? '' }}</td>
          <td>{{ $row->username ?? 'Unknown User' }}</td>
          <td>{{ $row->email ?? '' }}</td>
          <td>{{ $row->items ?? 'No items' }}</td>
          <td class="text-center">{{ (int) ($row->total_qty ?? 0) }}</td>
          <td class="text-end">${{ number_format((float) ($row->total_amount ?? 0), 2) }}</td>
          <td>
            @php $approved = (int) ($row->approved ?? 0); @endphp
            @if ($approved === 1)
              <span class="badge status-badge status-approved">Approved</span>
            @elseif ($approved === 2)
              <span class="badge status-badge status-rejected">Rejected</span>
            @else
              <span class="badge status-badge status-pending">Pending</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
@endif

<style>
.status-badge {
  border-radius: 999px;
  padding: 0.35rem 0.7rem;
  font-size: 0.85rem;
}
.status-approved {
  background: #dcfce7;
  color: #166534;
}
.status-pending {
  background: #fef3c7;
  color: #92400e;
}
.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}
</style>
@endsection
