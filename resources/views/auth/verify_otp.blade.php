@extends('layouts.app')

@section('content')
@if ($message)
  <p class="text-muted">{!! $message !!}</p>
@endif

<div class="form-card mx-auto">
  <h3 class="mb-4">Verify OTP</h3>
  <p style="color: #64748b;">Enter the 6-digit code sent to your email.</p>
  <form method="post">
    @csrf
    <div class="mb-4">
      <label class="form-label">OTP Code</label>
      <input type="text" class="form-control" name="otp_code" maxlength="6" required style="text-align: center; font-size: 1.5rem; letter-spacing: 0.5rem;">
    </div>
    <button type="submit" class="btn btn-primary w-100">Verify</button>
  </form>
</div>
@endsection
