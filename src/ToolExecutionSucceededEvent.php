<?php

declare(strict_types=1);

namespace CodeWheel\McpEvents;

/**
 * Dispatched when an MCP tool execution completes successfully.
 *
 * Use this event for:
 * - Logging successful operations
 * - Recording performance metrics
 * - Audit trails
 * - Usage analytics
 */
final class ToolExecutionSucceededEvent {

  /**
   * @param string $toolName
   *   MCP tool name.
   * @param string $pluginId
   *   Implementation-specific plugin identifier.
   * @param array<string, mixed> $arguments
   *   Sanitized tool arguments (caller must redact sensitive data).
   * @param object $result
   *   Tool result object. When using mcp/sdk, this is a CallToolResult.
   *   Expected properties: content (array), isError (bool), structuredContent (mixed).
   * @param float $durationMs
   *   Execution duration in milliseconds.
   * @param string|int|null $requestId
   *   MCP request id for correlation.
   */
  public function __construct(
    public readonly string $toolName,
    public readonly string $pluginId,
    public readonly array $arguments,
    public readonly object $result,
    public readonly float $durationMs,
    public readonly string|int|null $requestId,
  ) {}

}
