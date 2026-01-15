#!/usr/bin/env php
<?php
/**
 * AI Code Analyzer for SonarQube
 * Detects AI-generated code patterns and common AI coding errors
 * 
 * Usage: php ai-code-analyzer.php [path/to/analyze]
 */

declare(strict_types=1);

class AICodeAnalyzer
{
    private array $aiPatterns = [];
    private array $aiErrors = [];
    private array $findings = [];
    private int $scoreAI = 0;
    private int $totalScore = 0;

    public function __construct()
    {
        $this->loadPatterns();
    }

    /**
     * Load AI detection patterns from JSON file
     */
    private function loadPatterns(): void
    {
        $patternFile = __DIR__ . '/.ai-patterns.json';
        
        if (file_exists($patternFile)) {
            $data = json_decode(file_get_contents($patternFile), true);
            $this->aiPatterns = $data['patterns'] ?? [];
            $this->aiErrors = $data['errors'] ?? [];
        } else {
            // Default patterns if file doesn't exist
            $this->aiPatterns = $this->getDefaultPatterns();
            $this->aiErrors = $this->getDefaultErrors();
        }
    }

    /**
     * Default AI patterns to detect
     */
    private function getDefaultPatterns(): array
    {
        return [
            [
                'name' => 'Excessive Comments',
                'pattern' => '/\/\*[\s\S]{200,}?\*\//',
                'weight' => 3,
                'description' => 'AI code often has very long, detailed comments'
            ],
            [
                'name' => 'Generic Variable Names',
                'pattern' => '/(\$data|\$result|\$output|\$item|\$temp)\b/',
                'weight' => 2,
                'description' => 'AI tends to use generic variable names'
            ],
            [
                'name' => 'Magic Numbers in Loops',
                'pattern' => '/for\s*\(\s*\$i\s*=\s*0;\s*\$i\s*<\s*[0-9]+;/',
                'weight' => 2,
                'description' => 'Magic numbers often appear in AI-generated loops'
            ],
            [
                'name' => 'Template Comments',
                'pattern' => '/(\/\/|[\/*])\s*(This method|This function|The following|In this section)/i',
                'weight' => 4,
                'description' => 'Generic template comments are AI hallmarks'
            ],
            [
                'name' => 'Overly Perfect Formatting',
                'pattern' => '/\{\s*[\r\n]+\s*[\r\n]+\s*\}/',
                'weight' => 2,
                'description' => 'Excessive whitespace in empty blocks'
            ],
            [
                'name' => 'Chained Method Calls',
                'pattern' => '/->\w+\(\)->\w+\(\)->\w+\(\)/',
                'weight' => 3,
                'description' => 'Long method chains are common in AI code'
            ],
            [
                'name' => 'Generic Error Messages',
                'pattern' => '/throw new Exception\s*\(\s*["\']Error["\']\s*\)/',
                'weight' => 3,
                'description' => 'Generic error messages suggest AI generation'
            ],
            [
                'name' => 'Unused Imports/Requires',
                'pattern' => '/require\s+[^\n]+\s*[;\n]/',
                'weight' => 1,
                'check' => 'unused_imports',
                'description' => 'May have unused dependencies'
            ]
        ];
    }

