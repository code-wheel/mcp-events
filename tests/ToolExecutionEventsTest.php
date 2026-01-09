<?php

declare(strict_types=1);

namespace CodeWheel\McpEvents\Tests;

use CodeWheel\McpEvents\ToolExecutionFailedEvent;
use CodeWheel\McpEvents\ToolExecutionStartedEvent;
use CodeWheel\McpEvents\ToolExecutionSucceededEvent;
use PHPUnit\Framework\TestCase;

/**
 * Tests for MCP tool execution events.
 */
#[\PHPUnit\Framework\Attributes\CoversClass(ToolExecutionStartedEvent::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ToolExecutionSucceededEvent::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(ToolExecutionFailedEvent::class)]
final class ToolExecutionEventsTest extends TestCase {

  public function testToolExecutionStartedEventProperties(): void {
    $timestamp = microtime(true);
    $event = new ToolExecutionStartedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: ['arg1' => 'value1'],
      requestId: 'req-123',
      timestamp: $timestamp,
    );

    $this->assertSame('test_tool', $event->toolName);
    $this->assertSame('my_module.test_tool', $event->pluginId);
    $this->assertSame(['arg1' => 'value1'], $event->arguments);
    $this->assertSame('req-123', $event->requestId);
    $this->assertSame($timestamp, $event->timestamp);
  }

  public function testToolExecutionStartedEventWithNullRequestId(): void {
    $event = new ToolExecutionStartedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: [],
      requestId: null,
      timestamp: microtime(true),
    );

    $this->assertNull($event->requestId);
  }

  public function testToolExecutionStartedEventWithIntegerRequestId(): void {
    $event = new ToolExecutionStartedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: [],
      requestId: 42,
      timestamp: microtime(true),
    );

    $this->assertSame(42, $event->requestId);
  }

  public function testToolExecutionSucceededEventProperties(): void {
    // Create a simple result object (framework-agnostic)
    $result = new \stdClass();
    $result->content = [['type' => 'text', 'text' => 'Success']];
    $result->isError = false;
    $result->structuredContent = ['data' => 'test'];

    $event = new ToolExecutionSucceededEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: ['arg1' => 'value1'],
      result: $result,
      durationMs: 45.5,
      requestId: 'req-456',
    );

    $this->assertSame('test_tool', $event->toolName);
    $this->assertSame('my_module.test_tool', $event->pluginId);
    $this->assertSame(['arg1' => 'value1'], $event->arguments);
    $this->assertSame($result, $event->result);
    $this->assertSame(45.5, $event->durationMs);
    $this->assertSame('req-456', $event->requestId);
  }

  public function testToolExecutionSucceededEventAcceptsAnyObject(): void {
    // Can use any object as result - anonymous class, stdClass, or CallToolResult
    $result = new class {
      public bool $isError = false;
      public array $content = [];
    };

    $event = new ToolExecutionSucceededEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      result: $result,
      durationMs: 0,
      requestId: null,
    );

    $this->assertFalse($event->result->isError);
  }

  public function testToolExecutionSucceededEventWithIntegerRequestId(): void {
    $result = new \stdClass();

    $event = new ToolExecutionSucceededEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      result: $result,
      durationMs: 0,
      requestId: 123,
    );

    $this->assertSame(123, $event->requestId);
  }

  public function testToolExecutionFailedEventProperties(): void {
    $exception = new \RuntimeException('Test error');

    $event = new ToolExecutionFailedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: ['arg1' => 'value1'],
      reason: ToolExecutionFailedEvent::REASON_VALIDATION,
      result: null,
      exception: $exception,
      durationMs: 12.3,
      requestId: 'req-789',
    );

    $this->assertSame('test_tool', $event->toolName);
    $this->assertSame('my_module.test_tool', $event->pluginId);
    $this->assertSame(['arg1' => 'value1'], $event->arguments);
    $this->assertSame(ToolExecutionFailedEvent::REASON_VALIDATION, $event->reason);
    $this->assertNull($event->result);
    $this->assertSame($exception, $event->exception);
    $this->assertSame(12.3, $event->durationMs);
    $this->assertSame('req-789', $event->requestId);
  }

  public function testToolExecutionFailedEventWithResultObject(): void {
    $result = new \stdClass();
    $result->isError = true;

    $event = new ToolExecutionFailedEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      reason: ToolExecutionFailedEvent::REASON_RESULT,
      result: $result,
      exception: null,
      durationMs: 0,
      requestId: null,
    );

    $this->assertSame($result, $event->result);
    $this->assertTrue($event->result->isError);
  }

  public function testToolExecutionFailedEventWithIntegerRequestId(): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      reason: ToolExecutionFailedEvent::REASON_VALIDATION,
      result: null,
      exception: null,
      durationMs: 0,
      requestId: 999,
    );

    $this->assertSame(999, $event->requestId);
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('policyFailureReasonProvider')]
  public function testIsPolicyFailureReturnsTrueForPolicyReasons(string $reason): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      reason: $reason,
      result: null,
      exception: null,
      durationMs: 0,
      requestId: null,
    );

    $this->assertTrue($event->isPolicyFailure());
  }

  public static function policyFailureReasonProvider(): array {
    return [
      [ToolExecutionFailedEvent::REASON_POLICY],
      [ToolExecutionFailedEvent::REASON_POLICY_APPROVAL],
      [ToolExecutionFailedEvent::REASON_POLICY_BUDGET],
      [ToolExecutionFailedEvent::REASON_POLICY_DRY_RUN],
      [ToolExecutionFailedEvent::REASON_POLICY_SCOPE],
    ];
  }

  #[\PHPUnit\Framework\Attributes\DataProvider('nonPolicyFailureReasonProvider')]
  public function testIsPolicyFailureReturnsFalseForNonPolicyReasons(string $reason): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      reason: $reason,
      result: null,
      exception: null,
      durationMs: 0,
      requestId: null,
    );

    $this->assertFalse($event->isPolicyFailure());
  }

  public static function nonPolicyFailureReasonProvider(): array {
    return [
      [ToolExecutionFailedEvent::REASON_VALIDATION],
      [ToolExecutionFailedEvent::REASON_ACCESS_DENIED],
      [ToolExecutionFailedEvent::REASON_INSTANTIATION],
      [ToolExecutionFailedEvent::REASON_INVALID_TOOL],
      [ToolExecutionFailedEvent::REASON_RESULT],
      [ToolExecutionFailedEvent::REASON_EXECUTION],
    ];
  }

  public function testHasExceptionReturnsTrueWhenExceptionPresent(): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      reason: ToolExecutionFailedEvent::REASON_EXECUTION,
      result: null,
      exception: new \Exception('Test'),
      durationMs: 0,
      requestId: null,
    );

    $this->assertTrue($event->hasException());
  }

  public function testHasExceptionReturnsFalseWhenNoException(): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test',
      pluginId: 'test',
      arguments: [],
      reason: ToolExecutionFailedEvent::REASON_VALIDATION,
      result: null,
      exception: null,
      durationMs: 0,
      requestId: null,
    );

    $this->assertFalse($event->hasException());
  }

  public function testFailureReasonConstantsHaveExpectedValues(): void {
    $this->assertSame('validation_failed', ToolExecutionFailedEvent::REASON_VALIDATION);
    $this->assertSame('access_denied', ToolExecutionFailedEvent::REASON_ACCESS_DENIED);
    $this->assertSame('instantiation_failed', ToolExecutionFailedEvent::REASON_INSTANTIATION);
    $this->assertSame('invalid_tool', ToolExecutionFailedEvent::REASON_INVALID_TOOL);
    $this->assertSame('result_failed', ToolExecutionFailedEvent::REASON_RESULT);
    $this->assertSame('execution_failed', ToolExecutionFailedEvent::REASON_EXECUTION);
    $this->assertSame('policy_blocked', ToolExecutionFailedEvent::REASON_POLICY);
    $this->assertSame('policy_approval_required', ToolExecutionFailedEvent::REASON_POLICY_APPROVAL);
    $this->assertSame('policy_budget_exceeded', ToolExecutionFailedEvent::REASON_POLICY_BUDGET);
    $this->assertSame('policy_dry_run', ToolExecutionFailedEvent::REASON_POLICY_DRY_RUN);
    $this->assertSame('policy_scope_required', ToolExecutionFailedEvent::REASON_POLICY_SCOPE);
  }

  public function testAllReasonsReturnsAllReasonConstants(): void {
    $allReasons = ToolExecutionFailedEvent::allReasons();

    $this->assertIsArray($allReasons);
    $this->assertCount(11, $allReasons);
    $this->assertArrayHasKey('REASON_VALIDATION', $allReasons);
    $this->assertArrayHasKey('REASON_ACCESS_DENIED', $allReasons);
    $this->assertArrayHasKey('REASON_POLICY', $allReasons);
    $this->assertSame('validation_failed', $allReasons['REASON_VALIDATION']);
  }

  public function testAllReasonsIsCached(): void {
    $first = ToolExecutionFailedEvent::allReasons();
    $second = ToolExecutionFailedEvent::allReasons();

    // Same array reference due to caching
    $this->assertSame($first, $second);
  }

  public function testIsValidReasonReturnsTrueForDefinedReasons(): void {
    $this->assertTrue(ToolExecutionFailedEvent::isValidReason('validation_failed'));
    $this->assertTrue(ToolExecutionFailedEvent::isValidReason('access_denied'));
    $this->assertTrue(ToolExecutionFailedEvent::isValidReason('policy_blocked'));
    $this->assertTrue(ToolExecutionFailedEvent::isValidReason('policy_dry_run'));
  }

  public function testIsValidReasonReturnsFalseForUndefinedReasons(): void {
    $this->assertFalse(ToolExecutionFailedEvent::isValidReason('invalid_reason'));
    $this->assertFalse(ToolExecutionFailedEvent::isValidReason(''));
    $this->assertFalse(ToolExecutionFailedEvent::isValidReason('VALIDATION_FAILED'));
  }

  public function testToolExecutionStartedEventJsonSerialize(): void {
    $timestamp = 1704067200.123456;
    $event = new ToolExecutionStartedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: ['key' => 'value'],
      requestId: 'req-123',
      timestamp: $timestamp,
    );

    $json = $event->jsonSerialize();

    $this->assertSame('tool_execution_started', $json['event']);
    $this->assertSame('test_tool', $json['tool_name']);
    $this->assertSame('my_module.test_tool', $json['plugin_id']);
    $this->assertSame(['key' => 'value'], $json['arguments']);
    $this->assertSame('req-123', $json['request_id']);
    $this->assertSame($timestamp, $json['timestamp']);

    // Verify it works with json_encode
    $encoded = json_encode($event);
    $this->assertIsString($encoded);
    $this->assertStringContainsString('tool_execution_started', $encoded);
  }

  public function testToolExecutionSucceededEventJsonSerialize(): void {
    $result = new \stdClass();
    $result->isError = false;

    $event = new ToolExecutionSucceededEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: ['key' => 'value'],
      result: $result,
      durationMs: 42.5,
      requestId: 'req-456',
    );

    $json = $event->jsonSerialize();

    $this->assertSame('tool_execution_succeeded', $json['event']);
    $this->assertSame('test_tool', $json['tool_name']);
    $this->assertSame('my_module.test_tool', $json['plugin_id']);
    $this->assertSame(['key' => 'value'], $json['arguments']);
    $this->assertSame(42.5, $json['duration_ms']);
    $this->assertSame('req-456', $json['request_id']);
    // Result is intentionally not serialized (may contain sensitive data)
    $this->assertArrayNotHasKey('result', $json);

    // Verify it works with json_encode
    $encoded = json_encode($event);
    $this->assertIsString($encoded);
    $this->assertStringContainsString('tool_execution_succeeded', $encoded);
  }

  public function testToolExecutionFailedEventJsonSerializeWithoutException(): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: ['key' => 'value'],
      reason: ToolExecutionFailedEvent::REASON_VALIDATION,
      result: null,
      exception: null,
      durationMs: 10.0,
      requestId: 'req-789',
    );

    $json = $event->jsonSerialize();

    $this->assertSame('tool_execution_failed', $json['event']);
    $this->assertSame('test_tool', $json['tool_name']);
    $this->assertSame('my_module.test_tool', $json['plugin_id']);
    $this->assertSame(['key' => 'value'], $json['arguments']);
    $this->assertSame('validation_failed', $json['reason']);
    $this->assertSame(10.0, $json['duration_ms']);
    $this->assertSame('req-789', $json['request_id']);
    $this->assertFalse($json['is_policy_failure']);
    $this->assertFalse($json['has_exception']);
    $this->assertArrayNotHasKey('exception_class', $json);
    $this->assertArrayNotHasKey('exception_message', $json);
  }

  public function testToolExecutionFailedEventJsonSerializeWithException(): void {
    $exception = new \RuntimeException('Something went wrong');

    $event = new ToolExecutionFailedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: [],
      reason: ToolExecutionFailedEvent::REASON_EXECUTION,
      result: null,
      exception: $exception,
      durationMs: 5.0,
      requestId: null,
    );

    $json = $event->jsonSerialize();

    $this->assertSame('tool_execution_failed', $json['event']);
    $this->assertTrue($json['has_exception']);
    $this->assertSame('RuntimeException', $json['exception_class']);
    $this->assertSame('Something went wrong', $json['exception_message']);

    // Verify it works with json_encode
    $encoded = json_encode($event);
    $this->assertIsString($encoded);
    $this->assertStringContainsString('RuntimeException', $encoded);
  }

  public function testToolExecutionFailedEventJsonSerializeWithPolicyFailure(): void {
    $event = new ToolExecutionFailedEvent(
      toolName: 'test_tool',
      pluginId: 'my_module.test_tool',
      arguments: [],
      reason: ToolExecutionFailedEvent::REASON_POLICY_DRY_RUN,
      result: null,
      exception: null,
      durationMs: 0,
      requestId: null,
    );

    $json = $event->jsonSerialize();

    $this->assertTrue($json['is_policy_failure']);
    $this->assertSame('policy_dry_run', $json['reason']);
  }

}
