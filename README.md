# MCP Events

Standardized event classes for MCP (Model Context Protocol) tool execution lifecycle in PHP.

## Installation

```bash
composer require code-wheel/mcp-events
```

## Usage

These events follow a standard structure for MCP tool execution observability. Use them with any PSR-14 compatible event dispatcher.

### Dispatching Events

```php
use CodeWheel\McpEvents\ToolExecutionStartedEvent;
use CodeWheel\McpEvents\ToolExecutionSucceededEvent;
use CodeWheel\McpEvents\ToolExecutionFailedEvent;

// When tool execution starts
$startEvent = new ToolExecutionStartedEvent(
    toolName: 'create_user',
    pluginId: 'my_module.create_user',
    arguments: ['username' => 'john', 'email' => '[redacted]'],
    requestId: 'req-123',
    timestamp: microtime(true),
);
$dispatcher->dispatch($startEvent);

// On success
$successEvent = new ToolExecutionSucceededEvent(
    toolName: 'create_user',
    pluginId: 'my_module.create_user',
    arguments: ['username' => 'john', 'email' => '[redacted]'],
    result: $callToolResult,
    durationMs: 45.2,
    requestId: 'req-123',
);
$dispatcher->dispatch($successEvent);

// On failure
$failEvent = new ToolExecutionFailedEvent(
    toolName: 'create_user',
    pluginId: 'my_module.create_user',
    arguments: ['username' => 'john'],
    reason: ToolExecutionFailedEvent::REASON_VALIDATION,
    result: null,
    exception: $validationException,
    durationMs: 12.5,
    requestId: 'req-123',
);
$dispatcher->dispatch($failEvent);
```

### Listening to Events

```php
use CodeWheel\McpEvents\ToolExecutionStartedEvent;
use CodeWheel\McpEvents\ToolExecutionSucceededEvent;
use CodeWheel\McpEvents\ToolExecutionFailedEvent;

class ToolExecutionLogger {

    public function onStart(ToolExecutionStartedEvent $event): void {
        $this->logger->info('Tool started', [
            'tool' => $event->toolName,
            'request_id' => $event->requestId,
        ]);
    }

    public function onSuccess(ToolExecutionSucceededEvent $event): void {
        $this->metrics->histogram('tool_duration_ms', $event->durationMs, [
            'tool' => $event->toolName,
        ]);
    }

    public function onFailure(ToolExecutionFailedEvent $event): void {
        $context = [
            'tool' => $event->toolName,
            'reason' => $event->reason,
            'duration_ms' => $event->durationMs,
        ];

        if ($event->hasException()) {
            $context['exception'] = $event->exception->getMessage();
        }

        if ($event->isPolicyFailure()) {
            $this->logger->warning('Tool blocked by policy', $context);
        } else {
            $this->logger->error('Tool execution failed', $context);
        }
    }
}
```

## Events

### ToolExecutionStartedEvent

Dispatched when tool execution begins.

| Property | Type | Description |
|----------|------|-------------|
| `toolName` | string | MCP tool name |
| `pluginId` | string | Implementation plugin ID |
| `arguments` | array | Sanitized tool arguments |
| `requestId` | string\|int\|null | MCP request correlation ID |
| `timestamp` | float | Start timestamp (microtime) |

### ToolExecutionSucceededEvent

Dispatched when tool execution completes successfully.

| Property | Type | Description |
|----------|------|-------------|
| `toolName` | string | MCP tool name |
| `pluginId` | string | Implementation plugin ID |
| `arguments` | array | Sanitized tool arguments |
| `result` | CallToolResult | MCP SDK result object |
| `durationMs` | float | Execution duration in ms |
| `requestId` | string\|int\|null | MCP request correlation ID |

### ToolExecutionFailedEvent

Dispatched when tool execution fails.

| Property | Type | Description |
|----------|------|-------------|
| `toolName` | string | MCP tool name |
| `pluginId` | string | Implementation plugin ID |
| `arguments` | array | Sanitized tool arguments |
| `reason` | string | Failure reason constant |
| `result` | CallToolResult\|null | MCP result if available |
| `exception` | Throwable\|null | Exception if thrown |
| `durationMs` | float | Duration until failure in ms |
| `requestId` | string\|int\|null | MCP request correlation ID |

## Failure Reasons

| Constant | Description |
|----------|-------------|
| `REASON_VALIDATION` | Input validation failed |
| `REASON_ACCESS_DENIED` | Permission denied |
| `REASON_INSTANTIATION` | Failed to create tool instance |
| `REASON_INVALID_TOOL` | Tool not found or invalid |
| `REASON_RESULT` | Result processing failed |
| `REASON_EXECUTION` | General execution failure |
| `REASON_POLICY` | Blocked by policy |
| `REASON_POLICY_APPROVAL` | Requires approval |
| `REASON_POLICY_BUDGET` | Budget exceeded |
| `REASON_POLICY_DRY_RUN` | Dry run mode |
| `REASON_POLICY_SCOPE` | Insufficient scope |

## Helper Methods

```php
// Check if failure was policy-related
if ($event->isPolicyFailure()) {
    // Handle policy block differently
}

// Check if exception was thrown
if ($event->hasException()) {
    $exception = $event->exception;
}
```

## License

MIT
