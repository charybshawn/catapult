<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\File;
use Throwable;

class DebugService
{
    /**
     * Log detailed error information for debugging
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
     * Add debug data to a log file
     */
    public static function logData(string $message, array $data, string $context = 'debug')
    {
        Log::channel('daily')->info("DebugService: {$context} - {$message}", $data);
    }
    
    /**
     * Write debug information to a file in storage/logs
     */
    public static function writeToFile(string $filename, array $data)
    {
        $path = storage_path('logs/' . $filename);
        $jsonData = json_encode($data, JSON_PRETTY_PRINT);
        File::put($path, $jsonData);
        
        return $path;
    }
    
    /**
     * Create a debug checkpoint to examine the state of objects
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
     * Summarize function arguments for logging
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