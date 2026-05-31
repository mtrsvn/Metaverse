@extends('layouts.app')

@section('content')
<div class="page-header">
  <h2>Audit Log</h2>
</div>

<table class="table">
  <thead>
    <tr><th>User</th><th>Action</th><th>Time</th></tr>
  </thead>
  <tbody>
    @foreach ($rows as $row)
    <tr>
      <td>{{ $row->username ?? 'Unknown' }}</td>
      <td>{{ $row->action }}</td>
      <td style="color: #64748b;">{{ $row->log_time }}</td>
    </tr>
    @endforeach
  </tbody>
</table>

@if ($total > $perPage)
  @php
    $start = $total === 0 ? 0 : (($page - 1) * $perPage) + 1;
    $end = min($page * $perPage, $total);
    $pageUrl = function (int $p) {
        $params = request()->query();
        $params['page'] = $p;
        return '?' . http_build_query($params);
    };
  @endphp
  <div class="d-flex align-items-center justify-content-between" style="margin-top: 12px;">
    <div class="text-muted">Showing {{ $start }}–{{ $end }} of {{ $total }}</div>
    <nav aria-label="Audit log pagination">
      <ul class="pagination mb-0">
        <li class="page-item {{ $page <= 1 ? 'disabled' : '' }}">
          <a class="page-link" href="{{ $page > 1 ? $pageUrl($page - 1) : '#' }}" tabindex="-1" aria-disabled="{{ $page <= 1 ? 'true' : 'false' }}">Previous</a>
        </li>
        @php
          $window = 1;
          $pages = [1];
          for ($i = $page - $window; $i <= $page + $window; $i++) {
              if ($i > 1 && $i < $totalPages) { $pages[] = $i; }
          }
          if ($totalPages > 1) { $pages[] = $totalPages; }
          $pages = array_values(array_unique(array_filter($pages, function ($n) { return $n >= 1; })));
          sort($pages);
          $lastPrinted = 0;
        @endphp
        @foreach ($pages as $p)
          @if ($lastPrinted && $p > $lastPrinted + 1)
            <li class="page-item disabled"><span class="page-link">&hellip;</span></li>
          @endif
          <li class="page-item{{ $p == $page ? ' active' : '' }}"><a class="page-link" href="{{ $pageUrl($p) }}">{{ $p }}</a></li>
          @php $lastPrinted = $p; @endphp
        @endforeach
        <li class="page-item {{ $page >= $totalPages ? 'disabled' : '' }}">
          <a class="page-link" href="{{ $page < $totalPages ? $pageUrl($page + 1) : '#' }}" aria-disabled="{{ $page >= $totalPages ? 'true' : 'false' }}">Next</a>
        </li>
      </ul>
    </nav>
  </div>
@endif
@endsection
