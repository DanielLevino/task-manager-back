<!doctype html>
<html>
  <body>
    <h1>New Task: {{ $task->title }}</h1>
    <p><strong>Priority:</strong> {{ ucfirst($task->priority ?? '—') }}</p>
    <p><strong>Due:</strong> {{ $task->due_date ?? '—' }}</p>
    <p>Thanks,<br>{{ config('app.name') }}</p>
  </body>
</html>
