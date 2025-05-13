<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Error</title>
    <style>
        body {
            font-family: system-ui, -apple-system, sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 20px;
            background: #f8f9fa;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            margin-top: 0;
            color: #e74c3c;
        }
        h2 {
            margin-top: 30px;
            color: #3498db;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        pre {
            background: #f5f5f5;
            padding: 15px;
            overflow-x: auto;
            border-radius: 4px;
        }
        .location {
            font-family: monospace;
            background: #fafafa;
            padding: 10px;
            border-left: 4px solid #e74c3c;
        }
        .var-dump {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
        }
        details {
            margin: 20px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        summary {
            padding: 10px;
            background: #f5f5f5;
            cursor: pointer;
        }
        .properties {
            padding: 15px;
        }
        .property {
            margin-bottom: 8px;
            word-wrap: break-word;
        }
        .property-name {
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Debug Error: {{ get_class($exception) }}</h1>
        
        <div class="location">
            <strong>{{ $message }}</strong><br>
            {{ $file }} (line {{ $line }})
        </div>
        
        <h2>Debug Information</h2>
        
        <details>
            <summary>Stack Trace</summary>
            <pre>{{ $trace }}</pre>
        </details>
        
        <h2>Request Information</h2>
        
        <details>
            <summary>Basic Request Info</summary>
            <div class="properties">
                <div class="property">
                    <span class="property-name">URL:</span> {{ $request->fullUrl() }}
                </div>
                <div class="property">
                    <span class="property-name">Method:</span> {{ $request->method() }}
                </div>
                <div class="property">
                    <span class="property-name">IP:</span> {{ $request->ip() }}
                </div>
                <div class="property">
                    <span class="property-name">Authenticated User:</span> {{ $request->user() ? $request->user()->id : 'Guest' }}
                </div>
            </div>
        </details>
        
        <details>
            <summary>Request Headers</summary>
            <pre>@foreach($request->headers->all() as $header => $values){{ $header }}: {{ implode(', ', $values) }}
@endforeach</pre>
        </details>
        
        <details>
            <summary>Request Parameters</summary>
            <pre>{{ json_encode($request->all(), JSON_PRETTY_PRINT) }}</pre>
        </details>
        
        <h2>Debug Tools</h2>
        
        <details>
            <summary>Session Data</summary>
            <pre>{{ json_encode($request->session()->all(), JSON_PRETTY_PRINT) }}</pre>
        </details>
        
        <details>
            <summary>Route Information</summary>
            <div class="properties">
                <div class="property">
                    <span class="property-name">Route Name:</span> {{ $request->route() ? $request->route()->getName() : 'N/A' }}
                </div>
                <div class="property">
                    <span class="property-name">Controller:</span> 
                    @if($request->route() && $request->route()->getAction()['uses'] ?? false)
                        {{ $request->route()->getAction()['uses'] }}
                    @else
                        N/A
                    @endif
                </div>
                <div class="property">
                    <span class="property-name">Route Parameters:</span>
                    <pre>{{ json_encode($request->route() ? $request->route()->parameters() : [], JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </details>
    </div>
</body>
</html> 