<?php

declare(strict_types=1);

namespace CodeWheel\McpEvents;

/**
 * Dispatched when an MCP tool execution fails.
 *
 * Use this event for:
 * - Error logging and alerting
 * - Debugging and troubleshooting
 * - Error rate monitoring
 * - Incident tracking
 */
final class ToolExecutionFailedEvent implements \JsonSerializable {

  // Standard failure reason constants.
  public const REASON_VALIDATION = 'validation_failed';
  public const REASON_ACCESS_DENIED = 'access_denied';
  public const REASON_INSTANTIATION = 'instantiation_failed';
  public const REASON_INVALID_TOOL = 'invalid_tool';
  public const REASON_RESULT = 'result_failed';
  public const REASON_EXECUTION = 'execution_failed';

  // Policy-related failure reasons.
  public const REASON_POLICY = 'policy_blocked';
  public const REASON_POLICY_APPROVAL = 'policy_approval_required';
  public const REASON_POLICY_BUDGET = 'policy_budget_exceeded';
  public const REASON_POLICY_DRY_RUN = 'policy_dry_run';
  public const REASON_POLICY_SCOPE = 'policy_scope_required';

  /**
   * Cached list of all reason constants.
   *
   * @var array<string, string>|null
   */
  private static ?array $cachedReasons = null;

  /**
   * @param string $toolName
   *   MCP tool name.
   * @param string $pluginId
   *   Implementation-specific plugin identifier.
   * @param array<string, mixed> $arguments
   *   Sanitized tool arguments (caller must redact sensitive data).
   * @param string $reason
   *   Failure reason (use REASON_* constants).
   * @param object|null $result
   *   Tool result object if available. When using mcp/sdk, this is a CallToolResult.
   * @param \Throwable|null $exception
   *   Exception thrown during execution, if any.
   * @param float $durationMs
   *   Duration in milliseconds until failure.
   * @param string|int|null $requestId
   *   MCP request id for correlation.
   */
  public function __construct(
    public readonly string $toolName,
    public readonly string $pluginId,
    public readonly array $arguments,
    public readonly string $reason,
    public readonly ?object $result,
    public readonly ?\Throwable $exception,
    public readonly float $durationMs,
    public readonly string|int|null $requestId,
  ) {}

  /**
   * Get all defined failure reason constants.
   *
   * @return array<string, string>
   *   Map of constant names to values (e.g., ['REASON_VALIDATION' => 'validation_failed']).
   */
  public static function allReasons(): array {
    if (self::$cachedReasons === null) {
      self::$cachedReasons = [];
      $reflection = new \ReflectionClass(self::class);
      foreach ($reflection->getConstants() as $name => $value) {
        if (str_starts_with($name, 'REASON_')) {
          self::$cachedReasons[$name] = $value;
        }
      }
    }
    return self::$cachedReasons;
  }

  /**
   * Check if a reason string is a valid defined constant.
   *
   * @param string $reason
   *   The reason to validate.
   *
   * @return bool
   *   TRUE if the reason matches a REASON_* constant value.
   */
  public static function isValidReason(string $reason): bool {
    return in_array($reason, self::allReasons(), true);
  }

  /**
   * Check if failure was due to a policy restriction.
   */
  public function isPolicyFailure(): bool {
    return str_starts_with($this->reason, 'policy_');
  }

  /**
   * Check if failure was due to an exception.
   */
  public function hasException(): bool {
    return $this->exception !== null;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize(): array {
    $data = [
      'event' => 'tool_execution_failed',
      'tool_name' => $this->toolName,
      'plugin_id' => $this->pluginId,
      'arguments' => $this->arguments,
      'reason' => $this->reason,
      'duration_ms' => $this->durationMs,
      'request_id' => $this->requestId,
      'is_policy_failure' => $this->isPolicyFailure(),
      'has_exception' => $this->hasException(),
    ];

    if ($this->exception !== null) {
      $data['exception_class'] = $this->exception::class;
      $data['exception_message'] = $this->exception->getMessage();
    }

    return $data;
  }

}
