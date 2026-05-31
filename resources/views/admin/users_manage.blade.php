@extends('layouts.app')

@section('content')
<div class="page-header d-flex align-items-center justify-content-between">
  <h2>Staff User Management</h2>
</div>

@if (!empty($errors))
  <div class="alert alert-danger">
    <ul class="mb-0">
      @foreach ($errors as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif
@if (!empty($messages))
  <div class="alert alert-success">
    <ul class="mb-0">
      @foreach ($messages as $message)
        <li>{{ $message }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="card">
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-3">
      <h5 class="card-title mb-0">Existing Staff Users</h5>
      <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#staffCreateModal" style="font-size: 1.1rem; padding: 0.5rem 0.9rem;">
        <span aria-hidden="true" style="font-size: 1.35rem; line-height: 1;">＋</span>
        <span class="fw-semibold" style="line-height: 1;">Add</span>
      </button>
    </div>

    <div class="modal fade" id="staffCreateModal" tabindex="-1" aria-labelledby="staffCreateLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="staffCreateLabel">Add Staff User</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="post" id="staffCreateForm" onsubmit="return confirmCreateStaff(event)">
            @csrf
            <div class="modal-body row g-3">
              <input type="hidden" name="action" value="create">
              <div class="col-12">
                <label class="form-label">Username</label>
                <input type="text" name="username" class="form-control" required>
              </div>
              <div class="col-12">
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-control" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control" required>
              </div>
              <div class="col-12 col-md-6">
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control" required>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
              <button type="submit" class="btn btn-primary">Add</button>
            </div>
          </form>
        </div>
      </div>
    </div>
    @if ($staffUsers->isEmpty())
      <div class="alert alert-info mb-0">No staff users found.</div>
    @else
      <div class="table-responsive">
        <table class="table align-middle">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Email</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($staffUsers as $user)
            <tr>
              <td>{{ (int) $user->id }}</td>
              <td>
                <input type="text" name="username" class="form-control form-control-sm" value="{{ $user->username }}" placeholder="Username" required form="update-form-{{ (int) $user->id }}">
              </td>
              <td>
                <input type="email" name="email" class="form-control form-control-sm" value="{{ $user->email }}" placeholder="Email" required form="update-form-{{ (int) $user->id }}">
              </td>
              <td>
                <div class="staff-action-row">
                  <form id="update-form-{{ (int) $user->id }}" method="post" class="inline-form" onsubmit="return confirmUpdateStaff(event, {{ (int) $user->id }}, {{ json_encode($user->username) }})">
                    @csrf
                    <input type="hidden" name="action" value="update">
                    <input type="hidden" name="user_id" value="{{ (int) $user->id }}">
                    <input type="password" name="new_password" class="form-control password-input" placeholder="New password (optional)">
                  </form>
                  <div class="staff-button-group">
                    <button type="submit" form="update-form-{{ (int) $user->id }}" class="btn staff-btn staff-btn-save">Save</button>
                    <button type="button" class="btn staff-btn staff-btn-delete" onclick="confirmDelete({{ (int) $user->id }}, {{ json_encode($user->username) }})">Delete</button>
                  </div>
                  <form id="delete-form-{{ (int) $user->id }}" method="post" class="d-inline">
                    @csrf
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="user_id" value="{{ (int) $user->id }}">
                  </form>
                </div>
              </td>
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

<style>
.staff-btn {
  min-width: 96px;
  height: 40px;
  border-radius: 10px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-width: 1px;
}
.staff-btn-save {
  background-color: #1c1d1e;
  border-color: #1c1d1e;
  color: #fff;
}
.staff-btn-save:hover {
  background-color: #1c1d1e;
  border-color: #1c1d1e;
  color: #fff;
}
.staff-btn-delete {
  background-color: #dc3545;
  border-color: #dc3545;
  color: #fff;
}
.staff-btn-delete:hover {
  background-color: #bb2d3b;
  border-color: #bb2d3b;
  color: #fff;
}
.staff-action-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: nowrap;
  width: 100%;
  justify-content: flex-end;
}
.staff-action-row form {
  margin: 0;
}
.inline-form {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 6px;
  flex: 1 1 auto;
  min-width: 0;
}
.inline-form .form-control {
  flex: 1 1 120px;
  max-width: 160px;
  width: auto;
  min-width: 0;
}
.inline-form .password-input {
  flex: 1 1 220px;
  max-width: 320px;
}
.staff-button-group {
  display: flex;
  gap: 8px;
  flex-shrink: 0;
}
@media (max-width: 992px) {
  .staff-action-row,
  .inline-form {
    flex-wrap: wrap;
  }
}
</style>

<script>
function confirmCreateStaff(event) {
  event.preventDefault();
  const form = event.target;
  Swal.fire({
    title: 'Add Staff User?',
    text: 'Are you sure you want to create this new staff user?',
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#1c1d1e',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, create',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  return false;
}

function confirmUpdateStaff(event, userId, username) {
  event.preventDefault();
  const form = event.target;
  Swal.fire({
    title: 'Update Staff User?',
    text: `Are you sure you want to update ${username}?`,
    icon: 'question',
    showCancelButton: true,
    confirmButtonColor: '#1c1d1e',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, update',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  return false;
}

function confirmDelete(userId, username) {
  Swal.fire({
    title: 'Delete Staff User?',
    text: `Are you sure you want to delete ${username}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#dc3545',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, delete',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      const form = document.getElementById(`delete-form-${userId}`);
      if (form) form.submit();
    }
  });
}
</script>
@endsection
