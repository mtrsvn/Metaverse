@extends('layouts.app')

@section('content')
<div class="page-header">
  <h2>User Management</h2>
</div>

@if ($resetMessage)
  <p class="text-muted">{!! $resetMessage !!}</p>
@endif

<div class="password-hint mb-4">
  <strong>Password Reset Info:</strong> When you reset a user's password, it will be set to: <code>{{ $standardReset }}</code>
</div>

<table class="table">
  <thead>
    <tr><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>
  </thead>
  <tbody>
    @foreach ($users as $row)
    <tr>
      <td>{{ $row->username }}</td>
      <td>{{ $row->email }}</td>
      <td><span class="badge bg-secondary">{{ $row->role }}</span></td>
      <td>
        <form method="post" style="margin:0;" onsubmit="return confirmPasswordReset(event, this, {{ json_encode($row->username) }})">
          @csrf
          <input type="hidden" name="reset_id" value="{{ $row->id }}">
          <button type="submit" class="btn btn-secondary btn-sm">Reset Password</button>
        </form>
      </td>
    </tr>
    @endforeach
  </tbody>
</table>

<script>
function confirmPasswordReset(event, form, username) {
  event.preventDefault();
  Swal.fire({
    title: 'Reset Password?',
    text: `Are you sure you want to reset the password for ${username}?`,
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#1c1d1e',
    cancelButtonColor: '#6c757d',
    confirmButtonText: 'Yes, reset it',
    cancelButtonText: 'Cancel'
  }).then((result) => {
    if (result.isConfirmed) {
      form.submit();
    }
  });
  return false;
}
</script>
@endsection