    /**
     * Default AI coding errors to detect
     */
    private function getDefaultErrors(): array
    {
        return [
            [
                'name' => 'Empty Try-Catch',
                'pattern' => '/try\s*\{[^}]*\}\s*catch\s*\([^)]*\)\s*\{[^}]{0,50}\}/s',
                'weight' => 5,
                'severity' => 'BLOCKER',
                'description' => 'Empty or minimal catch blocks - common AI mistake'
            ],
            [
                'name' => 'SQL Injection Risk',
                'pattern' => '/->prepare\s*\(\s*["\'][^"\']*\$.*["\']/',
                'weight' => 10,
                'severity' => 'CRITICAL',
                'description' => 'Potential SQL injection - variable interpolation in SQL'
            ],
            [
                'name' => 'XSS Vulnerability',
                'pattern' => '/echo\s+.*\$_(?:GET|POST|REQUEST)\[/',
                'weight' => 8,
                'severity' => 'CRITICAL',
                'description' => 'Direct output of user input without escaping'
            ],
            [
                'name' => 'Type Juggling',
                'pattern' => '/==\s*(?![\'"])/',
                'weight' => 3,
                'severity' => 'MAJOR',
                'check' => 'type_juggling',
                'description' => 'Loose comparison instead of strict equality'
            ],
            [
                'name' => 'Missing Input Validation',
                'pattern' => '/\$_(?:GET|POST|REQUEST)\[/',
                'weight' => 4,
                'severity' => 'MAJOR',
                'check' => 'input_validation',
                'description' => 'Direct access to superglobals without validation'
            ],
            [
                'name' => 'Unsafe eval() Usage',
                'pattern' => '/eval\s*\(\s*\$/',
                'weight' => 10,
                'severity' => 'CRITICAL',
                'description' => 'eval() with variable input - security risk'
            ],
            [
                'name' => 'File Include with User Input',
                'pattern' => '/(?:include|require|include_once|require_once)\s*\([^)]*\$_(?:GET|POST|REQUEST)/',
                'weight' => 9,
                'severity' => 'CRITICAL',
                'description' => 'File inclusion with user-controlled path'
            ],
            [
                'name' => 'Hardcoded Passwords',
                'pattern' => '/["\'](?:password|passwd|pwd)["\']\s*=>\s*["\'][^"\']+["\']/',
                'weight' => 8,
                'severity' => 'CRITICAL',
                'description' => 'Hardcoded credentials detected'
            ],
            [
                'name' => 'Missing Strict Types',
                'pattern' => '/<\?php\s*(?!\s*declare\s*\(\s*strict_types\s*=\s*1\s*\))/i',
                'weight' => 3,
                'severity' => 'MAJOR',
                'description' => 'Missing strict_types declaration'
            ],
            [
                'name' => 'Unchecked Return Values',
                'pattern' => '/->\w+\(\)\s*;/',
                'weight' => 4,
                'severity' => 'MAJOR',
                'check' => 'return_value',
                'description' => 'Function return value not checked'
            ],
            [
                'name' => 'Use of @ to Suppress Errors',
                'pattern' => '/@[a-z_]+\s*\(/',
                'weight' => 5,
                'severity' => 'MAJOR',
                'description' => 'Error suppression operator used'
            ],
            [
                'name' => 'Incorrect Return Type',
                'pattern' => '/function\s+\w+\s*\([^)]*\)\s*:\s*\w+\s*\{/',
                'weight' => 4,
                'severity' => 'MAJOR',
                'check' => 'return_type',
                'description' => 'Function with return type but may not return correctly'
            ]
        ];
    }

    /**
     * Analyze a file for AI patterns and errors
     */
    public function analyzeFile(string $filePath): array
    {
        if (!file_exists($filePath)) {
            return ['error' => "File not found: $filePath"];
        }

        $content = file_get_contents($filePath);
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        // Only analyze PHP files
        if ($extension !== 'php') {
            return ['error' => "Only PHP files are supported"];
        }

        $this->findings = [];
        $this->scoreAI = 0;
        $this->totalScore = 0;

        // Detect AI patterns
        $this->detectAIPatterns($content, $filePath);

        // Detect AI coding errors
        $this->detectAIErrors($content, $filePath);

        // Calculate AI probability
        $aiProbability = $this->totalScore > 0 
            ? min(100, round(($this->scoreAI / $this->totalScore) * 100))
            : 0;

        return [
            'file' => $filePath,
            'ai_probability' => $aiProbability . '%',
            'is_likely_ai' => $aiProbability > 50,
            'findings' => $this->findings,
            'summary' => [
                'ai_patterns_found' => count(array_filter($this->findings, fn($f) => $f['type'] === 'pattern')),
                'ai_errors_found' => count(array_filter($this->findings, fn($f) => $f['type'] === 'error')),
                'total_findings' => count($this->findings)
            ]
        ];
    }

