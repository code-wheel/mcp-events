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

}
