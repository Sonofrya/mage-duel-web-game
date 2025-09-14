<?php

class Logger {
    
    const LEVEL_DEBUG = 'DEBUG';
    const LEVEL_INFO = 'INFO';
    const LEVEL_WARNING = 'WARNING';
    const LEVEL_ERROR = 'ERROR';
    const LEVEL_CRITICAL = 'CRITICAL';
    
    private static $logFile = 'logs/app.log';
    private static $maxFileSize = 10485760; // 10MB
    
    /**
     * Логирует сообщение
     */
    public static function log(string $level, string $message, array $context = []): void {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;
        
        // Создаем директорию логов если её нет
        $logDir = dirname(self::$logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Ротация логов если файл слишком большой
        if (file_exists(self::$logFile) && filesize(self::$logFile) > self::$maxFileSize) {
            self::rotateLog();
        }
        
        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Также выводим критические ошибки в error_log
        if ($level === self::LEVEL_CRITICAL) {
            error_log($logEntry);
        }
    }
    
    /**
     * Debug сообщение
     */
    public static function debug(string $message, array $context = []): void {
        self::log(self::LEVEL_DEBUG, $message, $context);
    }
    
    /**
     * Info сообщение
     */
    public static function info(string $message, array $context = []): void {
        self::log(self::LEVEL_INFO, $message, $context);
    }
    
    /**
     * Warning сообщение
     */
    public static function warning(string $message, array $context = []): void {
        self::log(self::LEVEL_WARNING, $message, $context);
    }
    
    /**
     * Error сообщение
     */
    public static function error(string $message, array $context = []): void {
        self::log(self::LEVEL_ERROR, $message, $context);
    }
    
    /**
     * Critical сообщение
     */
    public static function critical(string $message, array $context = []): void {
        self::log(self::LEVEL_CRITICAL, $message, $context);
    }
    
    /**
     * Логирует игровые события
     */
    public static function gameEvent(string $event, array $data = []): void {
        self::info("Game event: {$event}", $data);
    }
    
    /**
     * Логирует WebSocket события
     */
    public static function websocketEvent(string $event, array $data = []): void {
        self::debug("WebSocket event: {$event}", $data);
    }
    
    /**
     * Логирует производительность
     */
    public static function performance(string $operation, float $time, array $context = []): void {
        self::info("Performance: {$operation} took {$time}s", $context);
    }
    
    /**
     * Ротация логов
     */
    private static function rotateLog(): void {
        $backupFile = self::$logFile . '.' . date('Y-m-d-H-i-s');
        rename(self::$logFile, $backupFile);
        
        // Сжимаем старый лог
        if (function_exists('gzopen')) {
            $gz = gzopen($backupFile . '.gz', 'w9');
            gzwrite($gz, file_get_contents($backupFile));
            gzclose($gz);
            unlink($backupFile);
        }
    }
    
    /**
     * Получает последние записи лога
     */
    public static function getRecentLogs(int $lines = 100): array {
        if (!file_exists(self::$logFile)) {
            return [];
        }
        
        $logContent = file_get_contents(self::$logFile);
        $logLines = explode(PHP_EOL, $logContent);
        $logLines = array_filter($logLines);
        
        return array_slice($logLines, -$lines);
    }
}
?>
