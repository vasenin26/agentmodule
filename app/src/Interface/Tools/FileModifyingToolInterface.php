<?php

namespace Anymodule\Agentmodule\Interface\Tools;

/**
 * Marker interface for tools that modify files or repository state.
 * Used to wrap such tools with FileChangeTrackingDecorator in Developer workflow.
 */
interface FileModifyingToolInterface extends ToolInterface
{
}