    /**
     * Analyze a directory recursively
     */
    public function analyzeDirectory(string $directory): array
    {
        $results = [];
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory)
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $results[] = $this->analyzeFile($file->getPathname());
            }
        }

        return $this->aggregateResults($results);
    }

    /**
     * Detect AI-specific patterns in code
     */
    private function detectAIPatterns(string $content, string $file): void
    {
        foreach ($this->aiPatterns as $pattern) {
            $regex = $pattern['pattern'];
            
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $this->findings[] = [
                        'type' => 'pattern',
                        'name' => $pattern['name'],
                        'line' => $this->getLineNumber($content, $match[1]),
                        'message' => $pattern['description'],
                        'code' => substr($match[0], 0, 80),
                        'weight' => $pattern['weight']
                    ];
                    $this->scoreAI += $pattern['weight'];
                    $this->totalScore += 10;
                }
            }
        }

        // Additional heuristic: Comment-to-code ratio
        $this->analyzeCommentRatio($content, $file);
    }

    /**
     * Detect common AI coding errors
     */
    private function detectAIErrors(string $content, string $file): void
    {
        $lines = explode("\n", $content);
        
        foreach ($this->aiErrors as $error) {
            $regex = $error['pattern'];
            
            if (preg_match_all($regex, $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $lineNum = $this->getLineNumber($content, $match[1]);
                    
                    // Skip if it's in a comment (false positive)
                    if ($this->isInComment($content, $match[1], $lines)) {
                        continue;
                    }

                    $this->findings[] = [
                        'type' => 'error',
                        'name' => $error['name'],
                        'line' => $lineNum,
                        'severity' => $error['severity'] ?? 'MAJOR',
                        'message' => $error['description'],
                        'code' => substr($match[0], 0, 80),
                        'weight' => $error['weight']
                    ];
                    $this->scoreAI += $error['weight'];
                    $this->totalScore += 10;
                }
            }
        }
    }

    /**
     * Analyze comment-to-code ratio (AI tends to have more comments)
     */
    private function analyzeCommentRatio(string $content, string $file): void
    {
        $lines = explode("\n", $content);
        $totalLines = count($lines);
        $commentLines = 0;
        $codeLines = 0;

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if (preg_match('/^\s*(\/\/|\/\*|\*|#)/', $trimmed)) {
                $commentLines++;
            } elseif (!empty($trimmed) && strpos($trimmed, '<?php') === false) {
                $codeLines++;
            }
        }

        $ratio = $codeLines > 0 ? ($commentLines / $codeLines) : 0;

        // AI code typically has higher comment ratio
        if ($ratio > 0.4) {
            $this->findings[] = [
                'type' => 'pattern',
                'name' => 'High Comment Ratio',
                'line' => 1,
                'message' => "Comment-to-code ratio of " . round($ratio * 100) . "% suggests AI generation",
                'weight' => 3
            ];
            $this->scoreAI += 3;
            $this->totalScore += 10;
        }
    }

    /**
     * Get line number from character offset
     */
    private function getLineNumber(string $content, int $offset): int
    {
        return substr_count(substr($content, 0, $offset), "\n") + 1;
    }

    /**
     * Check if position is within a comment
     */
    private function isInComment(string $content, int $position, array $lines): bool
    {
        $lineNum = $this->getLineNumber($content, $position);
        $lineIndex = $lineNum - 1;
        
        if (!isset($lines[$lineIndex])) {
            return false;
        }

        $line = $lines[$lineIndex];
        
        // Check for single-line comments
        if (preg_match('/\/\/.*$/', $line, $matches, PREG_OFFSET_CAPTURE)) {
            if ($matches[0][1] < $position) {
                return true;
            }
        }

        return false;
    }

    /**
     * Aggregate results from multiple files
     */
    private function aggregateResults(array $results): array
    {
        $totalPatterns = 0;
        $totalErrors = 0;
        $allFindings = [];
        $filesAnalyzed = 0;
        $aiFiles = 0;

        foreach ($results as $result) {
            if (isset($result['error'])) {
                continue;
            }
            
            $filesAnalyzed++;
            $totalPatterns += $result['summary']['ai_patterns_found'];
            $totalErrors += $result['summary']['ai_errors_found'];
            
            if ($result['is_likely_ai']) {
                $aiFiles++;
            }
            
            $allFindings = array_merge($allFindings, $result['findings']);
        }

        $aiProbability = $filesAnalyzed > 0 
            ? round(($aiFiles / $filesAnalyzed) * 100) 
            : 0;

        return [
            'directory_analysis' => true,
            'ai_probability' => $aiProbability . '%',
            'is_likely_ai' => $aiProbability > 50,
            'files_analyzed' => $filesAnalyzed,
            'ai_generated_files' => $aiFiles,
            'total_patterns_found' => $totalPatterns,
            'total_errors_found' => $totalErrors,
            'findings' => $allFindings
        ];
    }

    /**
     * Generate SonarQube compatible report
     */
    public function generateSonarQubeReport(string $path): array
    {
        if (is_dir($path)) {
            $results = $this->analyzeDirectory($path);
        } else {
            $results = $this->analyzeFile($path);
        }

        // Convert to SonarQube generic issue format
        $sonarIssues = [];
        
        foreach ($this->findings as $finding) {
            $sonarIssues[] = [
                'engineId' => 'AI-Detector',
                'ruleId' => str_replace(' ', '-', strtolower($finding['name'])),
                'severity' => $finding['severity'] ?? $this->getSonarSeverity($finding['type']),
                'type' => $finding['type'] === 'error' ? 'VULNERABILITY' : 'CODE_SMELL',
                'primaryLocation' => [
                    'message' => $finding['message'],
                    'filePath' => $path,
                    'textRange' => [
                        'startLine' => $finding['line']
                    ]
                ]
            ];
        }

        return [
            'issues' => $sonarIssues,
            'summary' => $results
        ];
    }

    /**
     * Get SonarQube severity from finding type
     */
    private function getSonarSeverity(string $type): string
    {
        return $type === 'error' ? 'MAJOR' : 'INFO';
    }
}

