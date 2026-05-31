@extends('layouts.app')

@section('content')
<div class="page-header">
  <h2>Pending Orders</h2>
</div>

@if ($rows->count() > 0)
<form id="bulkForm" method="post">
  @csrf
  <input type="hidden" name="bulk_action" id="bulkActionInput" value="">
</form>

<table class="table align-middle">
  <thead>
    <tr><th><input type="checkbox" id="selectAllOrders"></th><th>User</th><th>Order Time</th><th>Items</th><th>Total Qty</th><th>Total Amount</th><th>Action</th></tr>
  </thead>
  <tbody>
    @foreach ($rows as $row)
    <tr>
      <td>
        <input type="checkbox" class="order-checkbox" name="bulk_orders[]" value="{{ $row->user_id . '|' . $row->order_time }}" form="bulkForm">
      </td>
      <td>{{ $row->username ?? 'Unknown User' }}</td>
      <td>{{ $row->order_time ?? '' }}</td>
      <td>{{ $row->item_list ?? 'No items' }}</td>
      <td>{{ (int) $row->total_qty }}</td>
      <td>${{ number_format((float) $row->total_amount, 2) }}</td>
      <td>
        <div class="d-flex gap-2">
          <form method="post" style="margin:0;" id="approve-form-{{ $row->user_id }}-{{ strtotime($row->order_time) }}" onsubmit="return confirmApprove(event, {{ json_encode($row->username ?? 'Unknown User') }})">
            @csrf
            <input type="hidden" name="user_id" value="{{ $row->user_id }}">
            <input type="hidden" name="order_time" value="{{ $row->order_time }}">
            <input type="hidden" name="approve_key" value="1">
            <button type="submit" class="btn btn-primary btn-sm">Approve</button>
          </form>
          <form method="post" style="margin:0;" id="reject-form-{{ $row->user_id }}-{{ strtotime($row->order_time) }}" onsubmit="return confirmReject(event, {{ json_encode($row->username ?? 'Unknown User') }})">
            @csrf
            <input type="hidden" name="user_id" value="{{ $row->user_id }}">
            <input type="hidden" name="order_time" value="{{ $row->order_time }}">
            <input type="hidden" name="reject_key" value="1">
            <button type="submit" class="btn btn-danger btn-sm">Reject</button>
          </form>
        </div>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

<div id="bulkBar" class="bulk-bar d-none">
  <div class="container d-flex justify-content-between align-items-center py-2">
    <div><strong id="selectedCount">0</strong> selected</div>
    <div class="d-flex gap-2">
      <button type="submit" class="btn btn-primary" data-action="approve" form="bulkForm">Approve</button>
      <button type="submit" class="btn btn-danger" data-action="reject" form="bulkForm">Reject</button>
    </div>
  </div>
</div>
@else
  <div class="card">
    <div class="card-body text-center py-4">No pending orders.</div>
  </div>
@endif

@if (!empty(session('admin_toast')))
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      try {
        var msg = @json(session('admin_toast')['message']);
        var type = @json(session('admin_toast')['type']);
        if (typeof showToast === 'function') {
          showToast(msg, type);
        }
      } catch(e) {}
    });
  </script>
  @php session()->forget('admin_toast'); @endphp
@endif

<script>
document.addEventListener('DOMContentLoaded', function(){
  var selectAll = document.getElementById('selectAllOrders');
  var checkboxes = document.querySelectorAll('.order-checkbox');
  var bulkBar = document.getElementById('bulkBar');
  var selectedCountEl = document.getElementById('selectedCount');
  var bulkActionInput = document.getElementById('bulkActionInput');
  var bulkButtons = document.querySelectorAll('#bulkBar button[data-action]');

  function updateSelectionState() {
    var checked = Array.from(checkboxes).filter(function(cb){ return cb.checked; }).length;
    if (selectedCountEl) selectedCountEl.textContent = checked;
    if (bulkBar) {
      if (checked > 0) {
        bulkBar.classList.remove('d-none');
      } else {
        bulkBar.classList.add('d-none');
      }
    }
    if (selectAll) {
      var total = checkboxes.length;
      selectAll.checked = checked === total && total > 0;
      selectAll.indeterminate = checked > 0 && checked < total;
    }
  }

  if (selectAll) {
    selectAll.addEventListener('change', function(){
      checkboxes.forEach(function(cb){ cb.checked = selectAll.checked; });
      updateSelectionState();
    });
  }

  checkboxes.forEach(function(cb){
    cb.addEventListener('change', updateSelectionState);
  });

  bulkButtons.forEach(function(btn){
    btn.addEventListener('click', function(e){
      e.preventDefault();
      const action = btn.getAttribute('data-action');
      const checked = Array.from(checkboxes).filter(function(cb){ return cb.checked; }).length;

      if (checked === 0) {
        Swal.fire({
          icon: 'warning',
          title: 'No Orders Selected',
          text: 'Please select at least one order.',
          confirmButtonColor: '#1c1d1e'
        });
        return;
      }

      const actionText = action === 'approve' ? 'Approve' : 'Reject';
      const actionColor = action === 'approve' ? '#1c1d1e' : '#dc3545';

      Swal.fire({
        title: `${actionText} ${checked} Order(s)?`,
        text: `Are you sure you want to ${action} ${checked} selected order(s)?`,
        icon: action === 'approve' ? 'question' : 'warning',
        showCancelButton: true,
        confirmButtonColor: actionColor,
        cancelButtonColor: '#6c757d',
        confirmButtonText: `Yes, ${action}`,
        cancelButtonText: 'Cancel'
      }).then((result) => {
        if (result.isConfirmed) {
          if (bulkActionInput) {
            bulkActionInput.value = action;
          }
          document.getElementById('bulkForm').submit();
        }
      });
    });
  });

  updateSelectionState();
});

function confirmApprove(event, username) {
  event.preventDefault();
  const form = event.target;
  Swal.fire({
    title: 'Approve Order?',
    text: `Are you sure you want to approve ${username}'s order?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#1c1d1e',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, approve',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  return false;
}

function confirmReject(event, username) {
  event.preventDefault();
  const form = event.target;
  Swal.fire({
    title: 'Reject Order?',
    text: `Are you sure you want to reject ${username}'s order?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, reject',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  return false;
}
</script>

<style>
.bulk-bar {
  position: fixed;
  left: 0;
  right: 0;
  bottom: 0;
  background: #fff;
  border-top: 1px solid #e5e7eb;
  box-shadow: 0 -6px 20px rgba(0,0,0,0.08);
  z-index: 1100;
}
</style>
@endsection
