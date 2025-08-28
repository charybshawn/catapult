<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Throwable;

/**
 * Agricultural system debugging and error analysis service.
 * 
 * Provides comprehensive debugging utilities for agricultural applications including
 * detailed error logging with stack traces, object state inspection, and data
 * checkpoint creation. Essential for diagnosing issues in crop management,
 * order processing, and inventory systems during development and production.
 *
 * @business_domain Agricultural system debugging and error analysis
 * @used_by Development debugging, production error analysis, system troubleshooting
 * @file_output Creates debug files in storage/logs for detailed analysis
 * @agricultural_context Specialized debugging for agricultural model relationships and state
 */
class DebugService
{
    /**
     * Log detailed error information for agricultural system debugging.
     * 
     * Creates comprehensive error logs including stack traces, file locations,
     * and function arguments for debugging agricultural system issues.
     * Essential for diagnosing crop management, order processing, and
     * inventory system failures.
     *
     * @param Throwable $e Exception or error to log
     * @param string $context Agricultural context ('crop', 'order', 'inventory', 'general')
     * @return void
     * @agricultural_context Provides context for agricultural system error analysis
     * @stack_trace Includes detailed stack trace with argument summaries
     */
    public static function logError(Throwable $e, string $context = 'general')
    {
        $trace = array_slice($e->getTrace(), 0, 20);
        $formattedTrace = [];

        foreach ($trace as $item) {
            $formattedTrace[] = [
                'file' => $item['file'] ?? 'Unknown file',
                'line' => $item['line'] ?? 0,
                'function' => ($item['class'] ?? '') . ($item['type'] ?? '') . ($item['function'] ?? ''),
                'args' => self::summarizeArgs($item['args'] ?? []),
            ];
        }

        Log::error("DebugService: {$context} - {$e->getMessage()}", [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $formattedTrace,
        ]);
    }
    
    /**
     * Log structured agricultural data for debugging analysis.
     * 
     * Records structured data from agricultural operations for detailed
     * analysis including crop calculations, order processing workflows,
     * and inventory state changes.
     *
     * @param string $message Descriptive message for the data being logged
     * @param array $data Structured data to log (crop state, order details, etc.)
     * @param string $context Agricultural context for categorization
     * @return void
     * @agricultural_context Logs agricultural workflow data for analysis
     */
    public static function logData(string $message, array $data, string $context = 'debug')
    {
        Log::channel('daily')->info("DebugService: {$context} - {$message}", $data);
    }
    
    /**
     * Write agricultural debug information to structured JSON files.
     * 
     * Creates formatted JSON files in storage/logs for detailed analysis
     * of agricultural system state and operations.
     *
     * @param string $filename Target filename for debug output
     * @param array $data Structured agricultural data to write
     * @return string Full path to created debug file
     * @agricultural_context Creates structured debug files for agricultural analysis
     */
    public static function writeToFile(string $filename, array $data)
    {
        $path = storage_path('logs/' . $filename);
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        File::put($path, $jsonData);
        
        return $path;
    }
    
    /**
     * Create detailed checkpoint of agricultural object state for debugging.
     * 
     * Captures comprehensive state information for agricultural models including
     * attributes, relationships, methods, and metadata. Essential for debugging
     * complex crop calculations, order state transitions, and inventory updates.
     *
     * @param mixed $object Agricultural object to analyze (Crop, Product, Order, etc.)
     * @param string $label Descriptive label for the checkpoint
     * @return string Path to created checkpoint file
     * @agricultural_context Specialized analysis for agricultural model relationships and state
     * @model_inspection Detailed analysis of Laravel model attributes and relationships
     */
    public static function checkpoint($object, string $label = 'checkpoint')
    {
        $data = [];
        
        // Check if it's an object and add class type
        if (is_object($object)) {
            $data['type'] = get_class($object);
            $data['hash'] = spl_object_hash($object);
            
            // If it has a toArray method, use it
            if (method_exists($object, 'toArray')) {
                try {
                    $data['data'] = $object->toArray();
                } catch (Throwable $e) {
                    $data['data'] = '[Could not convert to array: ' . $e->getMessage() . ']';
                }
            }
            
            // Get object methods
            $data['methods'] = get_class_methods($object);
            
            // Check specifically for Laravel models
            if (method_exists($object, 'getAttribute')) {
                try {
                    $data['attributes'] = $object->getAttributes();
                    
                    // Check for loaded relations
                    if (method_exists($object, 'getRelations')) {
                        $relations = $object->getRelations();
                        $relationData = [];
                        
                        foreach ($relations as $name => $relation) {
                            $relationData[$name] = [
                                'type' => is_object($relation) ? get_class($relation) : gettype($relation),
                                'isNull' => is_null($relation),
                            ];
                        }
                        
                        $data['relations'] = $relationData;
                    }
                } catch (Throwable $e) {
                    $data['error_getting_attributes'] = $e->getMessage();
                }
            }
        } else {
            $data['type'] = gettype($object);
            $data['value'] = is_scalar($object) ? $object : '[Non-scalar value]';
        }
        
        // Write to a unique file
        $filename = date('Y-m-d_H-i-s') . '_' . $label . '.json';
        return self::writeToFile($filename, $data);
    }
    
    /**
     * Summarize function arguments for agricultural debugging logs.
     * 
     * Creates concise summaries of function arguments for stack trace
     * logging, handling agricultural objects and large data structures
     * appropriately for debugging analysis.
     *
     * @param array $args Function arguments to summarize
     * @return array Summarized argument information for logging
     * @agricultural_context Handles agricultural model objects appropriately in summaries
     */
    private static function summarizeArgs(array $args): array
    {
        return array_map(function ($arg) {
            if (is_null($arg)) {
                return 'null';
            } elseif (is_scalar($arg)) {
                return is_string($arg) ? (strlen($arg) > 50 ? substr($arg, 0, 47) . '...' : $arg) : $arg;
            } elseif (is_array($arg)) {
                return '[Array with ' . count($arg) . ' items]';
            } elseif (is_object($arg)) {
                return '[Object of class ' . get_class($arg) . ']';
            }
            return gettype($arg);
        }, $args);
    }
} 