// CLI Interface
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($argv[0] ?? '')) {
    $analyzer = new AICodeAnalyzer();
    
    $target = $argv[1] ?? __DIR__ . '/src';
    
    if (!file_exists($target)) {
        echo "Error: Path not found: $target\n";
        exit(1);
    }
    
    echo "AI Code Analyzer - Starting analysis...\n";
    echo "Target: $target\n";
    echo str_repeat('-', 50) . "\n\n";
    
    $startTime = microtime(true);
    
    if (is_dir($target)) {
        $results = $analyzer->analyzeDirectory($target);
    } else {
        $results = $analyzer->analyzeFile($target);
    }
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "Analysis completed in {$duration}ms\n\n";
    
    if (isset($results['error'])) {
        echo "Error: {$results['error']}\n";
        exit(1);
    }
    
    // Display results
    echo "=== AI Detection Results ===\n";
    echo "AI Probability: {$results['ai_probability']}\n";
    echo "Likely AI Generated: " . ($results['is_likely_ai'] ? 'YES' : 'NO') . "\n\n";
    
    if (isset($results['files_analyzed'])) {
        echo "Files Analyzed: {$results['files_analyzed']}\n";
        echo "AI-Generated Files: {$results['ai_generated_files']}\n";
    }
    
    echo "\n=== Summary ===\n";
    echo "AI Patterns Found: {$results['summary']['ai_patterns_found']}\n";
    echo "AI Errors Found: {$results['summary']['ai_errors_found']}\n";
    echo "Total Findings: {$results['summary']['total_findings']}\n\n";
    
    if (!empty($results['findings'])) {
        echo "=== Detailed Findings ===\n";
        foreach ($results['findings'] as $i => $finding) {
            $icon = $finding['type'] === 'error' ? '[!]' : '[?]';
            $severity = $finding['severity'] ?? 'N/A';
            echo sprintf(
                "%s [%s] Line %d: %s\n    %s\n\n",
                $icon,
                $severity,
                $finding['line'],
                $finding['name'],
                $finding['message']
            );
        }
    }
    
    // Exit code based on AI probability
    exit($results['is_likely_ai'] ? 0 : 0);
}

return AICodeAnalyzer::class;

