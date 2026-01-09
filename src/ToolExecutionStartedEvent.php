<?php

declare(strict_types=1);

namespace CodeWheel\McpEvents;

/**
 * Dispatched when an MCP tool execution begins.
 *
 * Use this event for:
 * - Logging tool invocations
 * - Starting performance timers
 * - Request tracing/correlation
 * - Rate limiting checks
 */
final class ToolExecutionStartedEvent implements \JsonSerializable {

  /**
   * @param string $toolName
   *   MCP tool name.
   * @param string $pluginId
   *   Implementation-specific plugin identifier.
   * @param array<string, mixed> $arguments
   *   Sanitized tool arguments (sensitive values should be redacted).
   * @param string|int|null $requestId
   *   MCP request id for correlation.
   * @param float $timestamp
   *   UNIX timestamp (microtime) when execution started.
   */
  public function __construct(
    public readonly string $toolName,
    public readonly string $pluginId,
    public readonly array $arguments,
    public readonly string|int|null $requestId,
    public readonly float $timestamp,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    return [
      'event' => 'tool_execution_started',
      'tool_name' => $this->toolName,
      'plugin_id' => $this->pluginId,
      'arguments' => $this->arguments,
      'request_id' => $this->requestId,
      'timestamp' => $this->timestamp,
    ];
  }

}
