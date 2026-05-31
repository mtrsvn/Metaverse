@extends('layouts.app')

@section('content')
<div class="page-header">
  <h2>Admin Dashboard</h2>
</div>

<div class="row g-4" id="statsGrid">
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Total Products</div>
        <h3 class="mb-0" id="statProducts">--</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Pending Orders</div>
        <h3 class="mb-0" id="statPending">--</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Approved Orders</div>
        <h3 class="mb-0" id="statApproved">--</h3>
      </div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card">
      <div class="card-body">
        <div class="text-muted">Total Users</div>
        <h3 class="mb-0" id="statUsers">--</h3>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  fetch(@json(route('admin.stats', [], false)), { cache: 'no-store' })
    .then(r => r.json())
    .then(data => {
      if (!data || !data.success) return;
      const stats = data.data || {};
      document.getElementById('statProducts').textContent = stats.total_products ?? '--';
      document.getElementById('statPending').textContent = stats.pending_orders ?? '--';
      document.getElementById('statApproved').textContent = stats.approved_orders ?? '--';
      document.getElementById('statUsers').textContent = stats.total_users ?? '--';
    })
    .catch(() => {});
});
</script>
@endsection
